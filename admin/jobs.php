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
    
    // Get all jobs with customer and employee details
    $stmt = $conn->query("SELECT j.*, c.name as customer_name, c.phone as customer_phone, 
                         e.name as employee_name, e.phone as employee_phone
                         FROM jobs j 
                         LEFT JOIN customers c ON j.customer_id = c.id 
                         LEFT JOIN employees e ON j.employee_id = e.id 
                         ORDER BY j.created_at DESC");
    $jobs = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Management | LaboTech Admin</title>
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
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Job Management</h1>
                <p class="text-gray-600">Monitor and manage all jobs</p>
            </div>
            <a href="index" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                Back to Dashboard
            </a>
        </div>

        <!-- Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <?php
            $totalJobs = count($jobs);
            $pendingJobs = count(array_filter($jobs, function($j) { return $j['status'] == 'pending'; }));
            $activeJobs = count(array_filter($jobs, function($j) { return $j['status'] == 'active'; }));
            $completedJobs = count(array_filter($jobs, function($j) { return $j['status'] == 'completed'; }));
            ?>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="text-3xl font-bold text-blue-600"><?php echo $totalJobs; ?></div>
                <div class="text-sm text-gray-500">Total Jobs</div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="text-3xl font-bold text-yellow-600"><?php echo $pendingJobs; ?></div>
                <div class="text-sm text-gray-500">Pending</div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="text-3xl font-bold text-green-600"><?php echo $activeJobs; ?></div>
                <div class="text-sm text-gray-500">Active</div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="text-3xl font-bold text-purple-600"><?php echo $completedJobs; ?></div>
                <div class="text-sm text-gray-500">Completed</div>
            </div>
        </div>

        <!-- Jobs Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold">All Jobs</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Job Details</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($jobs as $job): ?>
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($job['title']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars(substr($job['description'], 0, 100)) . '...'; ?></div>
                                    <div class="text-xs text-gray-400"><?php echo htmlspecialchars($job['location']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($job['customer_name']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($job['customer_phone']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($job['employee_name']): ?>
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($job['employee_name']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($job['employee_phone']); ?></div>
                                    <?php else: ?>
                                        <span class="text-gray-400">Unassigned</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">$<?php echo number_format($job['amount'], 2); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php 
                                        switch($job['status']) {
                                            case 'active': echo 'bg-green-100 text-green-800'; break;
                                            case 'completed': echo 'bg-blue-100 text-blue-800'; break;
                                            case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                            case 'cancelled': echo 'bg-red-100 text-red-800'; break;
                                            default: echo 'bg-gray-100 text-gray-800';
                                        }
                                        ?>">
                                        <?php echo ucfirst($job['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('M d, Y', strtotime($job['created_at'])); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <button onclick="viewJob(<?php echo $job['id']; ?>)" 
                                                class="text-blue-600 hover:text-blue-900">View</button>
                                        <?php if ($job['status'] == 'pending'): ?>
                                            <button onclick="assignJob(<?php echo $job['id']; ?>)" 
                                                    class="text-green-600 hover:text-green-900">Assign</button>
                                        <?php endif; ?>
                                        <?php if ($job['status'] == 'active'): ?>
                                            <button onclick="completeJob(<?php echo $job['id']; ?>)" 
                                                    class="text-purple-600 hover:text-purple-900">Complete</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function viewJob(id) {
            // Implement view job details
            alert('View job ' + id);
        }
        
        function assignJob(id) {
            // Implement assign job functionality
            alert('Assign job ' + id);
        }
        
        function completeJob(id) {
            if (confirm('Are you sure you want to mark this job as completed?')) {
                // Implement complete job functionality
                alert('Job ' + id + ' completed');
            }
        }
    </script>
</body>
</html> 