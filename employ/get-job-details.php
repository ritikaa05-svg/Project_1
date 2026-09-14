<?php
session_start();
header('Content-Type: application/json');

require_once '../includes/db.php';

// Check if employee is logged in
if (!isset($_SESSION['employ'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

// Check if job ID is provided
if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Job ID required']);
    exit();
}

$jobId = $_GET['id'];
$employeeId = $_SESSION['employ'];

try {
    $pdo = new Database();
    $conn = $pdo->open();
    
    // Get job details with customer information
    $stmt = $conn->prepare("SELECT j.*, c.name as customer_name, c.phone as customer_phone, c.email as customer_email
                           FROM jobs j 
                           LEFT JOIN customers c ON j.customer_id = c.id 
                           WHERE j.id = ?");
    $stmt->execute([$jobId]);
    $job = $stmt->fetch();
    
    if (!$job) {
        echo json_encode(['success' => false, 'message' => 'Job not found']);
        exit();
    }
    
    // Check if this job is assigned to the current employee
    $isAssigned = ($job['employee_id'] == $employeeId);
    
    // Get job applications for this job
    $stmt = $conn->prepare("SELECT ja.*, e.name as employee_name, e.phone as employee_phone
                           FROM job_applications ja
                           LEFT JOIN employees e ON ja.employee_id = e.id
                           WHERE ja.job_id = ?
                           ORDER BY ja.created_at DESC");
    $stmt->execute([$jobId]);
    $applications = $stmt->fetchAll();
    
    // Check if current employee has applied for this job
    $hasApplied = false;
    $myApplication = null;
    foreach ($applications as $app) {
        if ($app['employee_id'] == $employeeId) {
            $hasApplied = true;
            $myApplication = $app;
            break;
        }
    }
    
    $response = [
        'success' => true,
        'job' => $job,
        'isAssigned' => $isAssigned,
        'hasApplied' => $hasApplied,
        'myApplication' => $myApplication,
        'applications' => $applications
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 