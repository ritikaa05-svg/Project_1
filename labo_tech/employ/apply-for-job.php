<?php
session_start();
header('Content-Type: application/json');

require_once '../includes/db.php';

// Check if employee is logged in
if (!isset($_SESSION['employ'])) {
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

if (!$input || !isset($input['job_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$jobId = $input['job_id'];
$employeeId = $_SESSION['employ'];
$proposedAmount = $input['proposed_amount'] ?? 0;
$message = $input['message'] ?? '';

try {
    $pdo = new Database();
    $conn = $pdo->open();
    
    // Check if job exists and is available
    $stmt = $conn->prepare("SELECT * FROM jobs WHERE id = ? AND status = 'pending' AND employee_id IS NULL");
    $stmt->execute([$jobId]);
    $job = $stmt->fetch();
    
    if (!$job) {
        echo json_encode(['success' => false, 'message' => 'Job not available']);
        exit();
    }
    
    // Check if employee has already applied
    $stmt = $conn->prepare("SELECT * FROM job_applications WHERE job_id = ? AND employee_id = ?");
    $stmt->execute([$jobId, $employeeId]);
    $existingApplication = $stmt->fetch();
    
    if ($existingApplication) {
        echo json_encode(['success' => false, 'message' => 'You have already applied for this job']);
        exit();
    }
    
    // Insert application
    $stmt = $conn->prepare("INSERT INTO job_applications (job_id, employee_id, proposed_amount, message, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
    $stmt->execute([$jobId, $employeeId, $proposedAmount, $message]);
    
    echo json_encode(['success' => true, 'message' => 'Application submitted successfully']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 