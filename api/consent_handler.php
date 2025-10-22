<?php
/**
 * Consent Handler API
 * 
 * Handles user consent preferences for GDPR compliance.
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

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        exit;
    }
    
    $consent = $input['consent'] ?? false;
    $preferences = $input['preferences'] ?? [];
    $timestamp = $input['timestamp'] ?? date('Y-m-d H:i:s');
    
    // Get client IP
    $client_ip = get_client_ip_secure();
    
    // Get database connection
    $db = get_database_connection();
    
    // Initialize geolocation system
    $geo = new IPGeolocation($db, get_config('geolocation_api_key'), get_config('geolocation_api_provider'));
    
    // Set consent
    $geo->set_consent($consent);
    
    // Store consent record
    try {
        $stmt = $db->prepare("
            INSERT INTO consent_records (
                ip_address, consent_type, consent_given, 
                consent_timestamp, user_agent, country
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $geo_data = $geo->get_geolocation($client_ip);
        $country = $geo_data['country'] ?? null;
        
        // Store each consent type
        $consent_types = ['analytics', 'marketing', 'necessary'];
        foreach ($consent_types as $type) {
            $type_consent = $preferences[$type] ?? false;
            
            $stmt->execute([
                $client_ip,
                $type,
                $type_consent ? 1 : 0,
                $timestamp,
                $user_agent,
                $country
            ]);
        }
        
    } catch (PDOException $e) {
        error_log("Consent storage error: " . $e->getMessage());
        // Don't fail the request if consent storage fails
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Consent preferences saved',
        'data' => [
            'consent' => $consent,
            'preferences' => $preferences,
            'timestamp' => $timestamp
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Consent handler error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error'
    ]);
}
?>
