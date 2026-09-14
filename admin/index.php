<?php
session_start();

require_once '../includes/db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin'])) {
    header('Location: ../login');
    exit();
}

try {
    $pdo = new Database();
    $conn = $pdo->open();
    
    // Get statistics
    $stmt = $conn->query("SELECT COUNT(*) as total FROM employees WHERE status = 'active'");
    $totalEmployees = $stmt->fetch()['total'];
    
    $stmt = $conn->query("SELECT COUNT(*) as total FROM customers WHERE status = 'active'");
    $totalCustomers = $stmt->fetch()['total'];
    
    $stmt = $conn->query("SELECT COUNT(*) as total FROM jobs WHERE status = 'active'");
    $totalJobs = $stmt->fetch()['total'];
    
    $stmt = $conn->query("SELECT COUNT(*) as total FROM jobs WHERE status = 'completed'");
    $completedJobs = $stmt->fetch()['total'];
    
    // Get recent activities with proper JOINs
    $stmt = $conn->query("SELECT j.*, c.name as customer_name, e.name as employee_name 
                         FROM jobs j 
                         LEFT JOIN customers c ON j.customer_id = c.id 
                         LEFT JOIN employees e ON j.employee_id = e.id 
                         ORDER BY j.created_at DESC LIMIT 5");
    $recentJobs = $stmt->fetchAll();
    
    // Get top performing employees
    $stmt = $conn->query("SELECT e.name, e.job_categories, COUNT(j.id) as job_count 
                         FROM employees e 
                         LEFT JOIN jobs j ON e.id = j.employee_id 
                         WHERE e.status = 'active' 
                         GROUP BY e.id, e.name, e.job_categories 
                         ORDER BY job_count DESC 
                         LIMIT 5");
    $topEmployees = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | LaboTech</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/webp" href="../assets/logo_without_bg.png">
    <link rel="stylesheet" href="../main.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <img src="../assets/logo_without_bg.png" alt="LaboTech" class="h-8 w-auto">
                    <span class="ml-2 text-xl font-bold text-gray-900">LaboTech Admin</span>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700">Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
                    <a href="../actions/logout" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="text-3xl font-bold text-blue-600"><?php echo $totalEmployees ?? 0; ?></div>
                <div class="text-sm text-gray-500">Active Employees</div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="text-3xl font-bold text-green-600"><?php echo $totalCustomers ?? 0; ?></div>
                <div class="text-sm text-gray-500">Registered Customers</div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="text-3xl font-bold text-purple-600"><?php echo $totalJobs ?? 0; ?></div>
                <div class="text-sm text-gray-500">Active Jobs</div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="text-3xl font-bold text-orange-600"><?php echo $completedJobs ?? 0; ?></div>
                <div class="text-sm text-gray-500">Completed Jobs</div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Recent Jobs -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4">Recent Jobs</h2>
                    <?php if (!empty($recentJobs)): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Job Title</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($recentJobs as $job): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($job['title']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($job['customer_name'] ?? 'Unknown'); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($job['employee_name'] ?? 'Unassigned'); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap">
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
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('M d, Y', strtotime($job['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500 text-center py-8">No recent jobs found.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Top Employees -->
            <div>
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4">Top Performing Employees</h2>
                    <?php if (!empty($topEmployees)): ?>
                        <div class="space-y-4">
                            <?php foreach ($topEmployees as $employee): ?>
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div>
                                        <h3 class="font-medium"><?php echo htmlspecialchars($employee['name']); ?></h3>
                                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($employee['job_categories']); ?></p>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-lg font-bold text-blue-600"><?php echo $employee['job_count']; ?></div>
                                        <div class="text-xs text-gray-500">jobs</div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500 text-center py-8">No employee data available.</p>
                    <?php endif; ?>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-lg shadow-md p-6 mt-6">
                    <h2 class="text-xl font-semibold mb-4">Quick Actions</h2>
                    <div class="space-y-3">
                        <a href="employees" class="block w-full bg-blue-600 text-white text-center py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors">
                            Manage Employees
                        </a>
                        <a href="customers" class="block w-full bg-green-600 text-white text-center py-2 px-4 rounded-lg hover:bg-green-700 transition-colors">
                            Manage Customers
                        </a>
                        <a href="jobs" class="block w-full bg-purple-600 text-white text-center py-2 px-4 rounded-lg hover:bg-purple-700 transition-colors">
                            View All Jobs
                        </a>
                        <a href="reports" class="block w-full bg-orange-600 text-white text-center py-2 px-4 rounded-lg hover:bg-orange-700 transition-colors">
                            Generate Reports
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add any JavaScript for interactivity here
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-refresh dashboard every 30 seconds
            setInterval(function() {
                location.reload();
            }, 30000);
        });
    </script>
</body>
</html> 