<?php
session_start();

require_once '../includes/db.php';
require_once '../includes/category-mapping.php';

// Check if manager is logged in
if (!isset($_SESSION['manager'])) {
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
    
    // Get job statistics
    $stmt = $conn->query("SELECT 
                         COUNT(*) as total_jobs,
                         SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_jobs,
                         SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_jobs,
                         SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_jobs,
                         SUM(amount) as total_revenue
                         FROM jobs");
    $jobStats = $stmt->fetch();
    
    // Get approved employees for assignment
    $stmt = $conn->query("SELECT id, name, job_categories, location, phone FROM employees WHERE status IN ('active', 'approved') ORDER BY name");
    $employees = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}

// Get job categories for filter
$jobCategories = CategoryMapping::getJobCategories();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Management | LaboTech Manager</title>
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
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Job Management</h1>
                <p class="text-gray-600">Monitor and manage job assignments</p>
            </div>
            <a href="index" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                Back to Dashboard
            </a>
        </div>

        <!-- Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="text-3xl font-bold text-blue-600"><?php echo $jobStats['total_jobs'] ?? 0; ?></div>
                <div class="text-sm text-gray-500">Total Jobs</div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="text-3xl font-bold text-yellow-600"><?php echo $jobStats['pending_jobs'] ?? 0; ?></div>
                <div class="text-sm text-gray-500">Pending</div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="text-3xl font-bold text-green-600"><?php echo $jobStats['active_jobs'] ?? 0; ?></div>
                <div class="text-sm text-gray-500">Active</div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="text-3xl font-bold text-purple-600"><?php echo $jobStats['completed_jobs'] ?? 0; ?></div>
                <div class="text-sm text-gray-500">Completed</div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="text-3xl font-bold text-green-600">$<?php echo number_format($jobStats['total_revenue'] ?? 0, 2); ?></div>
                <div class="text-sm text-gray-500">Total Revenue</div>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <div class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <input type="text" id="searchInput" placeholder="Search jobs..." 
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
                        <?php foreach ($jobCategories as $category): ?>
                            <option value="<?php echo $category; ?>"><?php echo $category; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Jobs Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold">All Jobs</h2>
            </div>
            <?php if (!empty($jobs)): ?>
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
                        <tbody class="bg-white divide-y divide-gray-200" id="jobsTableBody">
                            <?php foreach ($jobs as $job): ?>
                                <tr class="job-row" data-status="<?php echo $job['status']; ?>" data-category="<?php echo $job['category']; ?>">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($job['title']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars(substr($job['description'], 0, 100)) . '...'; ?></div>
                                        <div class="text-xs text-gray-400"><?php echo htmlspecialchars($job['location']); ?></div>
                                        <div class="text-xs text-gray-400"><?php echo htmlspecialchars($job['category']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($job['customer_name'] ?? 'Unknown'); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($job['customer_phone'] ?? ''); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($job['employee_name']): ?>
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($job['employee_name']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($job['employee_phone'] ?? ''); ?></div>
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
                                            <button onclick="viewJobDetails(<?php echo $job['id']; ?>)" 
                                                    class="text-blue-600 hover:text-blue-900">View</button>
                                            <?php if ($job['status'] == 'pending'): ?>
                                                <button onclick="assignJob(<?php echo $job['id']; ?>, '<?php echo htmlspecialchars($job['title']); ?>', '<?php echo $job['category']; ?>', <?php echo $job['amount']; ?>)" 
                                                        class="text-green-600 hover:text-green-900">Assign</button>
                                            <?php endif; ?>
                                            <?php if ($job['status'] == 'active'): ?>
                                                <button onclick="updateJobStatus(<?php echo $job['id']; ?>, 'completed')" 
                                                        class="text-purple-600 hover:text-purple-900">Complete</button>
                                            <?php endif; ?>
                                            <?php if (in_array($job['status'], ['pending', 'active'])): ?>
                                                <button onclick="updateJobStatus(<?php echo $job['id']; ?>, 'cancelled')" 
                                                        class="text-red-600 hover:text-red-900">Cancel</button>
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
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No jobs found</h3>
                    <p class="text-gray-500">There are no jobs in the system yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Job Details Modal -->
    <div id="jobDetailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div id="jobDetailsContent" class="text-left">
                    <!-- Job details will be loaded here -->
                </div>
                <div class="flex justify-end mt-4">
                    <button onclick="closeJobDetailsModal()" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Assign Job Modal -->
    <div id="assignJobModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Assign Job</h3>
                <p class="text-sm text-gray-600 mb-4" id="assignJobTitle"></p>
                
                <!-- Debug Info -->
                <div id="debugInfo" class="bg-yellow-50 border border-yellow-200 rounded p-3 mb-4 text-xs">
                    <strong>Debug Info:</strong><br>
                    <span id="debugJobCategory"></span><br>
                    <span id="debugRequiredCategory"></span><br>
                    <span id="debugAvailableEmployees"></span>
                </div>
                
                <form id="assignJobForm">
                    <input type="hidden" id="assignJobId" name="job_id">
                    <div class="mb-4">
                        <label for="assignEmployeeId" class="block text-sm font-medium text-gray-700 mb-2">Select Employee</label>
                        <select id="assignEmployeeId" name="employee_id" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Choose an employee...</option>
                            <?php foreach ($employees as $employee): ?>
                                <option value="<?php echo $employee['id']; ?>" data-category="<?php echo $employee['job_categories']; ?>">
                                    <?php echo htmlspecialchars($employee['name']); ?> (<?php echo htmlspecialchars($employee['job_categories']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="assignAmount" class="block text-sm font-medium text-gray-700 mb-2">Amount ($)</label>
                        <input type="number" id="assignAmount" name="amount" step="0.01" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeAssignJobModal()" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
                            Cancel
                        </button>
                        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                            Assign Job
                        </button>
                    </div>
                </form>
            </div>
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
        
        function viewJobDetails(jobId) {
            fetch('get-job-details?id=' + jobId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const job = data.job;
                        document.getElementById('jobDetailsContent').innerHTML = `
                            <h3 class="text-xl font-bold text-gray-900 mb-4">${job.title}</h3>
                            <div class="space-y-4">
                                <div>
                                    <h4 class="font-semibold text-gray-900">Description</h4>
                                    <p class="text-gray-700">${job.description}</p>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <h4 class="font-semibold text-gray-900">Category</h4>
                                        <p class="text-gray-700">${job.category}</p>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-900">Location</h4>
                                        <p class="text-gray-700">${job.location}</p>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-900">Amount</h4>
                                        <p class="text-gray-700">$${parseFloat(job.amount).toFixed(2)}</p>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-900">Status</h4>
                                        <p class="text-gray-700">${job.status.charAt(0).toUpperCase() + job.status.slice(1)}</p>
                                    </div>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900">Customer</h4>
                                    <p class="text-gray-700">${job.customer_name || 'Unknown'}</p>
                                    <p class="text-gray-500">${job.customer_phone || ''}</p>
                                </div>
                                ${job.employee_name ? `
                                <div>
                                    <h4 class="font-semibold text-gray-900">Assigned Employee</h4>
                                    <p class="text-gray-700">${job.employee_name}</p>
                                    <p class="text-gray-500">${job.employee_phone || ''}</p>
                                </div>
                                ` : ''}
                                <div>
                                    <h4 class="font-semibold text-gray-900">Created</h4>
                                    <p class="text-gray-700">${new Date(job.created_at).toLocaleDateString()}</p>
                                </div>
                            </div>
                        `;
                        document.getElementById('jobDetailsModal').classList.remove('hidden');
                    } else {
                        alert('Error loading job details');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading job details');
                });
        }
        
        function closeJobDetailsModal() {
            document.getElementById('jobDetailsModal').classList.add('hidden');
        }
        
        function assignJob(jobId, jobTitle, jobCategory, jobAmount) {
            document.getElementById('assignJobId').value = jobId;
            document.getElementById('assignJobTitle').textContent = jobTitle;
            document.getElementById('assignAmount').value = jobAmount;
            
            // Update debug info
            document.getElementById('debugJobCategory').textContent = `Job Category: ${jobCategory}`;
            document.getElementById('debugRequiredCategory').textContent = `Required Job Category: ${jobCategory}`;
            
            // Filter employees by job category
            const employeeSelect = document.getElementById('assignEmployeeId');
            const options = employeeSelect.querySelectorAll('option');
            let visibleCount = 0;
            let totalCount = 0;
            
            options.forEach(option => {
                if (option.value === '') return; // Skip placeholder
                totalCount++;
                const employeeCategories = option.getAttribute('data-category');
                if (employeeCategories && employeeCategories.includes(jobCategory)) {
                    option.style.display = '';
                    visibleCount++;
                } else {
                    option.style.display = 'none';
                }
            });
            
            document.getElementById('debugAvailableEmployees').textContent = `Available Employees: ${visibleCount} of ${totalCount} total`;
            
            document.getElementById('assignJobModal').classList.remove('hidden');
        }
        
        function closeAssignJobModal() {
            document.getElementById('assignJobModal').classList.add('hidden');
            document.getElementById('assignJobForm').reset();
            
            // Reset employee options visibility
            const options = document.getElementById('assignEmployeeId').querySelectorAll('option');
            options.forEach(option => option.style.display = '');
        }
        
        // Handle assign job form submission
        document.getElementById('assignJobForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = {
                job_id: formData.get('job_id'),
                employee_id: formData.get('employee_id'),
                amount: formData.get('amount')
            };
            
            fetch('assign-job', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Job assigned successfully!');
                    closeAssignJobModal();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while assigning the job.');
            });
        });
        
        function updateJobStatus(jobId, status) {
            if (confirm('Are you sure you want to update this job status to ' + status + '?')) {
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