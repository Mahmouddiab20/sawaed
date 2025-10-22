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
        // Parse form data
        const formData = JSON.parse(event.body);
        
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
                city: 'Unknown',
                region: 'Unknown',
                latitude: null,
                longitude: null,
                org: 'Unknown',
                timezone: 'Unknown'
            };
        }

        // Initialize Supabase
        const supabase = createClient(
            process.env.SUPABASE_URL || 'your-supabase-url',
            process.env.SUPABASE_ANON_KEY || 'your-supabase-anon-key'
        );

        // Prepare job application data
        const applicationData = {
            name: formData.name || '',
            email: formData.email || '',
            phone: formData.phone || '',
            job_title: formData.job_title || '',
            experience: formData.experience || '',
            portfolio_link: formData.portfolio_link || '',
            cover_letter: formData.cover_letter || '',
            cv_file_path: formData.cv_file_path || '',
            form_type: 'job_application',
            ip_address: clientIP,
            country: geoData.country_name || 'Unknown',
            region: geoData.region || 'Unknown',
            city: geoData.city || 'Unknown',
            latitude: geoData.latitude || null,
            longitude: geoData.longitude || null,
            isp: geoData.org || 'Unknown',
            timezone: geoData.timezone || 'Unknown',
            user_agent: event.headers['user-agent'] || 'Unknown',
            referrer: event.headers.referer || '',
            utm_source: formData.utm_source || '',
            utm_medium: formData.utm_medium || '',
            utm_campaign: formData.utm_campaign || '',
            consent_given: formData.consent || false,
            created_at: new Date().toISOString()
        };

        // Insert application into database
        const { data, error } = await supabase
            .from('leads')
            .insert([applicationData]);

        if (error) {
            console.error('Database error:', error);
            throw error;
        }

        // Send email notification
        try {
            await sendJobApplicationNotification(applicationData);
        } catch (emailError) {
            console.log('Email notification failed:', emailError);
        }

        return {
            statusCode: 200,
            headers: {
                'Content-Type': 'application/json; charset=utf-8',
                'Access-Control-Allow-Origin': '*'
            },
            body: JSON.stringify({
                success: true,
                message: 'تم إرسال طلبك بنجاح! سنراجع طلبك وسنتواصل معك قريباً.',
                data: {
                    name: formData.name,
                    email: formData.email,
                    job_title: formData.job_title,
                    timestamp: new Date().toISOString(),
                    location: {
                        country: geoData.country_name || 'غير محدد',
                        city: geoData.city || 'غير محدد'
                    }
                }
            })
        };

    } catch (error) {
        console.error('Job application handler error:', error);
        
        return {
            statusCode: 500,
            headers: {
                'Content-Type': 'application/json; charset=utf-8',
                'Access-Control-Allow-Origin': '*'
            },
            body: JSON.stringify({
                success: false,
                message: 'حدث خطأ في إرسال الطلب. يرجى المحاولة مرة أخرى.'
            })
        };
    }
};

// Email notification function for job applications
async function sendJobApplicationNotification(applicationData) {
    const emailData = {
        to: 'sawaedflow@gmail.com',
        subject: `طلب توظيف جديد - ${applicationData.job_title}`,
        html: `
            <html dir="rtl">
            <head>
                <meta charset="UTF-8">
                <title>طلب توظيف جديد</title>
            </head>
            <body>
                <h2>طلب توظيف جديد</h2>
                <p><strong>الاسم:</strong> ${applicationData.name}</p>
                <p><strong>البريد الإلكتروني:</strong> ${applicationData.email}</p>
                <p><strong>الهاتف:</strong> ${applicationData.phone || 'غير محدد'}</p>
                <p><strong>الوظيفة المتقدم لها:</strong> ${applicationData.job_title}</p>
                <p><strong>سنوات الخبرة:</strong> ${applicationData.experience || 'غير محدد'}</p>
                <p><strong>رابط المعرض:</strong> ${applicationData.portfolio_link || 'غير محدد'}</p>
                <p><strong>رسالة التغطية:</strong></p>
                <p>${applicationData.cover_letter.replace(/\n/g, '<br>')}</p>
                <hr>
                <h3>معلومات الموقع</h3>
                <p><strong>البلد:</strong> ${applicationData.country}</p>
                <p><strong>المدينة:</strong> ${applicationData.city}</p>
                <p><strong>عنوان IP:</strong> ${applicationData.ip_address}</p>
                <p><strong>مزود الخدمة:</strong> ${applicationData.isp}</p>
                <hr>
                <p><small>تم الإرسال في: ${new Date().toLocaleString('ar-SA')}</small></p>
            </body>
            </html>
        `
    };

    console.log('Job application email notification:', emailData);
}
