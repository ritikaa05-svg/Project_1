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
    $stmt = $conn->prepare("SELECT ja.*, j.customer_id, j.status as job_status, j.amount as job_amount
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
        echo json_encode(['success' => false, 'message' => 'Job is no longer available for assignment']);
        exit();
    }
    
    // Start transaction
    $conn->beginTransaction();
    
    try {
        // Update job to assign employee and change status to active
        $stmt = $conn->prepare("UPDATE jobs SET employee_id = ?, status = 'active', amount = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$application['employee_id'], $application['proposed_amount'], $application['job_id']]);
        
        // Update application status to accepted
        $stmt = $conn->prepare("UPDATE job_applications SET status = 'accepted', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$applicationId]);
        
        // Reject all other applications for this job
        $stmt = $conn->prepare("UPDATE job_applications SET status = 'rejected', updated_at = NOW() 
                               WHERE job_id = ? AND id != ?");
        $stmt->execute([$application['job_id'], $applicationId]);
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode(['success' => true, 'message' => 'Job assigned successfully']);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 