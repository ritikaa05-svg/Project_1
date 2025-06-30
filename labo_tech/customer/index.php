<?php
session_start();

require_once '../includes/db.php';

// Check if customer is logged in
if (!isset($_SESSION['customer'])) {
    header('Location: ../login');
    exit();
}

try {
    $pdo = new Database();
    $conn = $pdo->open();
    
    $customerId = $_SESSION['customer'];
    
    // Get customer details
    $stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch();
    
    // Get customer's jobs
    $stmt = $conn->prepare("SELECT j.*, e.name as employee_name, e.phone as employee_phone 
                           FROM jobs j 
                           LEFT JOIN employees e ON j.employee_id = e.id 
                           WHERE j.customer_id = ? 
                           ORDER BY j.created_at DESC");
    $stmt->execute([$customerId]);
    $customerJobs = $stmt->fetchAll();
    
    // Get job statistics
    $stmt = $conn->prepare("SELECT 
                           COUNT(*) as total_jobs,
                           SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_jobs,
                           SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_jobs,
                           SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_jobs,
                           SUM(amount) as total_spent
                           FROM jobs WHERE customer_id = ?");
    $stmt->execute([$customerId]);
    $jobStats = $stmt->fetch();
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard | LaboTech</title>
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
                    <span class="ml-2 text-xl font-bold text-gray-900">Customer Portal</span>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700">Welcome, <?php echo htmlspecialchars($_SESSION['customer_name']); ?></span>
                    <a href="profile" class="text-blue-600 hover:text-blue-800">Profile</a>
                    <a href="../actions/logout" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Customer Info Card -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($customer['name']); ?></h1>
                    <p class="text-gray-600"><?php echo htmlspecialchars($customer['email']); ?> • <?php echo htmlspecialchars($customer['phone']); ?></p>
                    <p class="text-sm text-gray-500">Member since <?php echo date('M Y', strtotime($customer['created_at'])); ?></p>
                </div>
                <div class="text-right">
                    <div class="text-3xl font-bold text-blue-600">$<?php echo number_format($jobStats['total_spent'] ?? 0, 2); ?></div>
                    <div class="text-sm text-gray-500">Total Spent</div>
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

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <div class="text-4xl text-blue-600 mb-4">
                    <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold mb-2">Post New Job</h3>
                <p class="text-gray-600 mb-4">Create a new job request and find the perfect professional</p>
                <a href="post-job" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    Post Job
                </a>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <div class="text-4xl text-green-600 mb-4">
                    <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold mb-2">Find Professionals</h3>
                <p class="text-gray-600 mb-4">Browse through our verified professionals and skilled workers</p>
                <a href="find-professionals" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition-colors">
                    Search
                </a>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <div class="text-4xl text-purple-600 mb-4">
                    <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold mb-2">Job History</h3>
                <p class="text-gray-600 mb-4">View all your past and current job requests</p>
                <a href="job-history" class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700 transition-colors">
                    View History
                </a>
            </div>
        </div>

        <!-- Recent Jobs -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-semibold">My Recent Jobs</h2>
                <a href="job-history" class="text-blue-600 hover:text-blue-800">View All</a>
            </div>
            
            <?php if (!empty($customerJobs)): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Job Title</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Professional</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($customerJobs as $job): ?>
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($job['title']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars(substr($job['description'], 0, 100)) . '...'; ?></div>
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
                                            <?php if ($job['status'] == 'pending'): ?>
                                                <a href="edit-job?id=<?php echo $job['id']; ?>" 
                                                   class="text-green-600 hover:text-green-900">Edit</a>
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
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No jobs yet</h3>
                    <p class="text-gray-500 mb-4">You haven't posted any jobs yet. Get started by posting your first job request.</p>
                    <a href="post-job" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        Post Your First Job
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 