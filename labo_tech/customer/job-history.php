<?php
session_start();

require_once '../includes/db.php';

// Check if customer is logged in
if (!isset($_SESSION['customer'])) {
    header('Location: ../login');
    exit();
}

$customerId = $_SESSION['customer'];

try {
    $pdo = new Database();
    $conn = $pdo->open();
    
    // Get customer's jobs with employee information
    $stmt = $conn->prepare("SELECT j.*, e.name as employee_name, e.phone as employee_phone, e.email as employee_email,
                           (SELECT COUNT(*) FROM job_applications WHERE job_id = j.id) as application_count
                           FROM jobs j 
                           LEFT JOIN employees e ON j.employee_id = e.id 
                           WHERE j.customer_id = ? 
                           ORDER BY j.created_at DESC");
    $stmt->execute([$customerId]);
    $jobs = $stmt->fetchAll();
    
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
    <title>Job History | LaboTech</title>
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
                    <a href="index" class="text-blue-600 hover:text-blue-800">Dashboard</a>
                    <a href="../actions/logout" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Job History</h1>
                <p class="text-gray-600">View all your job requests and their current status</p>
            </div>
            <a href="post-job" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                Post New Job
            </a>
        </div>

        <!-- Statistics -->
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

        <!-- Search and Filter -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <div class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <input type="text" id="searchInput" placeholder="Search jobs by title or description..." 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex gap-2">
                    <select id="statusFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="active">Active</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <select id="categoryFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Categories</option>
                        <option value="Plumbing">Plumbing</option>
                        <option value="Electrical">Electrical</option>
                        <option value="Carpentry">Carpentry</option>
                        <option value="Cleaning">Cleaning</option>
                        <option value="Gardening">Gardening</option>
                        <option value="Painting">Painting</option>
                        <option value="Moving">Moving</option>
                        <option value="Repair">Repair</option>
                        <option value="Installation">Installation</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Jobs Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold">All Jobs (<?php echo count($jobs); ?>)</h2>
            </div>
            
            <?php if (!empty($jobs)): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Job Details</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Professional</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="jobsTableBody">
                            <?php foreach ($jobs as $job): ?>
                                <tr class="job-row" data-status="<?php echo $job['status']; ?>" data-category="<?php echo $job['category']; ?>">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($job['title']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars(substr($job['description'], 0, 100)) . '...'; ?></div>
                                        <div class="text-xs text-gray-400"><?php echo htmlspecialchars($job['location']); ?></div>
                                        <?php if ($job['application_count'] > 0): ?>
                                            <div class="text-xs text-blue-600"><?php echo $job['application_count']; ?> applications</div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($job['employee_name']): ?>
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($job['employee_name']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($job['employee_phone']); ?></div>
                                            <div class="text-xs text-gray-400"><?php echo htmlspecialchars($job['employee_email']); ?></div>
                                        <?php else: ?>
                                            <span class="text-gray-400">Unassigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($job['category']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">$<?php echo number_format($job['amount'], 2); ?></td>
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
                                            <a href="job-details?id=<?php echo $job['id']; ?>" 
                                               class="text-blue-600 hover:text-blue-900">View</a>
                                            <?php if ($job['status'] == 'pending'): ?>
                                                <a href="edit-job?id=<?php echo $job['id']; ?>" 
                                                   class="text-green-600 hover:text-green-900">Edit</a>
                                                <button onclick="cancelJob(<?php echo $job['id']; ?>)" 
                                                        class="text-red-600 hover:text-red-900">Cancel</button>
                                            <?php endif; ?>
                                            <?php if ($job['status'] == 'completed'): ?>
                                                <a href="rate-job?id=<?php echo $job['id']; ?>" 
                                                   class="text-purple-600 hover:text-purple-900">Rate</a>
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

    <script>
        // Search and filter functionality
        document.getElementById('searchInput').addEventListener('input', filterJobs);
        document.getElementById('statusFilter').addEventListener('change', filterJobs);
        document.getElementById('categoryFilter').addEventListener('change', filterJobs);
        
        function filterJobs() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;
            const categoryFilter = document.getElementById('categoryFilter').value;
            const rows = document.querySelectorAll('.job-row');
            
            rows.forEach(row => {
                const title = row.querySelector('td:first-child').textContent.toLowerCase();
                const status = row.getAttribute('data-status');
                const category = row.getAttribute('data-category');
                
                const matchesSearch = title.includes(searchTerm);
                const matchesStatus = !statusFilter || status === statusFilter;
                const matchesCategory = !categoryFilter || category === categoryFilter;
                
                if (matchesSearch && matchesStatus && matchesCategory) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
        
        function cancelJob(jobId) {
            if (confirm('Are you sure you want to cancel this job?')) {
                fetch('cancel-job', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        job_id: jobId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Job cancelled successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while cancelling the job.');
                });
            }
        }
    </script>
</body>
</html> 