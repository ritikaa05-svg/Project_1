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
    
    // Get all customers with their statistics
    $stmt = $conn->query("SELECT c.*, 
                         COUNT(j.id) as total_jobs,
                         SUM(CASE WHEN j.status = 'completed' THEN 1 ELSE 0 END) as completed_jobs,
                         SUM(j.amount) as total_spent
                         FROM customers c 
                         LEFT JOIN jobs j ON c.id = j.customer_id 
                         GROUP BY c.id 
                         ORDER BY c.created_at DESC");
    $customers = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Management | LaboTech Admin</title>
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
                <h1 class="text-3xl font-bold text-gray-900">Customer Management</h1>
                <p class="text-gray-600">Manage all registered customers</p>
            </div>
            <a href="index" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                Back to Dashboard
            </a>
        </div>

        <!-- Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <?php
            $totalCustomers = count($customers);
            $activeCustomers = count(array_filter($customers, function($c) { return $c['status'] == 'active'; }));
            $suspendedCustomers = count(array_filter($customers, function($c) { return $c['status'] == 'suspended'; }));
            $totalRevenue = array_sum(array_column($customers, 'total_spent'));
            ?>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="text-3xl font-bold text-blue-600"><?php echo $totalCustomers; ?></div>
                <div class="text-sm text-gray-500">Total Customers</div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="text-3xl font-bold text-green-600"><?php echo $activeCustomers; ?></div>
                <div class="text-sm text-gray-500">Active</div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="text-3xl font-bold text-red-600"><?php echo $suspendedCustomers; ?></div>
                <div class="text-sm text-gray-500">Suspended</div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="text-3xl font-bold text-purple-600">$<?php echo number_format($totalRevenue, 2); ?></div>
                <div class="text-sm text-gray-500">Total Revenue</div>
            </div>
        </div>

        <!-- Customers Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold">All Customers</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Jobs</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Spent</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center">
                                                <span class="text-sm font-medium text-gray-700">
                                                    <?php echo strtoupper(substr($customer['name'], 0, 1)); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($customer['name']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($customer['email']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($customer['phone']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($customer['location']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo $customer['total_jobs']; ?> total<br>
                                    <span class="text-green-600"><?php echo $customer['completed_jobs']; ?> completed</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">$<?php echo number_format($customer['total_spent'] ?? 0, 2); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php 
                                        switch($customer['status']) {
                                            case 'active': echo 'bg-green-100 text-green-800'; break;
                                            case 'suspended': echo 'bg-red-100 text-red-800'; break;
                                            default: echo 'bg-gray-100 text-gray-800';
                                        }
                                        ?>">
                                        <?php echo ucfirst($customer['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <button onclick="viewCustomer(<?php echo $customer['id']; ?>)" 
                                                class="text-blue-600 hover:text-blue-900">View</button>
                                        <?php if ($customer['status'] == 'active'): ?>
                                            <button onclick="suspendCustomer(<?php echo $customer['id']; ?>)" 
                                                    class="text-orange-600 hover:text-orange-900">Suspend</button>
                                        <?php elseif ($customer['status'] == 'suspended'): ?>
                                            <button onclick="activateCustomer(<?php echo $customer['id']; ?>)" 
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
        function viewCustomer(id) {
            // Implement view customer details
            alert('View customer ' + id);
        }
        
        function suspendCustomer(id) {
            if (confirm('Are you sure you want to suspend this customer?')) {
                // Implement suspend functionality
                alert('Customer ' + id + ' suspended');
            }
        }
        
        function activateCustomer(id) {
            if (confirm('Are you sure you want to activate this customer?')) {
                // Implement activate functionality
                alert('Customer ' + id + ' activated');
            }
        }
    </script>
</body>
</html> 