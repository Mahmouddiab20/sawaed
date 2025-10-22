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

        console.log('Site ID:', siteId);
        console.log('Access Token:', accessToken ? 'Present' : 'Missing');

        if (!siteId || !accessToken) {
            return {
                statusCode: 500,
                headers: {
                    'Content-Type': 'application/json; charset=utf-8',
                    'Access-Control-Allow-Origin': '*'
                },
                body: JSON.stringify({ 
                    success: false, 
                    message: 'Netlify configuration missing. Please set NETLIFY_SITE_ID and NETLIFY_ACCESS_TOKEN environment variables.',
                    debug: {
                        siteId: !!siteId,
                        accessToken: !!accessToken
                    }
                })
            };
        }

        // For now, let's return mock data to test the dashboard
        // Later we can implement the real Netlify API call
        const mockSubmissions = [
            {
                id: '1',
                data: {
                    name: 'أحمد محمد',
                    email: 'ahmed@example.com',
                    phone: '+966501234567',
                    subject: 'استفسار عن خدمات التسويق',
                    message: 'أريد معرفة المزيد عن خدماتكم في التسويق الرقمي'
                },
                created_at: new Date().toISOString()
            },
            {
                id: '2',
                data: {
                    name: 'فاطمة علي',
                    email: 'fatima@example.com',
                    phone: '+966507654321',
                    subject: 'طلب عرض أسعار',
                    message: 'أحتاج عرض أسعار لخدمات إدارة وسائل التواصل الاجتماعي'
                },
                created_at: new Date(Date.now() - 86400000).toISOString()
            }
        ];

        const submissions = mockSubmissions;

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
