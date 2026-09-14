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
    
    // Get monthly statistics
    $stmt = $conn->query("SELECT 
                         MONTH(created_at) as month,
                         COUNT(*) as total_jobs,
                         SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_jobs,
                         SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_jobs,
                         SUM(amount) as total_revenue
                         FROM jobs 
                         WHERE YEAR(created_at) = YEAR(CURRENT_DATE())
                         GROUP BY MONTH(created_at)
                         ORDER BY month DESC");
    $monthlyStats = $stmt->fetchAll();
    
    // Get top performing employees
    $stmt = $conn->query("SELECT e.name, e.category, COUNT(j.id) as job_count, SUM(j.amount) as total_earnings
                         FROM employees e 
                         LEFT JOIN jobs j ON e.id = j.employee_id AND j.status = 'completed'
                         WHERE e.status = 'active'
                         GROUP BY e.id, e.name, e.category 
                         ORDER BY total_earnings DESC 
                         LIMIT 10");
    $topEmployees = $stmt->fetchAll();
    
    // Get customer statistics
    $stmt = $conn->query("SELECT c.name, COUNT(j.id) as job_count, SUM(j.amount) as total_spent
                         FROM customers c 
                         LEFT JOIN jobs j ON c.id = j.customer_id
                         WHERE c.status = 'active'
                         GROUP BY c.id, c.name 
                         ORDER BY total_spent DESC 
                         LIMIT 10");
    $topCustomers = $stmt->fetchAll();
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports | LaboTech Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/webp" href="../assets/logo_without_bg.png">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <h1 class="text-3xl font-bold text-gray-900">Reports & Analytics</h1>
                <p class="text-gray-600">Comprehensive insights and statistics</p>
            </div>
            <div class="flex space-x-4">
                <button onclick="exportReport()" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors">
                    Export Report
                </button>
                <a href="index" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                    Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Monthly Statistics Chart -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">Monthly Job Statistics</h2>
            <canvas id="monthlyChart" width="400" height="200"></canvas>
        </div>

        <!-- Top Performers Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Top Employees -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Top Performing Employees</h2>
                <?php if (!empty($topEmployees)): ?>
                    <div class="space-y-4">
                        <?php foreach ($topEmployees as $employee): ?>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div>
                                    <h3 class="font-medium"><?php echo htmlspecialchars($employee['name']); ?></h3>
                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($employee['category']); ?></p>
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-bold text-blue-600">$<?php echo number_format($employee['total_earnings'] ?? 0, 2); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo $employee['job_count']; ?> jobs</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 text-center py-8">No employee data available.</p>
                <?php endif; ?>
            </div>

            <!-- Top Customers -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">Top Customers</h2>
                <?php if (!empty($topCustomers)): ?>
                    <div class="space-y-4">
                        <?php foreach ($topCustomers as $customer): ?>
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div>
                                    <h3 class="font-medium"><?php echo htmlspecialchars($customer['name']); ?></h3>
                                    <p class="text-sm text-gray-600"><?php echo $customer['job_count']; ?> jobs</p>
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-bold text-green-600">$<?php echo number_format($customer['total_spent'] ?? 0, 2); ?></div>
                                    <div class="text-xs text-gray-500">Total spent</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 text-center py-8">No customer data available.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Monthly Statistics Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold">Monthly Breakdown</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Month</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Jobs</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Completed</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Active</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($monthlyStats as $stat): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo date('F Y', mktime(0, 0, 0, $stat['month'], 1)); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $stat['total_jobs']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $stat['completed_jobs']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $stat['active_jobs']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">$<?php echo number_format($stat['total_revenue'] ?? 0, 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Monthly Chart
        const ctx = document.getElementById('monthlyChart').getContext('2d');
        const monthlyData = <?php echo json_encode($monthlyStats); ?>;
        
        const labels = monthlyData.map(item => {
            const date = new Date(2024, item.month - 1, 1);
            return date.toLocaleDateString('en-US', { month: 'short' });
        }).reverse();
        
        const jobData = monthlyData.map(item => item.total_jobs).reverse();
        const revenueData = monthlyData.map(item => item.total_revenue || 0).reverse();
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Total Jobs',
                    data: jobData,
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.1
                }, {
                    label: 'Revenue ($)',
                    data: revenueData,
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.1,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        grid: {
                            drawOnChartArea: false,
                        },
                    },
                },
            },
        });
        
        function exportReport() {
            // Implement export functionality
            alert('Report export functionality will be implemented here');
        }
    </script>
</body>
</html> 