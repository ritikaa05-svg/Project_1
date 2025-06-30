<?php
session_start();
header('Content-Type: application/json');

require_once '../includes/db.php';

// Check if manager is logged in
if (!isset($_SESSION['manager'])) {
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

if (!$input || !isset($input['job_id']) || !isset($input['employee_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$jobId = $input['job_id'];
$employeeId = $input['employee_id'];
$amount = $input['amount'] ?? null;

try {
    $pdo = new Database();
    $conn = $pdo->open();
    
    // Check if job exists and is pending
    $stmt = $conn->prepare("SELECT * FROM jobs WHERE id = ? AND status = 'pending'");
    $stmt->execute([$jobId]);
    $job = $stmt->fetch();
    
    if (!$job) {
        echo json_encode(['success' => false, 'message' => 'Job not found or not available for assignment']);
        exit();
    }
    
    // Check if employee exists and is active
    $stmt = $conn->prepare("SELECT * FROM employees WHERE id = ? AND status = 'active'");
    $stmt->execute([$employeeId]);
    $employee = $stmt->fetch();
    
    if (!$employee) {
        echo json_encode(['success' => false, 'message' => 'Employee not found or not active']);
        exit();
    }
    
    // Check if employee job categories match job category
    $employeeJobCategories = explode(',', $employee['job_categories']);
    $employeeJobCategories = array_map('trim', $employeeJobCategories);
    
    if (!in_array($job['category'], $employeeJobCategories)) {
        echo json_encode(['success' => false, 'message' => 'Employee job categories do not match job category']);
        exit();
    }
    
    // Start transaction
    $conn->beginTransaction();
    
    try {
        // Update job to assign employee and change status to active
        $updateAmount = $amount ? $amount : $job['amount'];
        $stmt = $conn->prepare("UPDATE jobs SET employee_id = ?, status = 'active', amount = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$employeeId, $updateAmount, $jobId]);
        
        // Create a job application record for tracking
        $stmt = $conn->prepare("INSERT INTO job_applications (job_id, employee_id, proposed_amount, status, created_at) 
                               VALUES (?, ?, ?, 'accepted', NOW())");
        $stmt->execute([$jobId, $employeeId, $updateAmount]);
        
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