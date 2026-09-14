<?php
session_start();

require_once '../includes/db.php';
require_once '../includes/category-mapping.php';

// Check if employee is logged in
if (!isset($_SESSION['employ'])) {
    header('Location: ../login');
    exit();
}

$employeeId = $_SESSION['employ'];

try {
    $pdo = new Database();
    $conn = $pdo->open();
    
    // Get employee details to match job categories
    $stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->execute([$employeeId]);
    $employee = $stmt->fetch();
    
    // Get employee's job categories
    $employeeJobCategories = explode(',', $employee['job_categories']);
    $employeeJobCategories = array_map('trim', $employeeJobCategories);
    
    // Get available jobs (pending status, no employee assigned, matching job categories)
    $placeholders = str_repeat('?,', count($employeeJobCategories) - 1) . '?';
    $stmt = $conn->prepare("SELECT j.*, c.name as customer_name, c.phone as customer_phone
                           FROM jobs j 
                           LEFT JOIN customers c ON j.customer_id = c.id 
                           WHERE j.status = 'pending' 
                           AND j.employee_id IS NULL 
                           AND j.category IN ($placeholders)
                           ORDER BY j.created_at DESC");
    $stmt->execute($employeeJobCategories);
    $availableJobs = $stmt->fetchAll();
    
    // Get jobs the employee has already applied for
    $stmt = $conn->prepare("SELECT ja.job_id, ja.status as application_status, ja.proposed_amount
                           FROM job_applications ja
                           WHERE ja.employee_id = ?");
    $stmt->execute([$employeeId]);
    $myApplications = $stmt->fetchAll();
    
    // Create a map of applied jobs
    $appliedJobs = [];
    foreach ($myApplications as $app) {
        $appliedJobs[$app['job_id']] = $app;
    }
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Jobs | LaboTech Employee</title>
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
                <h1 class="text-3xl font-bold text-gray-900">Available Jobs</h1>
                <p class="text-gray-600">Your Job Categories: <span class="font-semibold"><?php echo htmlspecialchars($employee['job_categories']); ?></span></p>
                <p class="text-sm text-gray-500">Matching Job Categories: <?php echo implode(', ', $employeeJobCategories); ?></p>
            </div>
            <a href="index" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                Back to Dashboard
            </a>
        </div>

        <!-- Search and Filter -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <div class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <input type="text" id="searchInput" placeholder="Search jobs by title or location..." 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex gap-2">
                    <select id="priorityFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Priorities</option>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                    <select id="locationFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Locations</option>
                        <!-- Will be populated dynamically -->
                    </select>
                </div>
            </div>
        </div>

        <!-- Available Jobs -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold">Available Jobs (<?php echo count($availableJobs); ?>)</h2>
            </div>
            
            <?php if (!empty($availableJobs)): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Job Details</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Posted</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="jobsTableBody">
                            <?php foreach ($availableJobs as $job): ?>
                                <tr class="job-row" data-priority="<?php echo $job['priority']; ?>" data-location="<?php echo $job['location']; ?>">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($job['title']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars(substr($job['description'], 0, 100)) . '...'; ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($job['customer_name'] ?? 'Unknown'); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($job['customer_phone'] ?? ''); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($job['location']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">$<?php echo number_format($job['amount'], 2); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php 
                                            switch($job['priority']) {
                                                case 'urgent': echo 'bg-red-100 text-red-800'; break;
                                                case 'high': echo 'bg-orange-100 text-orange-800'; break;
                                                case 'medium': echo 'bg-yellow-100 text-yellow-800'; break;
                                                case 'low': echo 'bg-green-100 text-green-800'; break;
                                                default: echo 'bg-gray-100 text-gray-800';
                                            }
                                            ?>">
                                            <?php echo ucfirst($job['priority'] ?? 'Medium'); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('M d, Y', strtotime($job['created_at'])); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <a href="job-details?id=<?php echo $job['id']; ?>" 
                                               class="text-blue-600 hover:text-blue-900">View</a>
                                            <?php if (isset($appliedJobs[$job['id']])): ?>
                                                <span class="text-gray-400">Applied</span>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    <?php 
                                                    switch($appliedJobs[$job['id']]['application_status']) {
                                                        case 'accepted': echo 'bg-green-100 text-green-800'; break;
                                                        case 'rejected': echo 'bg-red-100 text-red-800'; break;
                                                        case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                                        default: echo 'bg-gray-100 text-gray-800';
                                                    }
                                                    ?>">
                                                    <?php echo ucfirst($appliedJobs[$job['id']]['application_status']); ?>
                                                </span>
                                            <?php else: ?>
                                                <button onclick="applyForJob(<?php echo $job['id']; ?>, '<?php echo htmlspecialchars($job['title']); ?>', <?php echo $job['amount']; ?>)" 
                                                        class="text-green-600 hover:text-green-900">Apply</button>
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2V6"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No available jobs</h3>
                    <p class="text-gray-500 mb-4">There are no jobs available in your category at the moment.</p>
                    <a href="index" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        Back to Dashboard
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Apply for Job Modal -->
    <div id="applyModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Apply for Job</h3>
                <p class="text-sm text-gray-600 mb-4" id="jobTitle"></p>
                <form id="applyForm">
                    <input type="hidden" id="jobId" name="job_id">
                    <div class="mb-4">
                        <label for="proposedAmount" class="block text-sm font-medium text-gray-700 mb-2">Proposed Amount ($)</label>
                        <input type="number" id="proposedAmount" name="proposed_amount" step="0.01" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div class="mb-4">
                        <label for="message" class="block text-sm font-medium text-gray-700 mb-2">Message (Optional)</label>
                        <textarea id="message" name="message" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                  placeholder="Tell the customer why you're the best fit for this job..."></textarea>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeApplyModal()" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
                            Cancel
                        </button>
                        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                            Submit Application
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Populate location filter
        const locations = [...new Set(Array.from(document.querySelectorAll('.job-row')).map(row => row.getAttribute('data-location')))];
        const locationFilter = document.getElementById('locationFilter');
        locations.forEach(location => {
            const option = document.createElement('option');
            option.value = location;
            option.textContent = location;
            locationFilter.appendChild(option);
        });
        
        // Search and filter functionality
        document.getElementById('searchInput').addEventListener('input', filterJobs);
        document.getElementById('priorityFilter').addEventListener('change', filterJobs);
        document.getElementById('locationFilter').addEventListener('change', filterJobs);
        
        function filterJobs() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const priorityFilter = document.getElementById('priorityFilter').value;
            const locationFilter = document.getElementById('locationFilter').value;
            const rows = document.querySelectorAll('.job-row');
            
            rows.forEach(row => {
                const title = row.querySelector('td:first-child').textContent.toLowerCase();
                const priority = row.getAttribute('data-priority');
                const location = row.getAttribute('data-location');
                
                const matchesSearch = title.includes(searchTerm);
                const matchesPriority = !priorityFilter || priority === priorityFilter;
                const matchesLocation = !locationFilter || location === locationFilter;
                
                if (matchesSearch && matchesPriority && matchesLocation) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
        
        function applyForJob(jobId, jobTitle, jobAmount) {
            document.getElementById('jobId').value = jobId;
            document.getElementById('jobTitle').textContent = jobTitle;
            document.getElementById('proposedAmount').value = jobAmount;
            document.getElementById('applyModal').classList.remove('hidden');
        }
        
        function closeApplyModal() {
            document.getElementById('applyModal').classList.add('hidden');
            document.getElementById('applyForm').reset();
        }
        
        // Handle application form submission
        document.getElementById('applyForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = {
                job_id: formData.get('job_id'),
                proposed_amount: formData.get('proposed_amount'),
                message: formData.get('message')
            };
            
            fetch('apply-for-job', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Application submitted successfully!');
                    closeApplyModal();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while submitting your application.');
            });
        });
    </script>
</body>
</html> 