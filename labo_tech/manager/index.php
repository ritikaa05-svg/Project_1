<?php
session_start();

require_once '../includes/db.php';

// Check if manager is logged in
if (!isset($_SESSION['manager'])) {
    header('Location: ../login');
    exit();
}

try {
    $pdo = new Database();
    $conn = $pdo->open();
    
    // Get statistics
    $stmt = $conn->query("SELECT COUNT(*) as total FROM employees WHERE status = 'pending'");
    $pendingEmployees = $stmt->fetch()['total'];
    
    $stmt = $conn->query("SELECT COUNT(*) as total FROM jobs WHERE status = 'pending'");
    $pendingJobs = $stmt->fetch()['total'];
    
    $stmt = $conn->query("SELECT COUNT(*) as total FROM jobs WHERE status = 'active'");
    $activeJobs = $stmt->fetch()['total'];
    
    $stmt = $conn->query("SELECT COUNT(*) as total FROM customers WHERE status = 'active'");
    $totalCustomers = $stmt->fetch()['total'];
    
    // Get pending employee approvals
    $stmt = $conn->query("SELECT * FROM employees WHERE status = 'pending' ORDER BY created_at DESC LIMIT 5");
    $pendingApprovals = $stmt->fetchAll();
    
    // Get recent jobs
    $stmt = $conn->query("SELECT j.*, c.name as customer_name, e.name as employee_name 
                         FROM jobs j 
                         LEFT JOIN customers c ON j.customer_id = c.id 
                         LEFT JOIN employees e ON j.employee_id = e.id 
                         ORDER BY j.created_at DESC LIMIT 5");
    $recentJobs = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard | LaboTech</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/webp" href="../assets/logo_without_bg.png">
</head>

<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <img src="../assets/logo_without_bg.png" alt="LaboTech" class="h-8 w-auto">
                    <span class="ml-2 text-xl font-bold text-gray-900">Manager Portal</span>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700">Welcome, <?php echo htmlspecialchars($_SESSION['manager_name']); ?></span>
                    <a href="../actions/logout" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="text-3xl font-bold text-yellow-600"><?php echo $pendingEmployees ?? 0; ?></div>
                <div class="text-sm text-gray-500">Pending Approvals</div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="text-3xl font-bold text-orange-600"><?php echo $pendingJobs ?? 0; ?></div>
                <div class="text-sm text-gray-500">Pending Jobs</div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="text-3xl font-bold text-green-600"><?php echo $activeJobs ?? 0; ?></div>
                <div class="text-sm text-gray-500">Active Jobs</div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="text-3xl font-bold text-blue-600"><?php echo $totalCustomers ?? 0; ?></div>
                <div class="text-sm text-gray-500">Total Customers</div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Pending Employee Approvals -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold">Pending Employee Approvals</h2>
                    <a href="employee-approvals" class="text-blue-600 hover:text-blue-800">View All</a>
                </div>
                
                <?php if (!empty($pendingApprovals)): ?>
                    <div class="space-y-4">
                        <?php foreach ($pendingApprovals as $employee): ?>
                            <div class="border rounded-lg p-4">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="font-medium"><?php echo htmlspecialchars($employee['name']); ?></h3>
                                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($employee['job_categories']); ?></p>
                                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($employee['location']); ?></p>
                                        <p class="text-sm text-gray-500">$<?php echo number_format($employee['hourly_rate'], 2); ?>/hr</p>
                                    </div>
                                    <div class="flex space-x-2">
                                        <button onclick="approveEmployee(<?php echo $employee['id']; ?>)" 
                                                class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700">
                                            Approve
                                        </button>
                                        <button onclick="rejectEmployee(<?php echo $employee['id']; ?>)" 
                                                class="bg-red-600 text-white px-3 py-1 rounded text-sm hover:bg-red-700">
                                            Reject
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 text-center py-8">No pending employee approvals.</p>
                <?php endif; ?>
            </div>

            <!-- Recent Jobs -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold">Recent Jobs</h2>
                    <a href="jobs" class="text-blue-600 hover:text-blue-800">View All</a>
                </div>
                
                <?php if (!empty($recentJobs)): ?>
                    <div class="space-y-4">
                        <?php foreach ($recentJobs as $job): ?>
                            <div class="border rounded-lg p-4">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="font-medium"><?php echo htmlspecialchars($job['title']); ?></h3>
                                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($job['customer_name'] ?? 'Unknown'); ?></p>
                                        <p class="text-sm text-gray-500">$<?php echo number_format($job['amount'], 2); ?></p>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php 
                                            switch($job['status']) {
                                                case 'active': echo 'bg-green-100 text-green-800'; break;
                                                case 'completed': echo 'bg-blue-100 text-blue-800'; break;
                                                case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                                default: echo 'bg-gray-100 text-gray-800';
                                            }
                                            ?>">
                                            <?php echo ucfirst($job['status']); ?>
                                        </span>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm text-gray-500"><?php echo date('M d, Y', strtotime($job['created_at'])); ?></p>
                                        <?php if ($job['employee_name']): ?>
                                            <p class="text-sm text-gray-600">Assigned to: <?php echo htmlspecialchars($job['employee_name']); ?></p>
                                        <?php else: ?>
                                            <p class="text-sm text-gray-400">Unassigned</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 text-center py-8">No recent jobs found.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white rounded-lg shadow-md p-6 mt-8">
            <h2 class="text-xl font-semibold mb-6">Quick Actions</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <a href="employee-approvals" class="bg-blue-600 text-white p-6 rounded-lg text-center hover:bg-blue-700 transition-colors">
                    <div class="text-3xl mb-2">👥</div>
                    <h3 class="font-semibold">Employee Approvals</h3>
                    <p class="text-sm opacity-90">Review and approve employee applications</p>
                </a>
                
                <a href="jobs" class="bg-green-600 text-white p-6 rounded-lg text-center hover:bg-green-700 transition-colors">
                    <div class="text-3xl mb-2">📋</div>
                    <h3 class="font-semibold">Job Management</h3>
                    <p class="text-sm opacity-90">Monitor and manage job assignments</p>
                </a>
                
                <a href="employees" class="bg-purple-600 text-white p-6 rounded-lg text-center hover:bg-purple-700 transition-colors">
                    <div class="text-3xl mb-2">👨‍💼</div>
                    <h3 class="font-semibold">Employee Management</h3>
                    <p class="text-sm opacity-90">Manage employee accounts and performance</p>
                </a>
            </div>
        </div>
    </div>

    <script>
        function approveEmployee(employeeId) {
            if (confirm('Are you sure you want to approve this employee?')) {
                fetch('approve-employee', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        employee_id: employeeId,
                        action: 'approve'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Employee approved successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while approving the employee.');
                });
            }
        }
        
        function rejectEmployee(employeeId) {
            if (confirm('Are you sure you want to reject this employee?')) {
                fetch('approve-employee', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        employee_id: employeeId,
                        action: 'reject'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Employee rejected successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while rejecting the employee.');
                });
            }
        }
    </script>
</body>
</html> 