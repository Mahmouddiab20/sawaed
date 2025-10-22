<?php
/**
 * SQLite Database Setup Script for Sawaed Marketing Agency
 * 
 * This script helps you set up SQLite database for the lead tracking system.
 * SQLite doesn't require a separate server - it uses a file-based database.
 */

// Include SQLite database configuration
require_once __DIR__ . '/config/database_sqlite.php';

echo "<!DOCTYPE html>
<html lang='ar' dir='rtl'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>إعداد قاعدة البيانات SQLite - سواعد</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' rel='stylesheet'>
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .setup-container { background: white; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); margin: 50px auto; max-width: 800px; }
        .setup-header { background: linear-gradient(135deg, #28a745, #20c997); color: white; padding: 30px; border-radius: 15px 15px 0 0; text-align: center; }
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
            <h2><i class='fas fa-database me-2'></i>إعداد قاعدة البيانات SQLite</h2>
            <p class='mb-0'>إعداد نظام تتبع العملاء المحتملين (بدون خادم منفصل)</p>
        </div>
        <div class='setup-content'>";

try {
    // Step 1: Test SQLite connection
    echo "<div class='step'>";
    echo "<h5><i class='fas fa-plug me-2'></i>الخطوة 1: اختبار الاتصال بقاعدة البيانات SQLite</h5>";
    
    $db = get_database_connection();
    echo "<div class='alert alert-success'><i class='fas fa-check me-2'></i>تم الاتصال بقاعدة البيانات SQLite بنجاح!</div>";
    echo "<div class='alert alert-info'><i class='fas fa-info me-2'></i>موقع قاعدة البيانات: " . DB_FILE . "</div>";
    echo "</div>";
    
    // Step 2: Initialize database
    echo "<div class='step'>";
    echo "<h5><i class='fas fa-database me-2'></i>الخطوة 2: إنشاء الجداول والبيانات</h5>";
    
    $init_success = initialize_database();
    
    if ($init_success) {
        echo "<div class='alert alert-success'><i class='fas fa-check me-2'></i>تم إنشاء قاعدة البيانات والجداول بنجاح!</div>";
        
        // Check if sample data was inserted
        $stmt = $db->query("SELECT COUNT(*) as count FROM leads");
        $count = $stmt->fetch()['count'];
        echo "<div class='alert alert-info'><i class='fas fa-info me-2'></i>عدد العملاء المحتملين: $count</div>";
    } else {
        echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle me-2'></i>تم إنشاء قاعدة البيانات، لكن قد تكون هناك مشكلة في البيانات التجريبية</div>";
    }
    echo "</div>";
    
    // Step 3: Test dashboard connection
    echo "<div class='step'>";
    echo "<h5><i class='fas fa-chart-line me-2'></i>الخطوة 3: اختبار لوحة التحكم</h5>";
    
    // Test the dashboard connection
    require_once __DIR__ . '/includes/ip_geolocation.php';
    $geo = new IPGeolocation($db, get_config('geolocation_api_key'), get_config('geolocation_api_provider'));
    $recent_leads = $geo->get_recent_leads(5);
    
    echo "<div class='alert alert-success'><i class='fas fa-check me-2'></i>تم اختبار لوحة التحكم بنجاح! تم العثور على " . count($recent_leads) . " عميل محتمل</div>";
    echo "</div>";
    
    // Success message
    echo "<div class='alert alert-success text-center'>";
    echo "<h4><i class='fas fa-trophy me-2'></i>تهانينا!</h4>";
    echo "<p>تم إعداد قاعدة البيانات SQLite بنجاح. يمكنك الآن:</p>";
    echo "<ul class='list-unstyled'>";
    echo "<li><i class='fas fa-arrow-right me-2'></i><a href='admin/dashboard.php' class='btn btn-primary'>فتح لوحة التحكم</a></li>";
    echo "<li><i class='fas fa-arrow-right me-2'></i><a href='test-lead-capture.html' class='btn btn-success'>اختبار نظام العملاء المحتملين</a></li>";
    echo "<li><i class='fas fa-arrow-right me-2'></i><a href='contact.html' class='btn btn-info'>اختبار نموذج التواصل</a></li>";
    echo "</ul>";
    echo "<div class='mt-3'>";
    echo "<h6>معلومات قاعدة البيانات:</h6>";
    echo "<p><strong>النوع:</strong> SQLite (ملف واحد)</p>";
    echo "<p><strong>الموقع:</strong> " . DB_FILE . "</p>";
    echo "<p><strong>المزايا:</strong> لا يحتاج خادم منفصل، سهل النقل، سريع</p>";
    echo "</div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='step error'>";
    echo "<h5><i class='fas fa-exclamation-triangle me-2'></i>خطأ في الإعداد</h5>";
    echo "<div class='alert alert-danger'>";
    echo "<strong>خطأ:</strong> " . htmlspecialchars($e->getMessage());
    echo "<br><br><strong>الحلول المقترحة:</strong>";
    echo "<ul>";
    echo "<li>تأكد من وجود مجلد database/</li>";
    echo "<li>تحقق من صلاحيات الكتابة</li>";
    echo "<li>تأكد من دعم SQLite في PHP</li>";
    echo "</ul>";
    echo "</div>";
    echo "</div>";
}

echo "</div></div></div></body></html>";
?>
