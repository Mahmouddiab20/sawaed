const fs = require('fs');
const path = require('path');

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

        // Get geolocation data (free service)
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

        // Prepare lead data
        const leadData = {
            id: Date.now().toString(),
            name: formData.name || '',
            email: formData.email || '',
            phone: formData.phone || '',
            subject: formData.subject || '',
            message: formData.message || '',
            form_type: 'contact',
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

        // Store in JSON file (for demo purposes)
        // In production, you'd want to use a proper database
        const dataFile = '/tmp/leads.json';
        let leads = [];
        
        try {
            if (fs.existsSync(dataFile)) {
                const existingData = fs.readFileSync(dataFile, 'utf8');
                leads = JSON.parse(existingData);
            }
        } catch (error) {
            console.log('No existing data file, starting fresh');
        }

        // Add new lead
        leads.push(leadData);

        // Save to file
        fs.writeFileSync(dataFile, JSON.stringify(leads, null, 2));

        // Send email notification (optional)
        await sendEmailNotification(leadData);

        return {
            statusCode: 200,
            headers: {
                'Content-Type': 'application/json; charset=utf-8',
                'Access-Control-Allow-Origin': '*'
            },
            body: JSON.stringify({
                success: true,
                message: 'تم إرسال رسالتك بنجاح! سنتواصل معك قريباً.',
                data: {
                    name: formData.name,
                    email: formData.email,
                    timestamp: new Date().toISOString(),
                    location: {
                        country: geoData.country_name || 'غير محدد',
                        city: geoData.city || 'غير محدد'
                    }
                }
            })
        };

    } catch (error) {
        console.error('Contact handler error:', error);
        
        return {
            statusCode: 500,
            headers: {
                'Content-Type': 'application/json; charset=utf-8',
                'Access-Control-Allow-Origin': '*'
            },
            body: JSON.stringify({
                success: false,
                message: 'حدث خطأ في إرسال الرسالة. يرجى المحاولة مرة أخرى.'
            })
        };
    }
};

// Email notification function
async function sendEmailNotification(leadData) {
    // You can integrate with email services like:
    // - SendGrid
    // - Mailgun
    // - AWS SES
    // - Or use Netlify's built-in email notifications
    
    const emailData = {
        to: 'sawaedflow@gmail.com',
        subject: `رسالة جديدة من موقع سواعد - ${leadData.subject || 'تواصل'}`,
        html: `
            <html dir="rtl">
            <head>
                <meta charset="UTF-8">
                <title>رسالة جديدة</title>
            </head>
            <body>
                <h2>رسالة جديدة من موقع سواعد</h2>
                <p><strong>الاسم:</strong> ${leadData.name}</p>
                <p><strong>البريد الإلكتروني:</strong> ${leadData.email}</p>
                <p><strong>الهاتف:</strong> ${leadData.phone || 'غير محدد'}</p>
                <p><strong>الموضوع:</strong> ${leadData.subject || 'غير محدد'}</p>
                <p><strong>الرسالة:</strong></p>
                <p>${leadData.message.replace(/\n/g, '<br>')}</p>
                <hr>
                <h3>معلومات الموقع</h3>
                <p><strong>البلد:</strong> ${leadData.country}</p>
                <p><strong>المدينة:</strong> ${leadData.city}</p>
                <p><strong>عنوان IP:</strong> ${leadData.ip_address}</p>
                <p><strong>مزود الخدمة:</strong> ${leadData.isp}</p>
                <hr>
                <p><small>تم الإرسال في: ${new Date().toLocaleString('ar-SA')}</small></p>
            </body>
            </html>
        `
    };

    // For now, just log the email data
    // You can implement actual email sending here
    console.log('Email notification:', emailData);
}
