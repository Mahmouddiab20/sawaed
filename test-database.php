<?php
/**
 * Simple Database Test
 * 
 * This script tests if the SQLite database is working properly
 */

// Include SQLite database configuration
require_once __DIR__ . '/config/database_sqlite.php';

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html lang='ar' dir='rtl'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>اختبار قاعدة البيانات</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .test-container { background: white; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); margin: 50px auto; max-width: 800px; }
        .test-header { background: linear-gradient(135deg, #007bff, #0056b3); color: white; padding: 30px; border-radius: 15px 15px 0 0; text-align: center; }
        .test-content { padding: 30px; }
    </style>
</head>
<body>
<div class='container'>
    <div class='test-container'>
        <div class='test-header'>
            <h2><i class='fas fa-database'></i> اختبار قاعدة البيانات</h2>
        </div>
        <div class='test-content'>";

try {
    echo "<h5>1. اختبار الاتصال بقاعدة البيانات</h5>";
    $db = get_database_connection();
    echo "<div class='alert alert-success'>✅ تم الاتصال بقاعدة البيانات بنجاح!</div>";
    
    echo "<h5>2. اختبار إنشاء الجداول</h5>";
    $init_success = initialize_database();
    if ($init_success) {
        echo "<div class='alert alert-success'>✅ تم إنشاء الجداول بنجاح!</div>";
    } else {
        echo "<div class='alert alert-warning'>⚠️ تم إنشاء الجداول، لكن قد تكون هناك مشكلة</div>";
    }
    
    echo "<h5>3. اختبار إدراج بيانات تجريبية</h5>";
    $stmt = $db->prepare("
        INSERT OR IGNORE INTO leads (
            name, email, phone, message, form_type, 
            ip_address, user_agent, consent_given, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))
    ");
    
    $test_data = [
        'محمود دياب', 
        'mahmouddiab176@gmail.com', 
        '01014169261', 
        'رسالة تجريبية من الاختبار', 
        'contact', 
        '127.0.0.1', 
        'Test Browser', 
        1
    ];
    
    $result = $stmt->execute($test_data);
    if ($result) {
        echo "<div class='alert alert-success'>✅ تم إدراج البيانات التجريبية بنجاح!</div>";
    } else {
        echo "<div class='alert alert-warning'>⚠️ لم يتم إدراج البيانات التجريبية</div>";
    }
    
    echo "<h5>4. اختبار قراءة البيانات</h5>";
    $stmt = $db->query("SELECT COUNT(*) as count FROM leads");
    $count = $stmt->fetch()['count'];
    echo "<div class='alert alert-info'>📊 عدد العملاء المحتملين: $count</div>";
    
    echo "<h5>5. عرض آخر 5 عملاء محتملين</h5>";
    $stmt = $db->query("SELECT name, email, created_at FROM leads ORDER BY created_at DESC LIMIT 5");
    $leads = $stmt->fetchAll();
    
    if (!empty($leads)) {
        echo "<table class='table table-striped'>";
        echo "<thead><tr><th>الاسم</th><th>البريد الإلكتروني</th><th>التاريخ</th></tr></thead>";
        echo "<tbody>";
        foreach ($leads as $lead) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($lead['name']) . "</td>";
            echo "<td>" . htmlspecialchars($lead['email']) . "</td>";
            echo "<td>" . $lead['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<div class='alert alert-warning'>⚠️ لا توجد بيانات في قاعدة البيانات</div>";
    }
    
    echo "<div class='alert alert-success text-center mt-4'>";
    echo "<h4>🎉 تهانينا!</h4>";
    echo "<p>قاعدة البيانات تعمل بشكل صحيح. يمكنك الآن:</p>";
    echo "<a href='test-lead-capture.html' class='btn btn-primary me-2'>اختبار نموذج التواصل</a>";
    echo "<a href='admin/dashboard.php' class='btn btn-success'>فتح لوحة التحكم</a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h5>❌ خطأ في قاعدة البيانات</h5>";
    echo "<p><strong>الخطأ:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>الحل:</strong> تأكد من تشغيل XAMPP و Apache</p>";
    echo "</div>";
}

echo "</div></div></div></body></html>";
?>
