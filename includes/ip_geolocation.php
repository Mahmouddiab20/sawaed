<?php
/**
 * Visitor IP Geolocation System for Sawaed Marketing Agency
 * 
 * This system captures visitor IP addresses, resolves their location using
 * geolocation APIs, and stores lead data with privacy compliance.
 * 
 * Features:
 * - Robust IP detection (handles proxies, load balancers)
 * - Multiple geolocation API support
 * - Privacy-compliant data collection
 * - Lead tracking and analytics
 * - GDPR compliance features
 */

class IPGeolocation {
    private $db;
    private $api_key;
    private $api_provider;
    
    public function __construct($database_connection, $api_key = null, $api_provider = 'ipapi') {
        $this->db = $database_connection;
        $this->api_key = $api_key;
        $this->api_provider = $api_provider;
    }
    
    /**
     * Get visitor's real IP address (handles proxies and load balancers)
     * 
     * @return string|null The visitor's IP address
     */
    public function get_client_ip() {
        $ip = null;
        
        // Check for shared internet
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        // Check for IP passed from proxy
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // X-Forwarded-For can contain multiple IPs, get the first one
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
        }
        // Check for IP from remote address
        elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        // Validate IP address
        if ($ip && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return $ip;
        }
        
        // Fallback to REMOTE_ADDR even if it's a private IP
        return $_SERVER['REMOTE_ADDR'] ?? null;
    }
    
    /**
     * Get geolocation data for an IP address
     * 
     * @param string $ip The IP address to lookup
     * @return array|null Geolocation data or null if failed
     */
    public function get_geolocation($ip) {
        if (!$ip || !filter_var($ip, FILTER_VALIDATE_IP)) {
            return null;
        }
        
        // Skip private IPs
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return [
                'country' => 'Private Network',
                'region' => 'Local',
                'city' => 'Local',
                'lat' => null,
                'lon' => null,
                'isp' => 'Private Network',
                'timezone' => null
            ];
        }
        
        switch ($this->api_provider) {
            case 'ipapi':
                return $this->get_geolocation_ipapi($ip);
            case 'ipinfo':
                return $this->get_geolocation_ipinfo($ip);
            case 'ipstack':
                return $this->get_geolocation_ipstack($ip);
            default:
                return $this->get_geolocation_ipapi($ip);
        }
    }
    
    /**
     * Get geolocation using ip-api.com (free tier available)
     */
    private function get_geolocation_ipapi($ip) {
        $url = "http://ip-api.com/json/" . urlencode($ip) . "?fields=status,message,country,regionName,city,lat,lon,timezone,isp,query";
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'Sawaed Marketing Agency/1.0'
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            return null;
        }
        
        $data = json_decode($response, true);
        
        if ($data['status'] === 'success') {
            return [
                'country' => $data['country'] ?? null,
                'region' => $data['regionName'] ?? null,
                'city' => $data['city'] ?? null,
                'lat' => $data['lat'] ?? null,
                'lon' => $data['lon'] ?? null,
                'isp' => $data['isp'] ?? null,
                'timezone' => $data['timezone'] ?? null,
                'query' => $data['query'] ?? $ip
            ];
        }
        
        return null;
    }
    
    /**
     * Get geolocation using ipinfo.io
     */
    private function get_geolocation_ipinfo($ip) {
        $url = "https://ipinfo.io/" . urlencode($ip) . "/json";
        
        if ($this->api_key) {
            $url .= "?token=" . $this->api_key;
        }
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'Sawaed Marketing Agency/1.0'
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            return null;
        }
        
        $data = json_decode($response, true);
        
        if (isset($data['country'])) {
            $location = explode(',', $data['loc'] ?? '');
            
            return [
                'country' => $data['country'] ?? null,
                'region' => $data['region'] ?? null,
                'city' => $data['city'] ?? null,
                'lat' => isset($location[0]) ? (float)$location[0] : null,
                'lon' => isset($location[1]) ? (float)$location[1] : null,
                'isp' => $data['org'] ?? null,
                'timezone' => $data['timezone'] ?? null,
                'query' => $data['ip'] ?? $ip
            ];
        }
        
        return null;
    }
    
    /**
     * Get geolocation using ipstack.com
     */
    private function get_geolocation_ipstack($ip) {
        if (!$this->api_key) {
            return null;
        }
        
        $url = "http://api.ipstack.com/" . urlencode($ip) . "?access_key=" . $this->api_key;
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'Sawaed Marketing Agency/1.0'
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            return null;
        }
        
        $data = json_decode($response, true);
        
        if (isset($data['country_name'])) {
            return [
                'country' => $data['country_name'] ?? null,
                'region' => $data['region_name'] ?? null,
                'city' => $data['city'] ?? null,
                'lat' => $data['latitude'] ?? null,
                'lon' => $data['longitude'] ?? null,
                'isp' => $data['connection']['isp'] ?? null,
                'timezone' => $data['time_zone']['id'] ?? null,
                'query' => $data['ip'] ?? $ip
            ];
        }
        
        return null;
    }
    
    /**
     * Capture and store visitor data
     * 
     * @param array $form_data The form submission data
     * @param string $form_type Type of form (contact, job_application, etc.)
     * @return bool Success status
     */
    public function capture_lead($form_data, $form_type = 'contact') {
        $ip = $this->get_client_ip();
        $geo_data = $this->get_geolocation($ip);
        
        // Get additional visitor data
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $referrer = $_SERVER['HTTP_REFERER'] ?? null;
        $utm_source = $_GET['utm_source'] ?? null;
        $utm_medium = $_GET['utm_medium'] ?? null;
        $utm_campaign = $_GET['utm_campaign'] ?? null;
        
        // Check if user has consented to tracking
        $consent_given = $this->check_consent();
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO leads (
                    name, email, phone, message, form_type, 
                    ip_address, country, region, city, latitude, longitude, 
                    isp, timezone, user_agent, referrer, 
                    utm_source, utm_medium, utm_campaign,
                    consent_given, created_at
                ) VALUES (
                    :name, :email, :phone, :message, :form_type,
                    :ip_address, :country, :region, :city, :latitude, :longitude,
                    :isp, :timezone, :user_agent, :referrer,
                    :utm_source, :utm_medium, :utm_campaign,
                    :consent_given, NOW()
                )
            ");
            
            $stmt->execute([
                'name' => $form_data['name'] ?? null,
                'email' => $form_data['email'] ?? null,
                'phone' => $form_data['phone'] ?? null,
                'message' => $form_data['message'] ?? null,
                'form_type' => $form_type,
                'ip_address' => $ip,
                'country' => $geo_data['country'] ?? null,
                'region' => $geo_data['region'] ?? null,
                'city' => $geo_data['city'] ?? null,
                'latitude' => $geo_data['lat'] ?? null,
                'longitude' => $geo_data['lon'] ?? null,
                'isp' => $geo_data['isp'] ?? null,
                'timezone' => $geo_data['timezone'] ?? null,
                'user_agent' => $user_agent,
                'referrer' => $referrer,
                'utm_source' => $utm_source,
                'utm_medium' => $utm_medium,
                'utm_campaign' => $utm_campaign,
                'consent_given' => $consent_given ? 1 : 0
            ]);
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Lead capture error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if user has given consent for tracking
     * 
     * @return bool Whether consent has been given
     */
    private function check_consent() {
        // Check for consent cookie
        if (isset($_COOKIE['sawaed_consent']) && $_COOKIE['sawaed_consent'] === 'accepted') {
            return true;
        }
        
        // Check for consent in session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['sawaed_consent']) && $_SESSION['sawaed_consent'] === 'accepted';
    }
    
    /**
     * Set user consent
     * 
     * @param bool $consent Whether user consents to tracking
     */
    public function set_consent($consent) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION['sawaed_consent'] = $consent ? 'accepted' : 'declined';
        
        // Set cookie for 1 year
        setcookie('sawaed_consent', $consent ? 'accepted' : 'declined', time() + (365 * 24 * 60 * 60), '/', '', true, true);
    }
    
    /**
     * Get analytics data for admin dashboard
     * 
     * @param int $days Number of days to look back
     * @return array Analytics data
     */
    public function get_analytics($days = 30) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_leads,
                    COUNT(DISTINCT ip_address) as unique_visitors,
                    COUNT(CASE WHEN consent_given = 1 THEN 1 END) as consented_leads,
                    COUNT(CASE WHEN form_type = 'contact' THEN 1 END) as contact_leads,
                    COUNT(CASE WHEN form_type = 'job_application' THEN 1 END) as job_leads,
                    country,
                    region,
                    city
                FROM leads 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                GROUP BY country, region, city
                ORDER BY total_leads DESC
            ");
            
            $stmt->execute(['days' => $days]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Analytics error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get recent leads for admin dashboard
     * 
     * @param int $limit Number of leads to return
     * @return array Recent leads
     */
    public function get_recent_leads($limit = 50) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    id, name, email, phone, form_type, 
                    country, region, city, 
                    utm_source, utm_medium, utm_campaign,
                    consent_given, created_at
                FROM leads 
                ORDER BY created_at DESC 
                LIMIT :limit
            ");
            
            $stmt->execute(['limit' => $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Recent leads error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Delete old leads for data retention compliance
     * 
     * @param int $days Number of days to retain data
     * @return int Number of deleted records
     */
    public function cleanup_old_leads($days = 365) {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM leads 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)
            ");
            
            $stmt->execute(['days' => $days]);
            return $stmt->rowCount();
            
        } catch (PDOException $e) {
            error_log("Cleanup error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Delete specific lead (GDPR right to be forgotten)
     * 
     * @param string $email Email of lead to delete
     * @return bool Success status
     */
    public function delete_lead_by_email($email) {
        try {
            $stmt = $this->db->prepare("DELETE FROM leads WHERE email = :email");
            $stmt->execute(['email' => $email]);
            return $stmt->rowCount() > 0;
            
        } catch (PDOException $e) {
            error_log("Delete lead error: " . $e->getMessage());
            return false;
        }
    }
}
?>
