const { createClient } = require('@supabase/supabase-js');

exports.handler = async (event, context) => {
    // Handle CORS preflight requests
    if (event.httpMethod === 'OPTIONS') {
        return {
            statusCode: 200,
            headers: {
                'Access-Control-Allow-Origin': '*',
                'Access-Control-Allow-Headers': 'Content-Type',
                'Access-Control-Allow-Methods': 'POST, OPTIONS'
            },
            body: ''
        };
    }

    if (event.httpMethod !== 'POST') {
        return { 
            statusCode: 405, 
            body: JSON.stringify({ success: false, message: 'Method not allowed' })
        };
    }

    try {
        // Parse request body
        const { consent, preferences, timestamp } = JSON.parse(event.body);
        
        // Get client IP
        const clientIP = event.headers['x-forwarded-for'] || 
                        event.headers['x-real-ip'] || 
                        event.headers['client-ip'] ||
                        'unknown';

        // Get geolocation data
        let geoData = {};
        try {
            const geoResponse = await fetch(`https://ipapi.co/${clientIP}/json/`);
            geoData = await geoResponse.json();
        } catch (geoError) {
            console.log('Geolocation failed:', geoError);
            geoData = {
                country_name: 'Unknown',
                city: 'Unknown'
            };
        }

        // Initialize Supabase
        const supabase = createClient(
            process.env.SUPABASE_URL || 'your-supabase-url',
            process.env.SUPABASE_ANON_KEY || 'your-supabase-anon-key'
        );

        // Store consent records for each consent type
        const consentTypes = ['analytics', 'marketing', 'necessary'];
        const consentRecords = consentTypes.map(type => ({
            ip_address: clientIP,
            consent_type: type,
            consent_given: preferences[type] || false,
            consent_timestamp: timestamp || new Date().toISOString(),
            user_agent: event.headers['user-agent'] || 'Unknown',
            country: geoData.country_name || 'Unknown'
        }));

        // Insert consent records
        const { data, error } = await supabase
            .from('consent_records')
            .insert(consentRecords);

        if (error) {
            console.error('Consent storage error:', error);
            // Don't fail the request if consent storage fails
        }

        return {
            statusCode: 200,
            headers: {
                'Content-Type': 'application/json; charset=utf-8',
                'Access-Control-Allow-Origin': '*'
            },
            body: JSON.stringify({
                success: true,
                message: 'Consent preferences saved',
                data: {
                    consent: consent,
                    preferences: preferences,
                    timestamp: timestamp || new Date().toISOString()
                }
            })
        };

    } catch (error) {
        console.error('Consent handler error:', error);
        
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
