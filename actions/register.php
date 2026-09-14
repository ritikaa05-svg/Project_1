<?php
session_start();
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userType = $_POST['user_type'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $location = trim($_POST['location']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Validate input
    if (empty($name) || empty($email) || empty($phone) || empty($location) || empty($password)) {
        $_SESSION['error'] = "Please fill in all required fields.";
        header('Location: ../register');
        exit();
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Please enter a valid email address.";
        header('Location: ../register');
        exit();
    }
    
    // Validate password
    if (strlen($password) < 6) {
        $_SESSION['error'] = "Password must be at least 6 characters long.";
        header('Location: ../register');
        exit();
    }
    
    if ($password !== $confirmPassword) {
        $_SESSION['error'] = "Passwords do not match.";
        header('Location: ../register');
        exit();
    }
    
    try {
        $pdo = new Database();
        $conn = $pdo->open();
        
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM customers WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = "Email already registered as a customer.";
            header('Location: ../register');
            exit();
        }
        
        $stmt = $conn->prepare("SELECT id FROM employees WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = "Email already registered as an employee.";
            header('Location: ../register');
            exit();
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        if ($userType === 'customer') {
            // Insert customer
            $stmt = $conn->prepare("INSERT INTO customers (name, email, phone, location, password, status, created_at) VALUES (?, ?, ?, ?, ?, 'active', NOW())");
            $stmt->execute([$name, $email, $phone, $location, $hashedPassword]);
            
            $_SESSION['success'] = "Customer account created successfully! You can now log in.";
            
        } elseif ($userType === 'employee') {
            // Validate employee-specific fields
            $jobCategories = isset($_POST['job_categories']) ? $_POST['job_categories'] : [];
            $description = trim($_POST['description'] ?? ''); // Optional field
            $hourlyRate = floatval($_POST['hourly_rate']);
            
            if (empty($jobCategories)) {
                $_SESSION['error'] = "Please select at least one job category.";
                header('Location: ../register');
                exit();
            }
            
            if ($hourlyRate <= 0) {
                $_SESSION['error'] = "Please enter a valid hourly rate.";
                header('Location: ../register');
                exit();
            }
            
            // Convert job categories array to comma-separated string
            $jobCategoriesString = implode(',', $jobCategories);
            
            // Insert employee with job categories and optional description
            $stmt = $conn->prepare("INSERT INTO employees (name, email, phone, location, job_categories, skills, hourly_rate, password, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())");
            $stmt->execute([$name, $email, $phone, $location, $jobCategoriesString, $description, $hourlyRate, $hashedPassword]);
            
            $_SESSION['success'] = "Professional account created successfully! Your account will be reviewed and activated soon.";
            
        } else {
            $_SESSION['error'] = "Invalid user type.";
            header('Location: ../register');
            exit();
        }
        
        header('Location: ../login');
        exit();
        
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
        header('Location: ../register');
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = "An error occurred. Please try again.";
        header('Location: ../register');
        exit();
    }
} else {
    // If not POST request, redirect to registration page
    header('Location: ../register');
    exit();
}
?> 