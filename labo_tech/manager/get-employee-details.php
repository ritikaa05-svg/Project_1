<?php
session_start();

require_once '../includes/db.php';

// Check if manager is logged in
if (!isset($_SESSION['manager'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if employee ID is provided
if (!isset($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Employee ID is required']);
    exit();
}

$employeeId = $_GET['id'];

try {
    $pdo = new Database();
    $conn = $pdo->open();
    
    // Get employee details
    $stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->execute([$employeeId]);
    $employee = $stmt->fetch();
    
    if (!$employee) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
        exit();
    }
    
    // Get employee job statistics
    $stmt = $conn->prepare("SELECT 
                           COUNT(*) as total_jobs,
                           SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_jobs,
                           SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_jobs,
                           SUM(amount) as total_earnings
                           FROM jobs WHERE employee_id = ?");
    $stmt->execute([$employeeId]);
    $jobStats = $stmt->fetch();
    
    // Combine employee data with job statistics
    $employeeData = array_merge($employee, [
        'job_stats' => $jobStats
    ]);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'employee' => $employeeData
    ]);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 