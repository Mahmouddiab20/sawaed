<?php
/**
 * Get Lead Details API
 * 
 * Returns detailed information about a specific lead or all leads for the admin dashboard.
 */

// Security check - simplified for dashboard access
// session_start();
// if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
//     http_response_code(401);
//     echo json_encode(['success' => false, 'message' => 'Unauthorized']);
//     exit;
// }

// Include required files
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $lead_id = $_GET['id'] ?? null;
    $get_all = $_GET['all'] ?? false;
    
    $db = get_database_connection();
    
    if ($get_all) {
        // Get all leads for dashboard
        $stmt = $db->prepare("
            SELECT 
                id, name, email, phone, message, form_type,
                ip_address, country, region, city, latitude, longitude,
                isp, timezone, user_agent, referrer,
                utm_source, utm_medium, utm_campaign,
                consent_given, created_at
            FROM leads 
            ORDER BY created_at DESC
        ");
        
        $stmt->execute();
        $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $leads,
            'total' => count($leads)
        ]);
        exit;
    }
    
    if (!$lead_id || !is_numeric($lead_id)) {
        echo json_encode(['success' => false, 'message' => 'Invalid lead ID']);
        exit;
    }
    
    $stmt = $db->prepare("
        SELECT 
            id, name, email, phone, message, form_type,
            ip_address, country, region, city, latitude, longitude,
            isp, timezone, user_agent, referrer,
            utm_source, utm_medium, utm_campaign,
            consent_given, created_at
        FROM leads 
        WHERE id = ?
    ");
    
    $stmt->execute([$lead_id]);
    $lead = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$lead) {
        echo json_encode(['success' => false, 'message' => 'Lead not found']);
        exit;
    }
    
    // Generate HTML for lead details
    $html = "
        <div class='row'>
            <div class='col-md-6'>
                <h6 class='text-primary mb-3'>معلومات العميل</h6>
                <table class='table table-sm'>
                    <tr>
                        <td><strong>الاسم:</strong></td>
                        <td>" . htmlspecialchars($lead['name']) . "</td>
                    </tr>
                    <tr>
                        <td><strong>البريد الإلكتروني:</strong></td>
                        <td><a href='mailto:" . htmlspecialchars($lead['email']) . "'>" . htmlspecialchars($lead['email']) . "</a></td>
                    </tr>
                    <tr>
                        <td><strong>الهاتف:</strong></td>
                        <td>" . htmlspecialchars($lead['phone'] ?? 'غير محدد') . "</td>
                    </tr>
                    <tr>
                        <td><strong>نوع النموذج:</strong></td>
                        <td><span class='badge bg-primary'>" . htmlspecialchars($lead['form_type']) . "</span></td>
                    </tr>
                    <tr>
                        <td><strong>الموافقة على التتبع:</strong></td>
                        <td>" . ($lead['consent_given'] ? '<i class="fas fa-check text-success"></i> نعم' : '<i class="fas fa-times text-danger"></i> لا') . "</td>
                    </tr>
                </table>
            </div>
            <div class='col-md-6'>
                <h6 class='text-primary mb-3'>الموقع الجغرافي</h6>
                <table class='table table-sm'>
                    <tr>
                        <td><strong>عنوان IP:</strong></td>
                        <td><code>" . htmlspecialchars($lead['ip_address']) . "</code></td>
                    </tr>
                    <tr>
                        <td><strong>البلد:</strong></td>
                        <td>" . htmlspecialchars($lead['country'] ?? 'غير محدد') . "</td>
                    </tr>
                    <tr>
                        <td><strong>المنطقة:</strong></td>
                        <td>" . htmlspecialchars($lead['region'] ?? 'غير محدد') . "</td>
                    </tr>
                    <tr>
                        <td><strong>المدينة:</strong></td>
                        <td>" . htmlspecialchars($lead['city'] ?? 'غير محدد') . "</td>
                    </tr>
                    <tr>
                        <td><strong>مزود الخدمة:</strong></td>
                        <td>" . htmlspecialchars($lead['isp'] ?? 'غير محدد') . "</td>
                    </tr>
                </table>
            </div>
        </div>
    ";
    
    if ($lead['message']) {
        $html .= "
            <div class='row mt-3'>
                <div class='col-12'>
                    <h6 class='text-primary mb-3'>الرسالة</h6>
                    <div class='bg-light p-3 rounded'>
                        " . nl2br(htmlspecialchars($lead['message'])) . "
                    </div>
                </div>
            </div>
        ";
    }
    
    if ($lead['utm_source'] || $lead['utm_medium'] || $lead['utm_campaign']) {
        $html .= "
            <div class='row mt-3'>
                <div class='col-12'>
                    <h6 class='text-primary mb-3'>معلومات الحملة</h6>
                    <table class='table table-sm'>
                        <tr>
                            <td><strong>مصدر الحملة:</strong></td>
                            <td>" . htmlspecialchars($lead['utm_source'] ?? 'غير محدد') . "</td>
                        </tr>
                        <tr>
                            <td><strong>وسيلة الحملة:</strong></td>
                            <td>" . htmlspecialchars($lead['utm_medium'] ?? 'غير محدد') . "</td>
                        </tr>
                        <tr>
                            <td><strong>اسم الحملة:</strong></td>
                            <td>" . htmlspecialchars($lead['utm_campaign'] ?? 'غير محدد') . "</td>
                        </tr>
                    </table>
                </div>
            </div>
        ";
    }
    
    $html .= "
        <div class='row mt-3'>
            <div class='col-12'>
                <h6 class='text-primary mb-3'>معلومات تقنية</h6>
                <table class='table table-sm'>
                    <tr>
                        <td><strong>تاريخ الإرسال:</strong></td>
                        <td>" . date('Y-m-d H:i:s', strtotime($lead['created_at'])) . "</td>
                    </tr>
                    <tr>
                        <td><strong>المتصفح:</strong></td>
                        <td><small>" . htmlspecialchars($lead['user_agent'] ?? 'غير محدد') . "</small></td>
                    </tr>
                    <tr>
                        <td><strong>الصفحة المرجعية:</strong></td>
                        <td>" . htmlspecialchars($lead['referrer'] ?? 'غير محدد') . "</td>
                    </tr>
                </table>
            </div>
        </div>
    ";
    
    echo json_encode([
        'success' => true,
        'html' => $html,
        'lead' => $lead
    ]);
    
} catch (Exception $e) {
    error_log("Get lead details error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
?>
