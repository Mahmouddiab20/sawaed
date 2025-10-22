<?php
/**
 * Delete Lead API
 * 
 * Handles deletion of leads from the admin dashboard.
 */

// Security check
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Include required files
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $lead_id = $input['id'] ?? null;
    
    if (!$lead_id || !is_numeric($lead_id)) {
        echo json_encode(['success' => false, 'message' => 'Invalid lead ID']);
        exit;
    }
    
    $db = get_database_connection();
    
    // Get lead details before deletion for logging
    $stmt = $db->prepare("SELECT name, email FROM leads WHERE id = ?");
    $stmt->execute([$lead_id]);
    $lead = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$lead) {
        echo json_encode(['success' => false, 'message' => 'Lead not found']);
        exit;
    }
    
    // Delete the lead
    $stmt = $db->prepare("DELETE FROM leads WHERE id = ?");
    $stmt->execute([$lead_id]);
    
    if ($stmt->rowCount() > 0) {
        // Log the deletion
        error_log("Lead deleted by admin: ID={$lead_id}, Name={$lead['name']}, Email={$lead['email']}");
        
        echo json_encode([
            'success' => true,
            'message' => 'Lead deleted successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete lead']);
    }
    
} catch (Exception $e) {
    error_log("Delete lead error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
?>
