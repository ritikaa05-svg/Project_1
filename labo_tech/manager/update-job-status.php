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

if (!$input || !isset($input['job_id']) || !isset($input['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$jobId = $input['job_id'];
$status = $input['status'];

// Validate status
$allowedStatuses = ['active', 'completed', 'cancelled'];
if (!in_array($status, $allowedStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

try {
    $pdo = new Database();
    $conn = $pdo->open();
    
    // Check if job exists
    $stmt = $conn->prepare("SELECT * FROM jobs WHERE id = ?");
    $stmt->execute([$jobId]);
    $job = $stmt->fetch();
    
    if (!$job) {
        echo json_encode(['success' => false, 'message' => 'Job not found']);
        exit();
    }
    
    // Update job status
    $stmt = $conn->prepare("UPDATE jobs SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$status, $jobId]);
    
    // If job is completed, create a payment record
    if ($status === 'completed' && $job['employee_id']) {
        $stmt = $conn->prepare("INSERT INTO payments (job_id, employee_id, customer_id, amount, status, created_at) 
                               VALUES (?, ?, ?, ?, 'completed', NOW())");
        $stmt->execute([$jobId, $job['employee_id'], $job['customer_id'], $job['amount']]);
    }
    
    echo json_encode(['success' => true, 'message' => 'Job status updated successfully']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 