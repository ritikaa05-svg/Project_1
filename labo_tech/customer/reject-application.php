<?php
session_start();
header('Content-Type: application/json');

require_once '../includes/db.php';

// Check if customer is logged in
if (!isset($_SESSION['customer'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['application_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$applicationId = $input['application_id'];
$customerId = $_SESSION['customer'];

try {
    $pdo = new Database();
    $conn = $pdo->open();
    
    // Get application details and verify it belongs to this customer
    $stmt = $conn->prepare("SELECT ja.*, j.customer_id, j.status as job_status
                           FROM job_applications ja
                           JOIN jobs j ON ja.job_id = j.id
                           WHERE ja.id = ? AND j.customer_id = ?");
    $stmt->execute([$applicationId, $customerId]);
    $application = $stmt->fetch();
    
    if (!$application) {
        echo json_encode(['success' => false, 'message' => 'Application not found']);
        exit();
    }
    
    if ($application['job_status'] !== 'pending') {
        echo json_encode(['success' => false, 'message' => 'Job is no longer available']);
        exit();
    }
    
    // Update application status to rejected
    $stmt = $conn->prepare("UPDATE job_applications SET status = 'rejected', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$applicationId]);
    
    echo json_encode(['success' => true, 'message' => 'Application rejected successfully']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 