<?php
/**
 * Admin Dashboard for Sawaed Marketing Agency
 * 
 * This dashboard provides analytics and lead management for the marketing agency.
 * It displays visitor geolocation data, lead analytics, and management tools.
 */

// Security check - add authentication here
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Include required files
require_once __DIR__ . '/../config/database_sqlite.php';
require_once __DIR__ . '/../includes/ip_geolocation.php';

// Get database connection
$db = get_database_connection();
$geo = new IPGeolocation($db, get_config('geolocation_api_key'), get_config('geolocation_api_provider'));

// Get analytics data
$analytics = $geo->get_analytics(30); // Last 30 days
$recent_leads = $geo->get_recent_leads(50);

// If no real leads found, show message
if (empty($recent_leads)) {
    $recent_leads = [];
}

// Calculate summary statistics
$total_leads = count($recent_leads);
$contact_leads = count(array_filter($recent_leads, function($lead) { return $lead['form_type'] === 'contact'; }));
$job_leads = count(array_filter($recent_leads, function($lead) { return $lead['form_type'] === 'job_application'; }));
$consented_leads = count(array_filter($recent_leads, function($lead) { return $lead['consent_given'] == 1; }));

// Get geographic distribution
$geo_distribution = [];
foreach ($recent_leads as $lead) {
    if ($lead['country']) {
        $country = $lead['country'];
        if (!isset($geo_distribution[$country])) {
            $geo_distribution[$country] = 0;
        }
        $geo_distribution[$country]++;
    }
}
arsort($geo_distribution);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - سواعد للتسويق الرقمي</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        .dashboard-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #007bff;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            margin-top: 5px;
        }
        
        .lead-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .lead-table th {
            background: #f8f9fa;
            border: none;
            font-weight: 600;
            color: #495057;
        }
        
        .lead-table td {
            border: none;
            border-bottom: 1px solid #f1f3f4;
            vertical-align: middle;
        }
        
        .badge-custom {
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        
        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-chart-line me-2"></i>
                لوحة تحكم سواعد
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>
                    تسجيل الخروج
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="dashboard-card">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="mb-2">مرحباً بك في لوحة التحكم</h2>
                            <p class="mb-0">إحصائيات وتتبع الزوار والعملاء المحتملين</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <i class="fas fa-globe-americas" style="font-size: 4rem; opacity: 0.3;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_leads; ?></div>
                    <div class="stat-label">إجمالي العملاء المحتملين</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $contact_leads; ?></div>
                    <div class="stat-label">استفسارات التواصل</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $job_leads; ?></div>
                    <div class="stat-label">طلبات التوظيف</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $consented_leads; ?></div>
                    <div class="stat-label">موافقة على التتبع</div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
            <div class="col-lg-6 mb-3">
                <div class="chart-container">
                    <h5 class="mb-3">التوزيع الجغرافي للعملاء</h5>
                    <canvas id="geoChart" width="400" height="200"></canvas>
                </div>
            </div>
            <div class="col-lg-6 mb-3">
                <div class="chart-container">
                    <h5 class="mb-3">أنواع النماذج</h5>
                    <canvas id="formTypeChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent Leads Table -->
        <div class="row">
            <div class="col-12">
                <div class="lead-table">
                    <div class="p-3 border-bottom">
                        <h5 class="mb-0">العملاء المحتملين الأخيرين</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>الاسم</th>
                                    <th>البريد الإلكتروني</th>
                                    <th>نوع النموذج</th>
                                    <th>البلد</th>
                                    <th>المدينة</th>
                                    <th>الموافقة</th>
                                    <th>التاريخ</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_leads)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="fas fa-inbox fa-3x mb-3"></i>
                                            <h5>لا توجد عملاء محتملين بعد</h5>
                                            <p>لم يتم إرسال أي نماذج تواصل حتى الآن.</p>
                                            <div class="mt-3">
                                                <a href="../contact.html" class="btn btn-primary" target="_blank">
                                                    <i class="fas fa-external-link-alt me-2"></i>
                                                    اختبار نموذج التواصل
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($recent_leads as $lead): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar bg-primary text-white rounded-circle me-2" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; font-size: 0.8rem;">
                                                <?php echo strtoupper(substr($lead['name'], 0, 2)); ?>
                                            </div>
                                            <?php echo htmlspecialchars($lead['name']); ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($lead['email']); ?></td>
                                    <td>
                                        <?php
                                        $form_type_labels = [
                                            'contact' => 'تواصل',
                                            'job_application' => 'توظيف',
                                            'newsletter' => 'نشرة',
                                            'consultation' => 'استشارة'
                                        ];
                                        $label = $form_type_labels[$lead['form_type']] ?? $lead['form_type'];
                                        $badge_class = $lead['form_type'] === 'contact' ? 'bg-primary' : 'bg-success';
                                        ?>
                                        <span class="badge badge-custom <?php echo $badge_class; ?>"><?php echo $label; ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($lead['country'] ?? 'غير محدد'); ?></td>
                                    <td><?php echo htmlspecialchars($lead['city'] ?? 'غير محدد'); ?></td>
                                    <td>
                                        <?php if ($lead['consent_given']): ?>
                                            <i class="fas fa-check-circle text-success"></i>
                                        <?php else: ?>
                                            <i class="fas fa-times-circle text-danger"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($lead['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary btn-sm" onclick="viewLead(<?php echo $lead['id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-outline-danger btn-sm" onclick="deleteLead(<?php echo $lead['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lead Details Modal -->
    <div class="modal fade" id="leadModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تفاصيل العميل المحتمل</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="leadDetails">
                    <!-- Lead details will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Geographic distribution chart
        const geoCtx = document.getElementById('geoChart').getContext('2d');
        const geoData = <?php echo json_encode(array_slice($geo_distribution, 0, 5)); ?>;
        
        new Chart(geoCtx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(geoData),
                datasets: [{
                    data: Object.values(geoData),
                    backgroundColor: [
                        '#007bff',
                        '#28a745',
                        '#ffc107',
                        '#dc3545',
                        '#6f42c1'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Form type chart
        const formCtx = document.getElementById('formTypeChart').getContext('2d');
        const formData = {
            'تواصل': <?php echo $contact_leads; ?>,
            'توظيف': <?php echo $job_leads; ?>,
            'نشرة': <?php echo count(array_filter($recent_leads, function($lead) { return $lead['form_type'] === 'newsletter'; })); ?>,
            'استشارة': <?php echo count(array_filter($recent_leads, function($lead) { return $lead['form_type'] === 'consultation'; })); ?>
        };
        
        new Chart(formCtx, {
            type: 'bar',
            data: {
                labels: Object.keys(formData),
                datasets: [{
                    label: 'عدد النماذج',
                    data: Object.values(formData),
                    backgroundColor: '#007bff'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // View lead details
        function viewLead(leadId) {
            fetch(`api/get_lead_details.php?id=${leadId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('leadDetails').innerHTML = data.html;
                        new bootstrap.Modal(document.getElementById('leadModal')).show();
                    } else {
                        alert('خطأ في تحميل تفاصيل العميل');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('حدث خطأ في تحميل البيانات');
                });
        }

        // Delete lead
        function deleteLead(leadId) {
            if (confirm('هل أنت متأكد من حذف هذا العميل المحتمل؟')) {
                fetch(`api/delete_lead.php`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({id: leadId})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('خطأ في حذف العميل');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('حدث خطأ في حذف البيانات');
                });
            }
        }

        // Auto-refresh every 5 minutes
        setInterval(function() {
            location.reload();
        }, 300000);
    </script>
</body>
</html>
