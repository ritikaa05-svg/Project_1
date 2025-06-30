<?php
session_start();
header('Content-Type: application/json');

require_once '../includes/db.php';

// Check if manager is logged in
if (!isset($_SESSION['manager'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

// Check if job ID is provided
if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Job ID required']);
    exit();
}

$jobId = $_GET['id'];

try {
    $pdo = new Database();
    $conn = $pdo->open();
    
    // Get job details with customer and employee information
    $stmt = $conn->prepare("SELECT j.*, c.name as customer_name, c.phone as customer_phone, c.email as customer_email,
                           e.name as employee_name, e.phone as employee_phone, e.email as employee_email
                           FROM jobs j 
                           LEFT JOIN customers c ON j.customer_id = c.id 
                           LEFT JOIN employees e ON j.employee_id = e.id 
                           WHERE j.id = ?");
    $stmt->execute([$jobId]);
    $job = $stmt->fetch();
    
    if (!$job) {
        echo json_encode(['success' => false, 'message' => 'Job not found']);
        exit();
    }
    
    // Get job applications for this job
    $stmt = $conn->prepare("SELECT ja.*, e.name as employee_name, e.phone as employee_phone
                           FROM job_applications ja
                           LEFT JOIN employees e ON ja.employee_id = e.id
                           WHERE ja.job_id = ?
                           ORDER BY ja.created_at DESC");
    $stmt->execute([$jobId]);
    $applications = $stmt->fetchAll();
    
    $response = [
        'success' => true,
        'job' => $job,
        'applications' => $applications
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 