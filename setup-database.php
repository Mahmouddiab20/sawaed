<?php
/**
 * Database Setup Script for Sawaed Marketing Agency
 * 
 * This script helps you set up the database and tables for the lead tracking system.
 * Run this script once to initialize your database.
 */

// Include database configuration
require_once __DIR__ . '/config/database.php';

echo "<!DOCTYPE html>
<html lang='ar' dir='rtl'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>إعداد قاعدة البيانات - سواعد</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' rel='stylesheet'>
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .setup-container { background: white; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); margin: 50px auto; max-width: 800px; }
        .setup-header { background: linear-gradient(135deg, #007bff, #0056b3); color: white; padding: 30px; border-radius: 15px 15px 0 0; text-align: center; }
        .setup-content { padding: 30px; }
        .step { background: #f8f9fa; border-radius: 10px; padding: 20px; margin-bottom: 20px; }
        .step.success { background: #d4edda; border: 1px solid #c3e6cb; }
        .step.error { background: #f8d7da; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
<div class='container'>
    <div class='setup-container'>
        <div class='setup-header'>
            <h2><i class='fas fa-database me-2'></i>إعداد قاعدة البيانات</h2>
            <p class='mb-0'>إعداد نظام تتبع العملاء المحتملين</p>
        </div>
        <div class='setup-content'>";

try {
    // Step 1: Test database connection
    echo "<div class='step'>";
    echo "<h5><i class='fas fa-plug me-2'></i>الخطوة 1: اختبار الاتصال بقاعدة البيانات</h5>";
    
    $dsn = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    echo "<div class='alert alert-success'><i class='fas fa-check me-2'></i>تم الاتصال بقاعدة البيانات بنجاح!</div>";
    echo "</div>";
    
    // Step 2: Create database if it doesn't exist
    echo "<div class='step'>";
    echo "<h5><i class='fas fa-database me-2'></i>الخطوة 2: إنشاء قاعدة البيانات</h5>";
    
    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE " . DB_NAME);
    echo "<div class='alert alert-success'><i class='fas fa-check me-2'></i>تم إنشاء قاعدة البيانات: " . DB_NAME . "</div>";
    echo "</div>";
    
    // Step 3: Create tables
    echo "<div class='step'>";
    echo "<h5><i class='fas fa-table me-2'></i>الخطوة 3: إنشاء الجداول</h5>";
    
    $schema_file = __DIR__ . '/database/schema.sql';
    if (!file_exists($schema_file)) {
        throw new Exception("Schema file not found: " . $schema_file);
    }
    
    $schema = file_get_contents($schema_file);
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    
    $tables_created = 0;
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            $pdo->exec($statement);
            $tables_created++;
        }
    }
    
    echo "<div class='alert alert-success'><i class='fas fa-check me-2'></i>تم إنشاء $tables_created جدول بنجاح!</div>";
    echo "</div>";
    
    // Step 4: Insert sample data
    echo "<div class='step'>";
    echo "<h5><i class='fas fa-seedling me-2'></i>الخطوة 4: إدراج بيانات تجريبية</h5>";
    
    // Check if leads table is empty
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM leads");
    $count = $stmt->fetch()['count'];
    
    if ($count == 0) {
        // Insert sample data
        $sample_data = [
            ['أحمد محمد', 'ahmed@example.com', '+966501234567', 'أريد استشارة تسويقية', 'contact', '192.168.1.1', 'Saudi Arabia', 'Riyadh', 'Riyadh', 24.7136, 46.6753, 'STC', 'Asia/Riyadh', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 1],
            ['فاطمة العلي', 'fatima@example.com', '+966502345678', 'تقدم لوظيفة مصمم جرافيك', 'job_application', '192.168.1.2', 'Saudi Arabia', 'Jeddah', 'Jeddah', 21.4858, 39.1925, 'Mobily', 'Asia/Riyadh', 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)', 1],
            ['محمد السعد', 'mohammed@example.com', '+966503456789', 'استفسار عن خدمات التسويق العقاري', 'consultation', '192.168.1.3', 'Saudi Arabia', 'Dammam', 'Dammam', 26.4207, 50.0888, 'Zain', 'Asia/Riyadh', 'Mozilla/5.0 (Android 10; Mobile; rv:68.0) Gecko/68.0 Firefox/68.0', 0]
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO leads (name, email, phone, message, form_type, ip_address, country, region, city, latitude, longitude, isp, timezone, user_agent, consent_given) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($sample_data as $data) {
            $stmt->execute($data);
        }
        
        echo "<div class='alert alert-success'><i class='fas fa-check me-2'></i>تم إدراج بيانات تجريبية للاختبار!</div>";
    } else {
        echo "<div class='alert alert-info'><i class='fas fa-info me-2'></i>قاعدة البيانات تحتوي على $count عميل محتمل بالفعل</div>";
    }
    echo "</div>";
    
    // Step 5: Test dashboard connection
    echo "<div class='step'>";
    echo "<h5><i class='fas fa-chart-line me-2'></i>الخطوة 5: اختبار لوحة التحكم</h5>";
    
    // Test the dashboard connection
    $test_db = get_database_connection();
    $geo = new IPGeolocation($test_db, get_config('geolocation_api_key'), get_config('geolocation_api_provider'));
    $recent_leads = $geo->get_recent_leads(5);
    
    echo "<div class='alert alert-success'><i class='fas fa-check me-2'></i>تم اختبار لوحة التحكم بنجاح! تم العثور على " . count($recent_leads) . " عميل محتمل</div>";
    echo "</div>";
    
    // Success message
    echo "<div class='alert alert-success text-center'>";
    echo "<h4><i class='fas fa-trophy me-2'></i>تهانينا!</h4>";
    echo "<p>تم إعداد قاعدة البيانات بنجاح. يمكنك الآن:</p>";
    echo "<ul class='list-unstyled'>";
    echo "<li><i class='fas fa-arrow-right me-2'></i><a href='admin/dashboard.php' class='btn btn-primary'>فتح لوحة التحكم</a></li>";
    echo "<li><i class='fas fa-arrow-right me-2'></i><a href='test-lead-capture.html' class='btn btn-success'>اختبار نظام العملاء المحتملين</a></li>";
    echo "<li><i class='fas fa-arrow-right me-2'></i><a href='contact.html' class='btn btn-info'>اختبار نموذج التواصل</a></li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='step error'>";
    echo "<h5><i class='fas fa-exclamation-triangle me-2'></i>خطأ في الإعداد</h5>";
    echo "<div class='alert alert-danger'>";
    echo "<strong>خطأ:</strong> " . htmlspecialchars($e->getMessage());
    echo "<br><br><strong>الحلول المقترحة:</strong>";
    echo "<ul>";
    echo "<li>تأكد من تشغيل خادم MySQL</li>";
    echo "<li>تحقق من بيانات الاتصال في ملف config/database.php</li>";
    echo "<li>تأكد من وجود صلاحيات إنشاء قواعد البيانات</li>";
    echo "</ul>";
    echo "</div>";
    echo "</div>";
}

echo "</div></div></div></body></html>";
?>
