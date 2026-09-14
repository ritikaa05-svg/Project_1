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
    
    // Get all employees with their statistics
    $stmt = $conn->query("SELECT e.*, 
                         COUNT(j.id) as total_jobs,
                         SUM(CASE WHEN j.status = 'completed' THEN 1 ELSE 0 END) as completed_jobs,
                         AVG(r.rating) as avg_rating
                         FROM employees e 
                         LEFT JOIN jobs j ON e.id = j.employee_id 
                         LEFT JOIN reviews r ON e.id = r.employee_id
                         GROUP BY e.id 
                         ORDER BY e.created_at DESC");
    $employees = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Management | LaboTech Admin</title>
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
                <h1 class="text-3xl font-bold text-gray-900">Employee Management</h1>
                <p class="text-gray-600">Manage all registered employees</p>
            </div>
            <a href="index" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                Back to Dashboard
            </a>
        </div>

        <!-- Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <?php
            $totalEmployees = count($employees);
            $activeEmployees = count(array_filter($employees, function($e) { return $e['status'] == 'active'; }));
            $pendingEmployees = count(array_filter($employees, function($e) { return $e['status'] == 'pending'; }));
            $suspendedEmployees = count(array_filter($employees, function($e) { return $e['status'] == 'suspended'; }));
            ?>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="text-3xl font-bold text-blue-600"><?php echo $totalEmployees; ?></div>
                <div class="text-sm text-gray-500">Total Employees</div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="text-3xl font-bold text-green-600"><?php echo $activeEmployees; ?></div>
                <div class="text-sm text-gray-500">Active</div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="text-3xl font-bold text-yellow-600"><?php echo $pendingEmployees; ?></div>
                <div class="text-sm text-gray-500">Pending</div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="text-3xl font-bold text-red-600"><?php echo $suspendedEmployees; ?></div>
                <div class="text-sm text-gray-500">Suspended</div>
            </div>
        </div>

        <!-- Employees Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold">All Employees</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rate</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jobs</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rating</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($employees as $employee): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                                <span class="text-sm font-medium text-gray-700">
                                                    <?php echo strtoupper(substr($employee['name'], 0, 1)); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($employee['name']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($employee['email']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($employee['job_categories']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($employee['location']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">$<?php echo number_format($employee['hourly_rate'], 2); ?>/hr</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo $employee['total_jobs']; ?> total<br>
                                    <span class="text-green-600"><?php echo $employee['completed_jobs']; ?> completed</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php if ($employee['avg_rating']): ?>
                                        <div class="flex items-center">
                                            <span class="text-yellow-400">★</span>
                                            <span class="ml-1"><?php echo number_format($employee['avg_rating'], 1); ?></span>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-gray-400">No ratings</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php 
                                        switch($employee['status']) {
                                            case 'active': echo 'bg-green-100 text-green-800'; break;
                                            case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                            case 'suspended': echo 'bg-red-100 text-red-800'; break;
                                            default: echo 'bg-gray-100 text-gray-800';
                                        }
                                        ?>">
                                        <?php echo ucfirst($employee['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <button onclick="viewEmployee(<?php echo $employee['id']; ?>)" 
                                                class="text-blue-600 hover:text-blue-900">View</button>
                                        <?php if ($employee['status'] == 'pending'): ?>
                                            <button onclick="approveEmployee(<?php echo $employee['id']; ?>)" 
                                                    class="text-green-600 hover:text-green-900">Approve</button>
                                            <button onclick="rejectEmployee(<?php echo $employee['id']; ?>)" 
                                                    class="text-red-600 hover:text-red-900">Reject</button>
                                        <?php elseif ($employee['status'] == 'active'): ?>
                                            <button onclick="suspendEmployee(<?php echo $employee['id']; ?>)" 
                                                    class="text-orange-600 hover:text-orange-900">Suspend</button>
                                        <?php elseif ($employee['status'] == 'suspended'): ?>
                                            <button onclick="activateEmployee(<?php echo $employee['id']; ?>)" 
                                                    class="text-green-600 hover:text-green-900">Activate</button>
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
        function viewEmployee(id) {
            // Implement view employee details
            alert('View employee ' + id);
        }
        
        function approveEmployee(id) {
            if (confirm('Are you sure you want to approve this employee?')) {
                // Implement approve functionality
                alert('Employee ' + id + ' approved');
            }
        }
        
        function rejectEmployee(id) {
            if (confirm('Are you sure you want to reject this employee?')) {
                // Implement reject functionality
                alert('Employee ' + id + ' rejected');
            }
        }
        
        function suspendEmployee(id) {
            if (confirm('Are you sure you want to suspend this employee?')) {
                // Implement suspend functionality
                alert('Employee ' + id + ' suspended');
            }
        }
        
        function activateEmployee(id) {
            if (confirm('Are you sure you want to activate this employee?')) {
                // Implement activate functionality
                alert('Employee ' + id + ' activated');
            }
        }
    </script>
</body>
</html> 