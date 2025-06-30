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

if (!isset($input['employee_id']) || !isset($input['action'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$employeeId = $input['employee_id'];
$action = $input['action'];

try {
    $pdo = new Database();
    $conn = $pdo->open();
    
    // Check if employee exists and is pending
    $stmt = $conn->prepare("SELECT * FROM employees WHERE id = ? AND status = 'pending'");
    $stmt->execute([$employeeId]);
    $employee = $stmt->fetch();
    
    if (!$employee) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Employee not found or not pending approval']);
        exit();
    }
    
    // Update employee status based on action
    $newStatus = ($action === 'approve') ? 'active' : 'inactive';
    
    $stmt = $conn->prepare("UPDATE employees SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $result = $stmt->execute([$newStatus, $employeeId]);
    
    if ($result) {
        // Log the action (you can create a logs table if needed)
        $actionText = ($action === 'approve') ? 'approved' : 'rejected';
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => "Employee {$actionText} successfully",
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