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

if (!$input || !isset($input['job_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$jobId = $input['job_id'];
$customerId = $_SESSION['customer'];

try {
    $pdo = new Database();
    $conn = $pdo->open();
    
    // Check if job exists and belongs to this customer
    $stmt = $conn->prepare("SELECT * FROM jobs WHERE id = ? AND customer_id = ? AND status = 'pending'");
    $stmt->execute([$jobId, $customerId]);
    $job = $stmt->fetch();
    
    if (!$job) {
        echo json_encode(['success' => false, 'message' => 'Job not found or cannot be cancelled']);
        exit();
    }
    
    // Update job status to cancelled
    $stmt = $conn->prepare("UPDATE jobs SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$jobId]);
    
    echo json_encode(['success' => true, 'message' => 'Job cancelled successfully']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 