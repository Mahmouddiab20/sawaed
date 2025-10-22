<?php
/**
 * Contact Form Handler with Geolocation Tracking
 * 
 * This file handles contact form submissions and captures
 * visitor geolocation data for marketing analytics.
 */

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/ip_geolocation.php';

// Start session for consent tracking
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set content type
header('Content-Type: application/json; charset=utf-8');

// CORS headers (adjust for your domain)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Get client IP
    $client_ip = get_client_ip_secure();
    
    // Check rate limiting
    if ($client_ip && !check_rate_limit($client_ip)) {
        log_security_event('Rate limit exceeded', $client_ip);
        echo json_encode([
            'success' => false, 
            'message' => 'Too many requests. Please try again later.'
        ]);
        exit;
    }
    
    // Validate and sanitize input
    $form_data = [
        'name' => sanitize_input($_POST['name'] ?? ''),
        'email' => sanitize_input($_POST['email'] ?? ''),
        'phone' => sanitize_input($_POST['phone'] ?? ''),
        'subject' => sanitize_input($_POST['subject'] ?? ''),
        'message' => sanitize_input($_POST['message'] ?? ''),
        'consent' => isset($_POST['consent']) ? (bool)$_POST['consent'] : false
    ];
    
    // Validate required fields
    $errors = [];
    
    if (empty($form_data['name'])) {
        $errors[] = 'الاسم مطلوب';
    }
    
    if (empty($form_data['email']) || !validate_email($form_data['email'])) {
        $errors[] = 'البريد الإلكتروني صحيح مطلوب';
    }
    
    if (empty($form_data['message'])) {
        $errors[] = 'الرسالة مطلوبة';
    }
    
    // Check consent if required
    if (get_config('require_consent') && !$form_data['consent']) {
        $errors[] = 'يجب الموافقة على سياسة الخصوصية';
    }
    
    if (!empty($errors)) {
        echo json_encode([
            'success' => false,
            'message' => 'يرجى تصحيح الأخطاء التالية:',
            'errors' => $errors
        ]);
        exit;
    }
    
    // Get database connection
    $db = get_database_connection();
    
    // Initialize geolocation system
    $geo = new IPGeolocation($db, get_config('geolocation_api_key'), get_config('geolocation_api_provider'));
    
    // Set consent if given
    if ($form_data['consent']) {
        $geo->set_consent(true);
    }
    
    // Capture lead with geolocation
    $capture_success = $geo->capture_lead($form_data, 'contact');
    
    if (!$capture_success) {
        error_log("Failed to capture lead for email: " . $form_data['email']);
    }
    
    // Send email notification (optional)
    $email_sent = send_contact_notification($form_data);
    
    // Prepare response
    $response = [
        'success' => true,
        'message' => 'تم إرسال رسالتك بنجاح! سنتواصل معك قريباً.',
        'data' => [
            'name' => $form_data['name'],
            'email' => $form_data['email'],
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ];
    
    // Add geolocation info if consent given
    if ($form_data['consent'] && $capture_success) {
        $geo_data = $geo->get_geolocation($client_ip);
        if ($geo_data) {
            $response['data']['location'] = [
                'country' => $geo_data['country'] ?? 'غير محدد',
                'city' => $geo_data['city'] ?? 'غير محدد'
            ];
        }
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Contact form error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ في إرسال الرسالة. يرجى المحاولة مرة أخرى.'
    ]);
}

/**
 * Send email notification to admin
 * 
 * @param array $form_data Form data
 * @return bool Success status
 */
function send_contact_notification($form_data) {
    try {
        $to = 'sawaedflow@gmail.com'; // Your email
        $subject = 'رسالة جديدة من موقع سواعد - ' . $form_data['subject'];
        
        $message = "
        <html dir='rtl'>
        <head>
            <meta charset='UTF-8'>
            <title>رسالة جديدة</title>
        </head>
        <body>
            <h2>رسالة جديدة من موقع سواعد</h2>
            <p><strong>الاسم:</strong> " . htmlspecialchars($form_data['name']) . "</p>
            <p><strong>البريد الإلكتروني:</strong> " . htmlspecialchars($form_data['email']) . "</p>
            <p><strong>الهاتف:</strong> " . htmlspecialchars($form_data['phone']) . "</p>
            <p><strong>الموضوع:</strong> " . htmlspecialchars($form_data['subject']) . "</p>
            <p><strong>الرسالة:</strong></p>
            <p>" . nl2br(htmlspecialchars($form_data['message'])) . "</p>
            <hr>
            <p><small>تم الإرسال في: " . date('Y-m-d H:i:s') . "</small></p>
        </body>
        </html>
        ";
        
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: Sawaed Website <noreply@sawaed.com>',
            'Reply-To: ' . $form_data['email'],
            'X-Mailer: PHP/' . phpversion()
        ];
        
        return mail($to, $subject, $message, implode("\r\n", $headers));
        
    } catch (Exception $e) {
        error_log("Email notification failed: " . $e->getMessage());
        return false;
    }
}
?>
