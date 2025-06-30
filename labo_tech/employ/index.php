<?php
session_start();

require_once '../includes/db.php';
require_once '../includes/category-mapping.php';

// Check if employee is logged in
if (!isset($_SESSION['employ'])) {
    header('Location: ../login');
    exit();
}

try {
    $pdo = new Database();
    $conn = $pdo->open();
    
    $employeeId = $_SESSION['employ'];
    
    // Get employee details
    $stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->execute([$employeeId]);
    $employee = $stmt->fetch();
    
    // Get employee's job categories
    $employeeJobCategories = explode(',', $employee['job_categories']);
    $employeeJobCategories = array_map('trim', $employeeJobCategories);
    
    // Get available job categories for this employee
    $availableJobCategories = CategoryMapping::getJobCategoriesForEmployee($employee['job_categories']);
    
    // Get assigned jobs
    $stmt = $conn->prepare("SELECT j.*, c.name as customer_name, c.phone as customer_phone 
                           FROM jobs j 
                           LEFT JOIN customers c ON j.customer_id = c.id 
                           WHERE j.employee_id = ? 
                           ORDER BY j.created_at DESC");
    $stmt->execute([$employeeId]);
    $assignedJobs = $stmt->fetchAll();
    
    // Get job statistics
    $stmt = $conn->prepare("SELECT 
                           COUNT(*) as total_jobs,
                           SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_jobs,
                           SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_jobs,
                           SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_jobs
                           FROM jobs WHERE employee_id = ?");
    $stmt->execute([$employeeId]);
    $jobStats = $stmt->fetch();
    
    // Get recent earnings
    $stmt = $conn->prepare("SELECT SUM(amount) as total_earnings 
                           FROM payments 
                           WHERE employee_id = ? AND status = 'completed'");
    $stmt->execute([$employeeId]);
    $earnings = $stmt->fetch();
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard | LaboTech</title>
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
                    <span class="ml-2 text-xl font-bold text-gray-900">Employee Portal</span>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700">Welcome, <?php echo htmlspecialchars($_SESSION['employ_name']); ?></span>
                    <a href="profile" class="text-blue-600 hover:text-blue-800">Profile</a>
                    <a href="../actions/logout" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Employee Info Card -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($employee['name']); ?></h1>
                    <p class="text-gray-600"><?php echo htmlspecialchars($employee['location']); ?></p>
                    <p class="text-sm text-gray-500">Member since <?php echo date('M Y', strtotime($employee['created_at'])); ?></p>
                    
                    <!-- Available Job Categories -->
                    <div class="mt-3">
                        <p class="text-sm font-medium text-gray-700 mb-2">Your Job Categories:</p>
                        <div class="flex flex-wrap gap-1">
                            <?php foreach ($employeeJobCategories as $jobCategory): ?>
                                <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">
                                    <?php echo htmlspecialchars($jobCategory); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-3xl font-bold text-green-600">$<?php echo number_format($earnings['total_earnings'] ?? 0, 2); ?></div>
                    <div class="text-sm text-gray-500">Total Earnings</div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="text-3xl font-bold text-blue-600"><?php echo $jobStats['total_jobs'] ?? 0; ?></div>
                <div class="text-sm text-gray-500">Total Jobs</div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="text-3xl font-bold text-green-600"><?php echo $jobStats['active_jobs'] ?? 0; ?></div>
                <div class="text-sm text-gray-500">Active Jobs</div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="text-3xl font-bold text-purple-600"><?php echo $jobStats['completed_jobs'] ?? 0; ?></div>
                <div class="text-sm text-gray-500">Completed</div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="text-3xl font-bold text-yellow-600"><?php echo $jobStats['pending_jobs'] ?? 0; ?></div>
                <div class="text-sm text-gray-500">Pending</div>
            </div>
        </div>

        <!-- Assigned Jobs -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-semibold">My Assigned Jobs</h2>
                <a href="available-jobs" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    View Available Jobs
                </a>
            </div>
            
            <?php if (!empty($assignedJobs)): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Job Title</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($assignedJobs as $job): ?>
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($job['title']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars(substr($job['description'], 0, 100)) . '...'; ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($job['customer_name'] ?? 'Unknown'); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($job['customer_phone'] ?? ''); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($job['location']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">$<?php echo number_format($job['amount'], 2); ?></td>
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
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <a href="job-details?id=<?php echo $job['id']; ?>" 
                                               class="text-blue-600 hover:text-blue-900">View</a>
                                            <?php if ($job['status'] == 'active'): ?>
                                                <button onclick="updateJobStatus(<?php echo $job['id']; ?>, 'completed')" 
                                                        class="text-green-600 hover:text-green-900">Complete</button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-12">
                    <div class="text-gray-400 mb-4">
                        <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No jobs assigned yet</h3>
                    <p class="text-gray-500 mb-4">You don't have any jobs assigned to you at the moment.</p>
                    <a href="available-jobs" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        Browse Available Jobs
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function updateJobStatus(jobId, status) {
            if (confirm('Are you sure you want to mark this job as ' + status + '?')) {
                fetch('update-job-status', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        job_id: jobId,
                        status: status
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Job status updated successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating job status.');
                });
            }
        }
    </script>
</body>
</html> 