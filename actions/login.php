<?php
session_start();
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Validate input
    if (empty($email) || empty($password)) {
        $_SESSION['error'] = "Please fill in all fields.";
        header('Location: ../login');
        exit();
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Please enter a valid email address.";
        header('Location: ../login');
        exit();
    }
    
    try {
        $pdo = new Database();
        $conn = $pdo->open();
        
        // Check admin table first
        $stmt = $conn->prepare("SELECT * FROM admins WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_email'] = $admin['email'];
            header('Location: ../admin/');
            exit();
        }
        
        // Check managers table
        $stmt = $conn->prepare("SELECT * FROM managers WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $manager = $stmt->fetch();
        
        if ($manager && password_verify($password, $manager['password'])) {
            $_SESSION['manager'] = $manager['id'];
            $_SESSION['manager_name'] = $manager['name'];
            $_SESSION['manager_email'] = $manager['email'];
            header('Location: ../manager/');
            exit();
        }
        
        // Check employees table
        $stmt = $conn->prepare("SELECT * FROM employees WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $employee = $stmt->fetch();
        
        if ($employee && password_verify($password, $employee['password'])) {
            $_SESSION['employ'] = $employee['id'];
            $_SESSION['employ_name'] = $employee['name'];
            $_SESSION['employ_email'] = $employee['email'];
            $_SESSION['employ_job_categories'] = $employee['job_categories'];
            header('Location: ../employ/');
            exit();
        }
        
        // Check customers table
        $stmt = $conn->prepare("SELECT * FROM customers WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $customer = $stmt->fetch();
        
        if ($customer && password_verify($password, $customer['password'])) {
            $_SESSION['customer'] = $customer['id'];
            $_SESSION['customer_name'] = $customer['name'];
            $_SESSION['customer_email'] = $customer['email'];
            header('Location: ../customer/');
            exit();
        }
        
        // If no user found or password doesn't match
        $_SESSION['error'] = "Invalid email or password. Please try again.";
        header('Location: ../login');
        exit();
        
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
        header('Location: ../login');
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "An error occurred. Please try again.";
        header('Location: ../login');
        exit();
    }
} else {
    // If not POST request, redirect to login page
    header('Location: ../login');
    exit();
}
?> 