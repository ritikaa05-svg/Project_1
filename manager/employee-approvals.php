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
    
    // Get all pending employees
    $stmt = $conn->query("SELECT * FROM employees WHERE status = 'pending' ORDER BY created_at DESC");
    $pendingEmployees = $stmt->fetchAll();
    
    // Get recently approved/rejected employees
    $stmt = $conn->query("SELECT * FROM employees WHERE status IN ('active', 'inactive') ORDER BY updated_at DESC LIMIT 10");
    $recentActions = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Approvals | LaboTech Manager</title>
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
                <h1 class="text-3xl font-bold text-gray-900">Employee Approvals</h1>
                <p class="text-gray-600">Review and approve employee applications</p>
            </div>
            <a href="index" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                Back to Dashboard
            </a>
        </div>

        <!-- Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="text-3xl font-bold text-yellow-600"><?php echo count($pendingEmployees); ?></div>
                <div class="text-sm text-gray-500">Pending Approvals</div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="text-3xl font-bold text-green-600"><?php echo count(array_filter($recentActions, function($e) { return $e['status'] == 'active'; })); ?></div>
                <div class="text-sm text-gray-500">Recently Approved</div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="text-3xl font-bold text-red-600"><?php echo count(array_filter($recentActions, function($e) { return $e['status'] == 'inactive'; })); ?></div>
                <div class="text-sm text-gray-500">Recently Rejected</div>
            </div>
        </div>

        <!-- Pending Approvals -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold">Pending Employee Approvals</h2>
            </div>
            <?php if (!empty($pendingEmployees)): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee Details</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact Info</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Skills & Rate</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applied</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($pendingEmployees as $employee): ?>
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($employee['name']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($employee['job_categories']); ?></div>
                                        <div class="text-xs text-gray-400"><?php echo htmlspecialchars($employee['location']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($employee['email']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($employee['phone']); ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-semibold text-green-600">$<?php echo number_format($employee['hourly_rate'], 2); ?>/hr</div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars(substr($employee['skills'], 0, 50)) . '...'; ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('M d, Y', strtotime($employee['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <button onclick="viewEmployee(<?php echo $employee['id']; ?>)" 
                                                    class="text-blue-600 hover:text-blue-900">View</button>
                                            <button onclick="approveEmployee(<?php echo $employee['id']; ?>)" 
                                                    class="text-green-600 hover:text-green-900">Approve</button>
                                            <button onclick="rejectEmployee(<?php echo $employee['id']; ?>)" 
                                                    class="text-red-600 hover:text-red-900">Reject</button>
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No pending approvals</h3>
                    <p class="text-gray-500">All employee applications have been reviewed.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Actions -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold">Recent Actions</h2>
            </div>
            <?php if (!empty($recentActions)): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action Date</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($recentActions as $employee): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($employee['name']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($employee['email']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($employee['job_categories']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php echo $employee['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo ucfirst($employee['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('M d, Y H:i', strtotime($employee['updated_at'])); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-gray-500 text-center py-8">No recent actions found.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Employee Details Modal -->
    <div id="employeeModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div id="employeeDetails" class="text-center">
                    <!-- Employee details will be loaded here -->
                </div>
                <div class="flex justify-end space-x-3 mt-4">
                    <button onclick="closeModal()" class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function viewEmployee(employeeId) {
            // Load employee details via AJAX
            fetch('get-employee-details?id=' + employeeId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const employee = data.employee;
                        document.getElementById('employeeDetails').innerHTML = `
                            <h3 class="text-lg font-medium text-gray-900 mb-4">${employee.name}</h3>
                            <div class="text-left space-y-2">
                                <p><strong>Email:</strong> ${employee.email}</p>
                                <p><strong>Phone:</strong> ${employee.phone}</p>
                                <p><strong>Category:</strong> ${employee.job_categories}</p>
                                <p><strong>Location:</strong> ${employee.location}</p>
                                <p><strong>Hourly Rate:</strong> $${parseFloat(employee.hourly_rate).toFixed(2)}/hr</p>
                                <p><strong>Skills:</strong> ${employee.skills}</p>
                                <p><strong>Applied:</strong> ${new Date(employee.created_at).toLocaleDateString()}</p>
                            </div>
                        `;
                        document.getElementById('employeeModal').classList.remove('hidden');
                    } else {
                        alert('Error loading employee details');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading employee details');
                });
        }
        
        function closeModal() {
            document.getElementById('employeeModal').classList.add('hidden');
        }
        
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