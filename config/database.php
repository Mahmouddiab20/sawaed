<?php
/**
 * Database Configuration for Sawaed Marketing Agency
 * 
 * This file contains database connection settings and configuration
 * for the lead tracking and geolocation system.
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'sawaed_leads');
define('DB_USER', 'root'); // Change to your MySQL username
define('DB_PASS', ''); // Change to your MySQL password
define('DB_CHARSET', 'utf8mb4');

// Geolocation API configuration
define('GEOLOCATION_API_PROVIDER', 'ipapi'); // ipapi, ipinfo, ipstack
define('GEOLOCATION_API_KEY', ''); // Leave empty for free services

// Privacy and compliance settings
define('DATA_RETENTION_DAYS', 365); // How long to keep lead data
define('REQUIRE_CONSENT', true); // Whether to require user consent
define('COOKIE_CONSENT_DURATION', 365); // Days to remember consent

// Security settings
define('ENABLE_IP_LOGGING', true);
define('ENABLE_GEOLOCATION', true);
define('ENABLE_ANALYTICS', true);

// Rate limiting (requests per IP per hour)
define('RATE_LIMIT_REQUESTS', 100);
define('RATE_LIMIT_WINDOW', 3600); // 1 hour in seconds

/**
 * Get database connection
 * 
 * @return PDO Database connection
 * @throws PDOException If connection fails
 */
function get_database_connection() {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
    ];
    
    try {
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        throw new PDOException("Database connection failed");
    }
}

/**
 * Check if database tables exist
 * 
 * @return bool True if all required tables exist
 */
function check_database_tables() {
    try {
        $db = get_database_connection();
        
        $required_tables = ['leads', 'page_visits', 'consent_records', 'analytics_summary'];
        
        foreach ($required_tables as $table) {
            $stmt = $db->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            
            if ($stmt->rowCount() === 0) {
                return false;
            }
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("Database table check failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Initialize database with schema
 * 
 * @return bool Success status
 */
function initialize_database() {
    try {
        $db = get_database_connection();
        
        // Read and execute schema file
        $schema_file = __DIR__ . '/../database/schema.sql';
        
        if (!file_exists($schema_file)) {
            throw new Exception("Schema file not found: " . $schema_file);
        }
        
        $schema = file_get_contents($schema_file);
        
        // Split by semicolon and execute each statement
        $statements = array_filter(array_map('trim', explode(';', $schema)));
        
        foreach ($statements as $statement) {
            if (!empty($statement) && !preg_match('/^--/', $statement)) {
                $db->exec($statement);
            }
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Database initialization failed: " . $e->getMessage());
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
        'db_host' => DB_HOST,
        'db_name' => DB_NAME,
        'db_user' => DB_USER,
        'db_pass' => DB_PASS,
        'geolocation_api_provider' => GEOLOCATION_API_PROVIDER,
        'geolocation_api_key' => GEOLOCATION_API_KEY,
        'data_retention_days' => DATA_RETENTION_DAYS,
        'require_consent' => REQUIRE_CONSENT,
        'cookie_consent_duration' => COOKIE_CONSENT_DURATION,
        'enable_ip_logging' => ENABLE_IP_LOGGING,
        'enable_geolocation' => ENABLE_GEOLOCATION,
        'enable_analytics' => ENABLE_ANALYTICS,
        'rate_limit_requests' => RATE_LIMIT_REQUESTS,
        'rate_limit_window' => RATE_LIMIT_WINDOW
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
            AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        
        $stmt->execute([$ip, RATE_LIMIT_WINDOW]);
        $result = $stmt->fetch();
        
        return $result['request_count'] < RATE_LIMIT_REQUESTS;
        
    } catch (PDOException $e) {
        error_log("Rate limit check failed: " . $e->getMessage());
        return true; // Allow request if check fails
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
    // Remove all non-digit characters
    $digits = preg_replace('/\D/', '', $phone);
    
    // Check if it's a reasonable length (7-15 digits)
    return strlen($digits) >= 7 && strlen($digits) <= 15;
}

/**
 * Get client IP address with security checks
 * 
 * @return string|null Client IP address
 */
function get_client_ip_secure() {
    $ip = null;
    
    // Check for shared internet
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }
    // Check for IP passed from proxy
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    }
    // Check for IP from remote address
    elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    // Validate IP address
    if ($ip && filter_var($ip, FILTER_VALIDATE_IP)) {
        return $ip;
    }
    
    return null;
}
?>
