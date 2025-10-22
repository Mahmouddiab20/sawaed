<?php
/**
 * Job Application Handler with Geolocation Tracking
 * 
 * This file handles job application form submissions and captures
 * visitor geolocation data for recruitment analytics.
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

// CORS headers
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
        'job_title' => sanitize_input($_POST['job_title'] ?? ''),
        'experience' => sanitize_input($_POST['experience'] ?? ''),
        'portfolio_link' => sanitize_input($_POST['portfolio_link'] ?? ''),
        'cover_letter' => sanitize_input($_POST['cover_letter'] ?? ''),
        'consent' => isset($_POST['consent']) ? (bool)$_POST['consent'] : false
    ];
    
    // Validate required fields
    $errors = [];
    
    if (empty($form_data['name'])) {
        $errors[] = 'الاسم الكامل مطلوب';
    }
    
    if (empty($form_data['email']) || !validate_email($form_data['email'])) {
        $errors[] = 'البريد الإلكتروني صحيح مطلوب';
    }
    
    if (empty($form_data['phone'])) {
        $errors[] = 'رقم الهاتف مطلوب';
    }
    
    if (empty($form_data['job_title'])) {
        $errors[] = 'الوظيفة المتقدم لها مطلوبة';
    }
    
    // Validate file upload
    $cv_file = null;
    if (isset($_FILES['cv_file']) && $_FILES['cv_file']['error'] === UPLOAD_ERR_OK) {
        $cv_file = $_FILES['cv_file'];
        
        // Check file size (5MB limit)
        if ($cv_file['size'] > 5 * 1024 * 1024) {
            $errors[] = 'حجم الملف يجب أن يكون أقل من 5 ميجابايت';
        }
        
        // Check file type
        $allowed_types = ['application/pdf'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $cv_file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime_type, $allowed_types)) {
            $errors[] = 'يجب أن يكون الملف من نوع PDF';
        }
    } else {
        $errors[] = 'رفع السيرة الذاتية مطلوب';
    }
    
    // Check consent if required
    if (get_config('require_consent') && !$form_data['consent']) {
        $errors[] = 'يجب الموافقة على شروط الخصوصية وسياسة التوظيف';
    }
    
    if (!empty($errors)) {
        echo json_encode([
            'success' => false,
            'message' => 'يرجى تصحيح الأخطاء التالية:',
            'errors' => $errors
        ]);
        exit;
    }
    
    // Handle file upload
    $upload_success = handle_cv_upload($cv_file, $form_data['name'], $form_data['job_title']);
    
    if (!$upload_success) {
        echo json_encode([
            'success' => false,
            'message' => 'فشل في رفع الملف. يرجى المحاولة مرة أخرى.'
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
    
    // Add file path to form data
    $form_data['cv_file_path'] = $upload_success;
    
    // Capture lead with geolocation
    $capture_success = $geo->capture_lead($form_data, 'job_application');
    
    if (!$capture_success) {
        error_log("Failed to capture job application for email: " . $form_data['email']);
    }
    
    // Send email notification
    $email_sent = send_job_application_notification($form_data, $upload_success);
    
    // Prepare response
    $response = [
        'success' => true,
        'message' => 'تم إرسال طلبك بنجاح! سنراجع طلبك وسنتواصل معك قريباً.',
        'data' => [
            'name' => $form_data['name'],
            'email' => $form_data['email'],
            'job_title' => $form_data['job_title'],
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
    error_log("Job application error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ في إرسال الطلب. يرجى المحاولة مرة أخرى.'
    ]);
}

/**
 * Handle CV file upload
 * 
 * @param array $file Uploaded file
 * @param string $name Applicant name
 * @param string $job_title Job title
 * @return string|false File path or false on failure
 */
function handle_cv_upload($file, $name, $job_title) {
    try {
        // Create uploads directory if it doesn't exist
        $upload_dir = __DIR__ . '/../uploads/cv/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Generate unique filename
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $safe_name = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $name);
        $safe_job = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $job_title);
        $filename = $safe_name . '_' . $safe_job . '_' . time() . '.' . $file_extension;
        
        $file_path = $upload_dir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            return $file_path;
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log("File upload error: " . $e->getMessage());
        return false;
    }
}

/**
 * Send job application notification email
 * 
 * @param array $form_data Form data
 * @param string $cv_file_path Path to uploaded CV
 * @return bool Success status
 */
function send_job_application_notification($form_data, $cv_file_path) {
    try {
        $to = 'sawaedflow@gmail.com'; // Your email
        $subject = 'طلب توظيف جديد - ' . $form_data['job_title'];
        
        $message = "
        <html dir='rtl'>
        <head>
            <meta charset='UTF-8'>
            <title>طلب توظيف جديد</title>
        </head>
        <body>
            <h2>طلب توظيف جديد</h2>
            <p><strong>الاسم:</strong> " . htmlspecialchars($form_data['name']) . "</p>
            <p><strong>البريد الإلكتروني:</strong> " . htmlspecialchars($form_data['email']) . "</p>
            <p><strong>الهاتف:</strong> " . htmlspecialchars($form_data['phone']) . "</p>
            <p><strong>الوظيفة المتقدم لها:</strong> " . htmlspecialchars($form_data['job_title']) . "</p>
            <p><strong>سنوات الخبرة:</strong> " . htmlspecialchars($form_data['experience']) . "</p>
            <p><strong>رابط المعرض:</strong> " . htmlspecialchars($form_data['portfolio_link']) . "</p>
            <p><strong>رسالة التغطية:</strong></p>
            <p>" . nl2br(htmlspecialchars($form_data['cover_letter'])) . "</p>
            <hr>
            <p><small>تم الإرسال في: " . date('Y-m-d H:i:s') . "</small></p>
            <p><small>ملف السيرة الذاتية: " . basename($cv_file_path) . "</small></p>
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
        error_log("Job application email failed: " . $e->getMessage());
        return false;
    }
}
?>
