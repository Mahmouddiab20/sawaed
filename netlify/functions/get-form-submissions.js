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

        // Get Netlify site ID and access token from environment variables
        const siteId = process.env.NETLIFY_SITE_ID;
        const accessToken = process.env.NETLIFY_ACCESS_TOKEN;

        if (!siteId || !accessToken) {
            return {
                statusCode: 500,
                headers: {
                    'Content-Type': 'application/json; charset=utf-8',
                    'Access-Control-Allow-Origin': '*'
                },
                body: JSON.stringify({ 
                    success: false, 
                    message: 'Netlify configuration missing. Please set NETLIFY_SITE_ID and NETLIFY_ACCESS_TOKEN environment variables.' 
                })
            };
        }

        // Fetch form submissions from Netlify API
        const netlifyResponse = await fetch(`https://api.netlify.com/api/v1/sites/${siteId}/forms/contact/submissions`, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${accessToken}`,
                'Content-Type': 'application/json'
            }
        });

        if (!netlifyResponse.ok) {
            throw new Error(`Netlify API error: ${netlifyResponse.status}`);
        }

        const submissions = await netlifyResponse.json();

        // Transform the data to our format
        const transformedData = submissions.map((submission, index) => ({
            id: submission.id || index + 1,
            name: submission.data.name || 'غير محدد',
            email: submission.data.email || 'غير محدد',
            phone: submission.data.phone || 'غير محدد',
            subject: submission.data.subject || 'غير محدد',
            message: submission.data.message || 'غير محدد',
            created_at: submission.created_at || new Date().toISOString(),
            ip_address: submission.ip || 'غير محدد',
            user_agent: submission.user_agent || 'غير محدد'
        }));

        return {
            statusCode: 200,
            headers: {
                'Content-Type': 'application/json; charset=utf-8',
                'Access-Control-Allow-Origin': '*'
            },
            body: JSON.stringify({
                success: true,
                data: transformedData,
                total: transformedData.length
            })
        };

    } catch (error) {
        console.error('Get form submissions error:', error);
        
        return {
            statusCode: 500,
            headers: {
                'Content-Type': 'application/json; charset=utf-8',
                'Access-Control-Allow-Origin': '*'
            },
            body: JSON.stringify({
                success: false,
                message: 'Internal server error',
                error: error.message
            })
        };
    }
};
