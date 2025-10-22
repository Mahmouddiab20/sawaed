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

        // Get environment variables
        const siteId = process.env.NETLIFY_SITE_ID;
        const accessToken = process.env.NETLIFY_ACCESS_TOKEN;

        console.log('Fetching real data from Netlify API...');
        console.log('Site ID:', siteId);
        console.log('Access Token:', accessToken ? 'Present' : 'Missing');

        let realData = [];

        if (siteId && accessToken) {
            try {
                // Fetch real form submissions from Netlify API
                const netlifyResponse = await fetch(`https://api.netlify.com/api/v1/sites/${siteId}/forms/contact/submissions`, {
                    method: 'GET',
                    headers: {
                        'Authorization': `Bearer ${accessToken}`,
                        'Content-Type': 'application/json'
                    }
                });

                if (netlifyResponse.ok) {
                    const submissions = await netlifyResponse.json();
                    console.log('Real submissions found:', submissions.length);
                    
                    // Transform real data
                    realData = submissions.map((submission, index) => ({
                        id: submission.id || index + 1,
                        name: submission.data.name || 'غير محدد',
                        email: submission.data.email || 'غير محدد',
                        phone: submission.data.phone || 'غير محدد',
                        subject: submission.data.subject || 'غير محدد',
                        message: submission.data.message || 'غير محدد',
                        created_at: submission.created_at || new Date().toISOString()
                    }));
                } else {
                    console.error('Netlify API error:', netlifyResponse.status);
                    throw new Error(`Netlify API error: ${netlifyResponse.status}`);
                }
            } catch (apiError) {
                console.error('API Error:', apiError);
                // Fallback to sample data if API fails
                realData = [
                    {
                        id: 1,
                        name: 'خطأ في تحميل البيانات',
                        email: 'error@example.com',
                        phone: 'غير متوفر',
                        subject: 'لا يمكن تحميل البيانات الحقيقية',
                        message: 'يرجى التحقق من إعدادات Netlify API',
                        created_at: new Date().toISOString()
                    }
                ];
            }
        } else {
            console.log('Missing environment variables, using sample data');
            // Fallback to sample data if env vars missing
            realData = [
                {
                    id: 1,
                    name: 'إعدادات مفقودة',
                    email: 'config@example.com',
                    phone: 'غير متوفر',
                    subject: 'NETLIFY_SITE_ID أو NETLIFY_ACCESS_TOKEN مفقود',
                    message: 'يرجى إضافة المتغيرات المطلوبة في إعدادات Netlify',
                    created_at: new Date().toISOString()
                }
            ];
        }

        return {
            statusCode: 200,
            headers: {
                'Content-Type': 'application/json; charset=utf-8',
                'Access-Control-Allow-Origin': '*'
            },
            body: JSON.stringify({
                success: true,
                data: realData,
                total: realData.length,
                message: realData.length > 0 ? 'Real data loaded successfully' : 'No real data found, check your form submissions',
                debug: {
                    siteId: !!siteId,
                    accessToken: !!accessToken,
                    dataSource: siteId && accessToken ? 'Netlify API' : 'Fallback'
                }
            })
        };

    } catch (error) {
        console.error('Simple dashboard error:', error);
        
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
