<?php
session_start();

require_once '../includes/db.php';

// Check if employee is logged in
if (!isset($_SESSION['employ'])) {
    header('Location: ../login');
    exit();
}

// Check if job ID is provided
if (!isset($_GET['id'])) {
    header('Location: index');
    exit();
}

$jobId = $_GET['id'];
$employeeId = $_SESSION['employ'];

try {
    $pdo = new Database();
    $conn = $pdo->open();
    
    // Get job details with customer information
    $stmt = $conn->prepare("SELECT j.*, c.name as customer_name, c.phone as customer_phone, c.email as customer_email
                           FROM jobs j 
                           LEFT JOIN customers c ON j.customer_id = c.id 
                           WHERE j.id = ?");
    $stmt->execute([$jobId]);
    $job = $stmt->fetch();
    
    if (!$job) {
        header('Location: index');
        exit();
    }
    
    // Check if this job is assigned to the current employee
    $isAssigned = ($job['employee_id'] == $employeeId);
    
    // Get employee details
    $stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->execute([$employeeId]);
    $employee = $stmt->fetch();
    
    // Get job applications for this job
    $stmt = $conn->prepare("SELECT ja.*, e.name as employee_name, e.phone as employee_phone
                           FROM job_applications ja
                           LEFT JOIN employees e ON ja.employee_id = e.id
                           WHERE ja.job_id = ?
                           ORDER BY ja.created_at DESC");
    $stmt->execute([$jobId]);
    $applications = $stmt->fetchAll();
    
    // Check if current employee has applied for this job
    $hasApplied = false;
    $myApplication = null;
    foreach ($applications as $app) {
        if ($app['employee_id'] == $employeeId) {
            $hasApplied = true;
            $myApplication = $app;
            break;
        }
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
    <title>Job Details | LaboTech Employee</title>
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

    <div class="max-w-4xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Job Details</h1>
                <p class="text-gray-600">View detailed information about this job</p>
            </div>
            <a href="index" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                Back to Dashboard
            </a>
        </div>

        <!-- Job Information -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <div class="flex justify-between items-start mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($job['title']); ?></h2>
                    <p class="text-gray-600"><?php echo htmlspecialchars($job['category']); ?> • <?php echo htmlspecialchars($job['location']); ?></p>
                </div>
                <div class="text-right">
                    <div class="text-3xl font-bold text-green-600">$<?php echo number_format($job['amount'], 2); ?></div>
                    <div class="text-sm text-gray-500">Job Amount</div>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <h3 class="text-lg font-semibold mb-3">Job Description</h3>
                    <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($job['description'])); ?></p>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold mb-3">Job Details</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Status:</span>
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
                        <div class="flex justify-between">
                            <span class="text-gray-600">Priority:</span>
                            <span class="font-medium"><?php echo ucfirst($job['priority'] ?? 'Medium'); ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Posted:</span>
                            <span class="font-medium"><?php echo date('M d, Y', strtotime($job['created_at'])); ?></span>
                        </div>
                        <?php if ($job['scheduled_date']): ?>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Scheduled:</span>
                            <span class="font-medium"><?php echo date('M d, Y', strtotime($job['scheduled_date'])); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Customer Information -->
            <div class="border-t pt-6">
                <h3 class="text-lg font-semibold mb-3">Customer Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <span class="text-gray-600">Name:</span>
                        <div class="font-medium"><?php echo htmlspecialchars($job['customer_name'] ?? 'Unknown'); ?></div>
                    </div>
                    <div>
                        <span class="text-gray-600">Phone:</span>
                        <div class="font-medium"><?php echo htmlspecialchars($job['customer_phone'] ?? 'Not provided'); ?></div>
                    </div>
                    <div>
                        <span class="text-gray-600">Email:</span>
                        <div class="font-medium"><?php echo htmlspecialchars($job['customer_email'] ?? 'Not provided'); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <?php if ($isAssigned): ?>
            <!-- Job is assigned to this employee -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
                <h3 class="text-lg font-semibold text-blue-900 mb-3">Job Actions</h3>
                <div class="flex space-x-4">
                    <?php if ($job['status'] == 'active'): ?>
                        <button onclick="updateJobStatus(<?php echo $job['id']; ?>, 'completed')" 
                                class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition-colors">
                            Mark as Completed
                        </button>
                    <?php endif; ?>
                    <a href="index" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        Back to Dashboard
                    </a>
                </div>
            </div>
        <?php elseif ($job['status'] == 'pending' && !$hasApplied): ?>
            <!-- Job is available and employee hasn't applied -->
            <div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-8">
                <h3 class="text-lg font-semibold text-green-900 mb-3">Apply for this Job</h3>
                <p class="text-green-700 mb-4">This job is available and matches your category. You can apply for it.</p>
                <button onclick="applyForJob(<?php echo $job['id']; ?>)" 
                        class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition-colors">
                    Apply Now
                </button>
            </div>
        <?php elseif ($hasApplied): ?>
            <!-- Employee has already applied -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-8">
                <h3 class="text-lg font-semibold text-yellow-900 mb-3">Application Status</h3>
                <div class="space-y-2">
                    <p class="text-yellow-700">You have already applied for this job.</p>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Your Proposed Amount:</span>
                        <span class="font-medium">$<?php echo number_format($myApplication['proposed_amount'], 2); ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Application Status:</span>
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                            <?php 
                            switch($myApplication['status']) {
                                case 'accepted': echo 'bg-green-100 text-green-800'; break;
                                case 'rejected': echo 'bg-red-100 text-red-800'; break;
                                case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                default: echo 'bg-gray-100 text-gray-800';
                            }
                            ?>">
                            <?php echo ucfirst($myApplication['status']); ?>
                        </span>
                    </div>
                    <?php if ($myApplication['message']): ?>
                        <div>
                            <span class="text-gray-600">Your Message:</span>
                            <p class="text-gray-700 mt-1"><?php echo htmlspecialchars($myApplication['message']); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <!-- Job is not available -->
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-6 mb-8">
                <h3 class="text-lg font-semibold text-gray-900 mb-3">Job Status</h3>
                <p class="text-gray-700">This job is not available for application at the moment.</p>
            </div>
        <?php endif; ?>

        <!-- Applications (if any) -->
        <?php if (!empty($applications)): ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold">Applications (<?php echo count($applications); ?>)</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Proposed Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applied</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($applications as $application): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($application['employee_name']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($application['employee_phone'] ?? ''); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">$<?php echo number_format($application['proposed_amount'] ?? 0, 2); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php 
                                            switch($application['status']) {
                                                case 'accepted': echo 'bg-green-100 text-green-800'; break;
                                                case 'rejected': echo 'bg-red-100 text-red-800'; break;
                                                case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                                default: echo 'bg-gray-100 text-gray-800';
                                            }
                                            ?>">
                                            <?php echo ucfirst($application['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('M d, Y', strtotime($application['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Apply for Job Modal -->
    <div id="applyModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Apply for Job</h3>
                <form id="applyForm">
                    <input type="hidden" id="jobId" name="job_id" value="<?php echo $job['id']; ?>">
                    <div class="mb-4">
                        <label for="proposedAmount" class="block text-sm font-medium text-gray-700 mb-2">Proposed Amount ($)</label>
                        <input type="number" id="proposedAmount" name="proposed_amount" step="0.01" required
                               value="<?php echo $job['amount']; ?>"
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
        
        function applyForJob(jobId) {
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