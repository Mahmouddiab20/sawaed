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
        // Simple authentication check - make it more flexible
        const authHeader = event.headers.authorization || event.headers.Authorization;
        const adminToken = process.env.ADMIN_TOKEN || 'sawaed-admin-2024';
        
        // Check if authorization header exists and matches
        if (!authHeader) {
            console.log('No authorization header found');
            return {
                statusCode: 401,
                headers: {
                    'Content-Type': 'application/json; charset=utf-8',
                    'Access-Control-Allow-Origin': '*'
                },
                body: JSON.stringify({ 
                    success: false, 
                    message: 'No authorization header provided',
                    debug: {
                        expected: `Bearer ${adminToken}`,
                        received: authHeader
                    }
                })
            };
        }
        
        // Check if the token matches (with or without Bearer prefix)
        const token = authHeader.replace('Bearer ', '');
        if (token !== adminToken) {
            console.log('Token mismatch:', { expected: adminToken, received: token });
            return {
                statusCode: 401,
                headers: {
                    'Content-Type': 'application/json; charset=utf-8',
                    'Access-Control-Allow-Origin': '*'
                },
                body: JSON.stringify({ 
                    success: false, 
                    message: 'Invalid authorization token',
                    debug: {
                        expected: adminToken,
                        received: token
                    }
                })
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
                // First, get all forms to find the right form names
                const formsResponse = await fetch(`https://api.netlify.com/api/v1/sites/${siteId}/forms`, {
                    method: 'GET',
                    headers: {
                        'Authorization': `Bearer ${accessToken}`,
                        'Content-Type': 'application/json'
                    }
                });

                let allSubmissions = [];

                if (formsResponse.ok) {
                    const forms = await formsResponse.json();
                    console.log('Available forms:', forms.map(f => f.name));
                    console.log('Forms details:', forms);
                    
                    if (forms.length === 0) {
                        console.log('No forms found on the site');
                        throw new Error('No forms found on the site');
                    }
                    
                    // Get submissions from all forms
                    for (const form of forms) {
                        try {
                            const submissionsResponse = await fetch(`https://api.netlify.com/api/v1/sites/${siteId}/forms/${form.name}/submissions`, {
                                method: 'GET',
                                headers: {
                                    'Authorization': `Bearer ${accessToken}`,
                                    'Content-Type': 'application/json'
                                }
                            });

                            if (submissionsResponse.ok) {
                                const submissions = await submissionsResponse.json();
                                console.log(`Found ${submissions.length} submissions in form: ${form.name}`);
                                
                                // Add form type to each submission
                                submissions.forEach(submission => {
                                    submission.form_type = form.name;
                                });
                                
                                allSubmissions = allSubmissions.concat(submissions);
                            }
                        } catch (formError) {
                            console.error(`Error fetching submissions from form ${form.name}:`, formError);
                        }
                    }
                }

                if (allSubmissions.length > 0) {
                    console.log(`Total real submissions found: ${allSubmissions.length}`);
                    
                    // Transform real data with more comprehensive mapping
                    realData = allSubmissions.map((submission, index) => ({
                        id: submission.id || index + 1,
                        name: submission.data.name || submission.data.full_name || 'غير محدد',
                        email: submission.data.email || 'غير محدد',
                        phone: submission.data.phone || submission.data.telephone || 'غير محدد',
                        subject: submission.data.subject || submission.data.topic || 'غير محدد',
                        message: submission.data.message || submission.data.comments || 'غير محدد',
                        company: submission.data.company || submission.data.organization || 'غير محدد',
                        service: submission.data.service || submission.data.interest || 'غير محدد',
                        form_type: submission.form_type || 'contact',
                        created_at: submission.created_at || new Date().toISOString(),
                        ip_address: submission.ip || 'غير محدد',
                        user_agent: submission.user_agent || 'غير محدد'
                    }));
                } else {
                    console.log('No real submissions found, using sample data');
                    // Don't throw error, just use sample data
                    realData = [
                        {
                            id: 1,
                            name: 'لا توجد بيانات حقيقية',
                            email: 'no-data@example.com',
                            phone: 'غير متوفر',
                            subject: 'لم يتم العثور على بيانات حقيقية',
                            message: 'يرجى التأكد من وجود نماذج مرسلة في الموقع',
                            company: 'غير محدد',
                            service: 'غير محدد',
                            form_type: 'no_data',
                            created_at: new Date().toISOString()
                        }
                    ];
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
                        company: 'غير محدد',
                        service: 'غير محدد',
                        form_type: 'error',
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
