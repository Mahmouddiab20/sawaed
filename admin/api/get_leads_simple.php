<?php
/**
 * Simple Leads API for Dashboard
 * 
 * Returns sample lead data for the dashboard without requiring database connection.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle CORS preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Sample lead data
    $sampleLeads = [
        [
            'id' => 1,
            'name' => 'أحمد محمد',
            'email' => 'ahmed@example.com',
            'phone' => '+966501234567',
            'subject' => 'استفسار عن خدمات التسويق',
            'message' => 'أرغب في معرفة المزيد عن خدماتكم في التسويق الرقمي',
            'form_type' => 'contact',
            'consent_given' => 1,
            'created_at' => date('Y-m-d H:i:s')
        ],
        [
            'id' => 2,
            'name' => 'فاطمة العلي',
            'email' => 'fatima@example.com',
            'phone' => '+966509876543',
            'subject' => 'طلب استشارة',
            'message' => 'أحتاج استشارة حول استراتيجية التسويق لشركتي',
            'form_type' => 'consultation',
            'consent_given' => 1,
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
        ],
        [
            'id' => 3,
            'name' => 'محمد السعيد',
            'email' => 'mohammed@example.com',
            'phone' => '+966501112233',
            'subject' => 'طلب وظيفة',
            'message' => 'أرغب في التقدم لوظيفة في مجال التسويق الرقمي',
            'form_type' => 'job_application',
            'consent_given' => 0,
            'created_at' => date('Y-m-d H:i:s', strtotime('-5 days'))
        ],
        [
            'id' => 4,
            'name' => 'سارة أحمد',
            'email' => 'sara@example.com',
            'phone' => '+966502223344',
            'subject' => 'استفسار عن الأسعار',
            'message' => 'أريد معرفة أسعار خدماتكم في الإعلانات',
            'form_type' => 'contact',
            'consent_given' => 1,
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 week'))
        ],
        [
            'id' => 5,
            'name' => 'خالد النعيمي',
            'email' => 'khalid@example.com',
            'phone' => '+966503334455',
            'subject' => 'طلب عرض أسعار',
            'message' => 'أحتاج عرض أسعار لحملة إعلانية شاملة',
            'form_type' => 'contact',
            'consent_given' => 1,
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 weeks'))
        ]
    ];

    // Add some random leads for better statistics
    $additionalLeads = [];
    for ($i = 6; $i <= 15; $i++) {
        $names = ['عبدالله', 'نورا', 'عبدالرحمن', 'هند', 'سعد', 'ريم', 'عبدالعزيز', 'لينا', 'فيصل', 'مها'];
        $subjects = ['استفسار', 'طلب استشارة', 'عرض أسعار', 'طلب وظيفة', 'متابعة'];
        $formTypes = ['contact', 'consultation', 'job_application', 'newsletter'];
        
        $additionalLeads[] = [
            'id' => $i,
            'name' => $names[array_rand($names)] . ' ' . $names[array_rand($names)],
            'email' => 'client' . $i . '@example.com',
            'phone' => '+96650' . rand(1000000, 9999999),
            'subject' => $subjects[array_rand($subjects)],
            'message' => 'رسالة تجريبية رقم ' . $i,
            'form_type' => $formTypes[array_rand($formTypes)],
            'consent_given' => rand(0, 1),
            'created_at' => date('Y-m-d H:i:s', strtotime('-' . rand(1, 30) . ' days'))
        ];
    }

    $allLeads = array_merge($sampleLeads, $additionalLeads);

    echo json_encode([
        'success' => true,
        'data' => $allLeads,
        'total' => count($allLeads),
        'message' => 'Sample data loaded successfully',
        'debug' => [
            'dataSource' => 'Sample Data',
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);

} catch (Exception $e) {
    error_log("Simple leads API error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'error' => $e->getMessage()
    ]);
}
?>
