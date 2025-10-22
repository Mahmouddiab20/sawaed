const { createClient } = require('@supabase/supabase-js');

exports.handler = async (event, context) => {
    // Handle CORS preflight requests
    if (event.httpMethod === 'OPTIONS') {
        return {
            statusCode: 200,
            headers: {
                'Access-Control-Allow-Origin': '*',
                'Access-Control-Allow-Headers': 'Content-Type, Authorization',
                'Access-Control-Allow-Methods': 'GET, OPTIONS'
            },
            body: ''
        };
    }

    if (event.httpMethod !== 'GET') {
        return { 
            statusCode: 405, 
            body: JSON.stringify({ success: false, message: 'Method not allowed' })
        };
    }

    try {
        // Simple authentication check (you can improve this)
        const authHeader = event.headers.authorization;
        if (!authHeader || authHeader !== `Bearer ${process.env.ADMIN_TOKEN || 'sawaed-admin-2024'}`) {
            return {
                statusCode: 401,
                headers: {
                    'Content-Type': 'application/json; charset=utf-8',
                    'Access-Control-Allow-Origin': '*'
                },
                body: JSON.stringify({ success: false, message: 'Unauthorized' })
            };
        }

        // Initialize Supabase
        const supabase = createClient(
            process.env.SUPABASE_URL || 'your-supabase-url',
            process.env.SUPABASE_ANON_KEY || 'your-supabase-anon-key'
        );

        // Get query parameters
        const { limit = 50, offset = 0, form_type } = event.queryStringParameters || {};

        // Build query
        let query = supabase
            .from('leads')
            .select('*')
            .order('created_at', { ascending: false })
            .range(offset, offset + limit - 1);

        // Filter by form type if specified
        if (form_type) {
            query = query.eq('form_type', form_type);
        }

        const { data: leads, error } = await query;

        if (error) {
            console.error('Database error:', error);
            throw error;
        }

        // Get analytics data
        const { data: analytics } = await supabase
            .from('leads')
            .select('form_type, country, consent_given, created_at');

        // Calculate statistics
        const stats = {
            total_leads: leads.length,
            contact_leads: analytics.filter(lead => lead.form_type === 'contact').length,
            job_leads: analytics.filter(lead => lead.form_type === 'job_application').length,
            consented_leads: analytics.filter(lead => lead.consent_given).length,
            unique_countries: [...new Set(analytics.map(lead => lead.country))].length
        };

        // Get geographic distribution
        const geoDistribution = {};
        analytics.forEach(lead => {
            if (lead.country) {
                geoDistribution[lead.country] = (geoDistribution[lead.country] || 0) + 1;
            }
        });

        return {
            statusCode: 200,
            headers: {
                'Content-Type': 'application/json; charset=utf-8',
                'Access-Control-Allow-Origin': '*'
            },
            body: JSON.stringify({
                success: true,
                data: {
                    leads: leads,
                    statistics: stats,
                    geographic_distribution: geoDistribution,
                    total_count: leads.length
                }
            })
        };

    } catch (error) {
        console.error('Get leads error:', error);
        
        return {
            statusCode: 500,
            headers: {
                'Content-Type': 'application/json; charset=utf-8',
                'Access-Control-Allow-Origin': '*'
            },
            body: JSON.stringify({
                success: false,
                message: 'Internal server error'
            })
        };
    }
};
