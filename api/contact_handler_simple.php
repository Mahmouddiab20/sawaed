<?php
/**
 * Simple Contact Form Handler for Testing
 * 
 * This is a simplified version that works with SQLite
 */

// Set content type
header('Content-Type: application/json; charset=utf-8');

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Include SQLite database configuration
    require_once __DIR__ . '/../config/database_sqlite.php';
    
    // Get database connection
    $db = get_database_connection();
    
    // Get form data
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    $consent = isset($_POST['consent']) ? 1 : 0;
    
    // Validate required fields
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'الاسم مطلوب';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'البريد الإلكتروني صحيح مطلوب';
    }
    
    if (empty($message)) {
        $errors[] = 'الرسالة مطلوبة';
    }
    
    if (!empty($errors)) {
        echo json_encode([
            'success' => false,
            'message' => 'يرجى تصحيح الأخطاء التالية:',
            'errors' => $errors
        ]);
        exit;
    }
    
    // Get client IP
    $client_ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    
    // Get user agent
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    // Insert lead into database
    $stmt = $db->prepare("
        INSERT INTO leads (
            name, email, phone, message, form_type, 
            ip_address, user_agent, consent_given, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))
    ");
    
    $result = $stmt->execute([
        $name,
        $email,
        $phone,
        $message,
        'contact',
        $client_ip,
        $user_agent,
        $consent
    ]);
    
    if ($result) {
        // Send email notification (optional)
        $email_sent = send_contact_notification([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'subject' => $subject,
            'message' => $message
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'تم إرسال رسالتك بنجاح! سنتواصل معك قريباً.',
            'data' => [
                'name' => $name,
                'email' => $email,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'حدث خطأ في حفظ البيانات'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Contact form error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ في إرسال الرسالة. يرجى المحاولة مرة أخرى.'
    ]);
}

/**
 * Send email notification to admin
 */
function send_contact_notification($form_data) {
    try {
        $to = 'sawaedflow@gmail.com';
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
