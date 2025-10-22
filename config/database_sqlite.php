<?php
/**
 * SQLite Database Configuration for Sawaed Marketing Agency
 * 
 * This is an alternative to MySQL that doesn't require a separate server.
 * SQLite stores the database in a single file.
 */

// SQLite database file path
define('DB_FILE', __DIR__ . '/../database/sawaed_leads.db');

// Privacy and compliance settings
define('DATA_RETENTION_DAYS', 365);
define('REQUIRE_CONSENT', true);
define('COOKIE_CONSENT_DURATION', 365);

// Security settings
define('ENABLE_IP_LOGGING', true);
define('ENABLE_GEOLOCATION', true);
define('ENABLE_ANALYTICS', true);

// Rate limiting (requests per IP per hour)
define('RATE_LIMIT_REQUESTS', 100);
define('RATE_LIMIT_WINDOW', 3600);

/**
 * Get SQLite database connection
 * 
 * @return PDO Database connection
 * @throws PDOException If connection fails
 */
function get_database_connection() {
    try {
        // Create database directory if it doesn't exist
        $db_dir = dirname(DB_FILE);
        if (!is_dir($db_dir)) {
            mkdir($db_dir, 0755, true);
        }
        
        $dsn = "sqlite:" . DB_FILE;
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $pdo = new PDO($dsn, null, null, $options);
        
        // Enable foreign keys
        $pdo->exec("PRAGMA foreign_keys = ON");
        
        return $pdo;
        
    } catch (PDOException $e) {
        error_log("SQLite connection failed: " . $e->getMessage());
        throw new PDOException("Database connection failed");
    }
}

/**
 * Initialize SQLite database with schema
 * 
 * @return bool Success status
 */
function initialize_database() {
    try {
        $db = get_database_connection();
        
        // Create leads table
        $db->exec("
            CREATE TABLE IF NOT EXISTS leads (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL,
                phone TEXT,
                message TEXT,
                form_type TEXT DEFAULT 'contact',
                ip_address TEXT NOT NULL,
                country TEXT,
                region TEXT,
                city TEXT,
                latitude REAL,
                longitude REAL,
                isp TEXT,
                timezone TEXT,
                user_agent TEXT,
                referrer TEXT,
                utm_source TEXT,
                utm_medium TEXT,
                utm_campaign TEXT,
                consent_given INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Create page_visits table
        $db->exec("
            CREATE TABLE IF NOT EXISTS page_visits (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                ip_address TEXT NOT NULL,
                page_url TEXT NOT NULL,
                page_title TEXT,
                referrer TEXT,
                user_agent TEXT,
                country TEXT,
                region TEXT,
                city TEXT,
                utm_source TEXT,
                utm_medium TEXT,
                utm_campaign TEXT,
                session_id TEXT,
                visit_duration INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Create consent_records table
        $db->exec("
            CREATE TABLE IF NOT EXISTS consent_records (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                ip_address TEXT NOT NULL,
                consent_type TEXT NOT NULL,
                consent_given INTEGER NOT NULL,
                consent_timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
                user_agent TEXT,
                country TEXT
            )
        ");
        
        // Create analytics_summary table
        $db->exec("
            CREATE TABLE IF NOT EXISTS analytics_summary (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                date TEXT NOT NULL UNIQUE,
                total_visitors INTEGER DEFAULT 0,
                total_leads INTEGER DEFAULT 0,
                contact_leads INTEGER DEFAULT 0,
                job_leads INTEGER DEFAULT 0,
                newsletter_leads INTEGER DEFAULT 0,
                consultation_leads INTEGER DEFAULT 0,
                top_country TEXT,
                top_region TEXT,
                top_city TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        // Insert sample data if table is empty
        $stmt = $db->query("SELECT COUNT(*) as count FROM leads");
        $count = $stmt->fetch()['count'];
        
        if ($count == 0) {
            $sample_data = [
                ['أحمد محمد', 'ahmed@example.com', '+966501234567', 'أريد استشارة تسويقية', 'contact', '192.168.1.1', 'Saudi Arabia', 'Riyadh', 'Riyadh', 24.7136, 46.6753, 'STC', 'Asia/Riyadh', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 1],
                ['فاطمة العلي', 'fatima@example.com', '+966502345678', 'تقدم لوظيفة مصمم جرافيك', 'job_application', '192.168.1.2', 'Saudi Arabia', 'Jeddah', 'Jeddah', 21.4858, 39.1925, 'Mobily', 'Asia/Riyadh', 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)', 1],
                ['محمد السعد', 'mohammed@example.com', '+966503456789', 'استفسار عن خدمات التسويق العقاري', 'consultation', '192.168.1.3', 'Saudi Arabia', 'Dammam', 'Dammam', 26.4207, 50.0888, 'Zain', 'Asia/Riyadh', 'Mozilla/5.0 (Android 10; Mobile; rv:68.0) Gecko/68.0 Firefox/68.0', 0]
            ];
            
            $stmt = $db->prepare("
                INSERT INTO leads (name, email, phone, message, form_type, ip_address, country, region, city, latitude, longitude, isp, timezone, user_agent, consent_given) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($sample_data as $data) {
                $stmt->execute($data);
            }
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("SQLite initialization failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Get configuration value
 * 
 * @param string $key Configuration key
 * @param mixed $default Default value if key not found
 * @return mixed Configuration value
 */
function get_config($key, $default = null) {
    $config = [
        'db_file' => DB_FILE,
        'data_retention_days' => DATA_RETENTION_DAYS,
        'require_consent' => REQUIRE_CONSENT,
        'cookie_consent_duration' => COOKIE_CONSENT_DURATION,
        'enable_ip_logging' => ENABLE_IP_LOGGING,
        'enable_geolocation' => ENABLE_GEOLOCATION,
        'enable_analytics' => ENABLE_ANALYTICS,
        'rate_limit_requests' => RATE_LIMIT_REQUESTS,
        'rate_limit_window' => RATE_LIMIT_WINDOW,
        'geolocation_api_provider' => 'ipapi',
        'geolocation_api_key' => ''
    ];
    
    return isset($config[$key]) ? $config[$key] : $default;
}

/**
 * Check rate limit for IP address
 * 
 * @param string $ip IP address to check
 * @return bool True if within rate limit
 */
function check_rate_limit($ip) {
    try {
        $db = get_database_connection();
        
        $stmt = $db->prepare("
            SELECT COUNT(*) as request_count 
            FROM leads 
            WHERE ip_address = ? 
            AND created_at > datetime('now', '-' || ? || ' seconds')
        ");
        
        $stmt->execute([$ip, RATE_LIMIT_WINDOW]);
        $result = $stmt->fetch();
        
        return $result['request_count'] < RATE_LIMIT_REQUESTS;
        
    } catch (PDOException $e) {
        error_log("Rate limit check failed: " . $e->getMessage());
        return true;
    }
}

/**
 * Log security event
 * 
 * @param string $event Event description
 * @param string $ip IP address
 * @param array $data Additional data
 */
function log_security_event($event, $ip, $data = []) {
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'event' => $event,
        'ip' => $ip,
        'data' => $data,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    ];
    
    error_log("Security Event: " . json_encode($log_entry));
}

/**
 * Sanitize input data
 * 
 * @param mixed $data Data to sanitize
 * @return mixed Sanitized data
 */
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    
    if (is_string($data)) {
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    
    return $data;
}

/**
 * Validate email address
 * 
 * @param string $email Email to validate
 * @return bool True if valid
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (basic validation)
 * 
 * @param string $phone Phone to validate
 * @return bool True if valid
 */
function validate_phone($phone) {
    $digits = preg_replace('/\D/', '', $phone);
    return strlen($digits) >= 7 && strlen($digits) <= 15;
}

/**
 * Get client IP address with security checks
 * 
 * @return string|null Client IP address
 */
function get_client_ip_secure() {
    $ip = null;
    
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    }
    elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    if ($ip && filter_var($ip, FILTER_VALIDATE_IP)) {
        return $ip;
    }
    
    return null;
}
?>
