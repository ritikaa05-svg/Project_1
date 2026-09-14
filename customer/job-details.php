<?php
session_start();

require_once '../includes/db.php';

// Check if customer is logged in
if (!isset($_SESSION['customer'])) {
    header('Location: ../login');
    exit();
}

// Check if job ID is provided
if (!isset($_GET['id'])) {
    header('Location: index');
    exit();
}

$jobId = $_GET['id'];
$customerId = $_SESSION['customer'];

try {
    $pdo = new Database();
    $conn = $pdo->open();
    
    // Get job details with customer information
    $stmt = $conn->prepare("SELECT j.*, c.name as customer_name, c.phone as customer_phone, c.email as customer_email
                           FROM jobs j 
                           LEFT JOIN customers c ON j.customer_id = c.id 
                           WHERE j.id = ? AND j.customer_id = ?");
    $stmt->execute([$jobId, $customerId]);
    $job = $stmt->fetch();
    
    if (!$job) {
        header('Location: index');
        exit();
    }
    
    // Get assigned employee details
    $stmt = $conn->prepare("SELECT e.* FROM employees e 
                           WHERE e.id = ?");
    $stmt->execute([$job['employee_id']]);
    $assignedEmployee = $stmt->fetch();
    
    // Get job applications for this job
    $stmt = $conn->prepare("SELECT ja.*, e.name as employee_name, e.phone as employee_phone, e.email as employee_email, e.job_categories as employee_job_categories
                           FROM job_applications ja
                           LEFT JOIN employees e ON ja.employee_id = e.id
                           WHERE ja.job_id = ?
                           ORDER BY ja.created_at DESC");
    $stmt->execute([$jobId]);
    $applications = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Details | LaboTech Customer</title>
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

    <div class="max-w-4xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Job Details</h1>
                <p class="text-gray-600">View detailed information about your job request</p>
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
                                    case 'cancelled': echo 'bg-red-100 text-red-800'; break;
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
        </div>

        <!-- Assigned Professional -->
        <?php if ($assignedEmployee): ?>
            <div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-8">
                <h3 class="text-lg font-semibold text-green-900 mb-4">Assigned Professional</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <div class="text-sm font-medium text-gray-700">Name:</div>
                        <div class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($assignedEmployee['name']); ?></div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-700">Job Categories:</div>
                        <div class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($assignedEmployee['job_categories']); ?></div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-700">Phone:</div>
                        <div class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($assignedEmployee['phone']); ?></div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-700">Email:</div>
                        <div class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($assignedEmployee['email']); ?></div>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="text-sm font-medium text-gray-700">Location:</div>
                    <div class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($assignedEmployee['location']); ?></div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Applications (if job is pending) -->
        <?php if ($job['status'] == 'pending' && !empty($applications)): ?>
            <div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold">Professional Applications (<?php echo count($applications); ?>)</h3>
                    <p class="text-sm text-gray-600 mt-1">Review and select a professional for your job</p>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Professional</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Proposed Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Message</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applied</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($applications as $application): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($application['employee_name']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($application['employee_phone']); ?></div>
                                        <div class="text-xs text-gray-400"><?php echo htmlspecialchars($application['employee_email']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($application['employee_job_categories']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-green-600">$<?php echo number_format($application['proposed_amount'] ?? 0, 2); ?></td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">
                                            <?php if ($application['message']): ?>
                                                <?php echo htmlspecialchars(substr($application['message'], 0, 100)) . (strlen($application['message']) > 100 ? '...' : ''); ?>
                                            <?php else: ?>
                                                <span class="text-gray-400">No message</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('M d, Y', strtotime($application['created_at'])); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <?php if ($application['status'] == 'pending'): ?>
                                                <button onclick="acceptApplication(<?php echo $application['id']; ?>)" 
                                                        class="text-green-600 hover:text-green-900">Accept</button>
                                                <button onclick="rejectApplication(<?php echo $application['id']; ?>)" 
                                                        class="text-red-600 hover:text-red-900">Reject</button>
                                            <?php else: ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    <?php 
                                                    switch($application['status']) {
                                                        case 'accepted': echo 'bg-green-100 text-green-800'; break;
                                                        case 'rejected': echo 'bg-red-100 text-red-800'; break;
                                                        default: echo 'bg-gray-100 text-gray-800';
                                                    }
                                                    ?>">
                                                    <?php echo ucfirst($application['status']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php elseif ($job['status'] == 'pending' && empty($applications)): ?>
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-8">
                <h3 class="text-lg font-semibold text-yellow-900 mb-2">No Applications Yet</h3>
                <p class="text-yellow-700">Your job is posted and waiting for professionals to apply. You'll be notified when applications come in.</p>
            </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="flex justify-end space-x-4">
            <?php if ($job['status'] == 'pending'): ?>
                <a href="edit-job?id=<?php echo $job['id']; ?>" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                    Edit Job
                </a>
                <button onclick="cancelJob(<?php echo $job['id']; ?>)" class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 transition-colors">
                    Cancel Job
                </button>
            <?php endif; ?>
            <a href="job-history" class="bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                Back to Job History
            </a>
        </div>
    </div>

    <script>
        function acceptApplication(applicationId) {
            if (confirm('Are you sure you want to accept this application? This will assign the job to this professional.')) {
                fetch('accept-application', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        application_id: applicationId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Application accepted! The job has been assigned to the professional.');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while accepting the application.');
                });
            }
        }
        
        function rejectApplication(applicationId) {
            if (confirm('Are you sure you want to reject this application?')) {
                fetch('reject-application', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        application_id: applicationId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Application rejected successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while rejecting the application.');
                });
            }
        }
        
        function cancelJob(jobId) {
            if (confirm('Are you sure you want to cancel this job? This action cannot be undone.')) {
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
                        window.location.href = 'job-history';
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