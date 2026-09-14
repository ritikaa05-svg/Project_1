<?php
session_start();

require_once '../includes/db.php';

// Check if manager is logged in
if (!isset($_SESSION['manager'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['employee_id']) || !isset($input['status'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$employeeId = $input['employee_id'];
$newStatus = $input['status'];

// Validate status
$validStatuses = ['active', 'inactive', 'suspended'];
if (!in_array($newStatus, $validStatuses)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

try {
    $pdo = new Database();
    $conn = $pdo->open();
    
    // Check if employee exists
    $stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->execute([$employeeId]);
    $employee = $stmt->fetch();
    
    if (!$employee) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
        exit();
    }
    
    // Update employee status
    $stmt = $conn->prepare("UPDATE employees SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $result = $stmt->execute([$newStatus, $employeeId]);
    
    if ($result) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => "Employee status updated to {$newStatus}",
            'status' => $newStatus
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to update employee status']);
    }
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 