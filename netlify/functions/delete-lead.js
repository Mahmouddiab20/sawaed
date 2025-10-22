const { createClient } = require('@supabase/supabase-js');

exports.handler = async (event, context) => {
    // Handle CORS preflight requests
    if (event.httpMethod === 'OPTIONS') {
        return {
            statusCode: 200,
            headers: {
                'Access-Control-Allow-Origin': '*',
                'Access-Control-Allow-Headers': 'Content-Type, Authorization',
                'Access-Control-Allow-Methods': 'DELETE, OPTIONS'
            },
            body: ''
        };
    }

    if (event.httpMethod !== 'DELETE') {
        return { 
            statusCode: 405, 
            body: JSON.stringify({ success: false, message: 'Method not allowed' })
        };
    }

    try {
        // Simple authentication check
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

        // Get lead ID from query parameters
        const { id } = event.queryStringParameters || {};
        
        if (!id) {
            return {
                statusCode: 400,
                headers: {
                    'Content-Type': 'application/json; charset=utf-8',
                    'Access-Control-Allow-Origin': '*'
                },
                body: JSON.stringify({ success: false, message: 'Lead ID is required' })
            };
        }

        // Initialize Supabase
        const supabase = createClient(
            process.env.SUPABASE_URL || 'your-supabase-url',
            process.env.SUPABASE_ANON_KEY || 'your-supabase-anon-key'
        );

        // Get lead details before deletion for logging
        const { data: lead, error: fetchError } = await supabase
            .from('leads')
            .select('name, email')
            .eq('id', id)
            .single();

        if (fetchError || !lead) {
            return {
                statusCode: 404,
                headers: {
                    'Content-Type': 'application/json; charset=utf-8',
                    'Access-Control-Allow-Origin': '*'
                },
                body: JSON.stringify({ success: false, message: 'Lead not found' })
            };
        }

        // Delete the lead
        const { error: deleteError } = await supabase
            .from('leads')
            .delete()
            .eq('id', id);

        if (deleteError) {
            console.error('Delete error:', deleteError);
            throw deleteError;
        }

        // Log the deletion
        console.log(`Lead deleted: ID=${id}, Name=${lead.name}, Email=${lead.email}`);

        return {
            statusCode: 200,
            headers: {
                'Content-Type': 'application/json; charset=utf-8',
                'Access-Control-Allow-Origin': '*'
            },
            body: JSON.stringify({
                success: true,
                message: 'Lead deleted successfully'
            })
        };

    } catch (error) {
        console.error('Delete lead error:', error);
        
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
