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

        // Return sample data for now
        const sampleData = [
            {
                id: 1,
                name: 'أحمد محمد',
                email: 'ahmed@example.com',
                phone: '+966501234567',
                subject: 'استفسار عن خدمات التسويق',
                message: 'أريد معرفة المزيد عن خدماتكم في التسويق الرقمي',
                created_at: new Date().toISOString()
            },
            {
                id: 2,
                name: 'فاطمة علي',
                email: 'fatima@example.com',
                phone: '+966507654321',
                subject: 'طلب عرض أسعار',
                message: 'أحتاج عرض أسعار لخدمات إدارة وسائل التواصل الاجتماعي',
                created_at: new Date(Date.now() - 86400000).toISOString()
            },
            {
                id: 3,
                name: 'محمد السعيد',
                email: 'mohammed@example.com',
                phone: '+966509876543',
                subject: 'استشارة تسويقية',
                message: 'أريد استشارة حول استراتيجية التسويق لمتجر إلكتروني',
                created_at: new Date(Date.now() - 172800000).toISOString()
            }
        ];

        return {
            statusCode: 200,
            headers: {
                'Content-Type': 'application/json; charset=utf-8',
                'Access-Control-Allow-Origin': '*'
            },
            body: JSON.stringify({
                success: true,
                data: sampleData,
                total: sampleData.length,
                message: 'Sample data loaded successfully'
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
