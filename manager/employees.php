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
    
    // Get all employees
    $stmt = $conn->query("SELECT * FROM employees ORDER BY created_at DESC");
    $employees = $stmt->fetchAll();
    
    // Get employee statistics
    $stmt = $conn->query("SELECT 
                         COUNT(*) as total_employees,
                         SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_employees,
                         SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_employees,
                         SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_employees
                         FROM employees");
    $employeeStats = $stmt->fetch();
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Management | LaboTech Manager</title>
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
                <h1 class="text-3xl font-bold text-gray-900">Employee Management</h1>
                <p class="text-gray-600">Manage employee accounts and performance</p>
            </div>
            <a href="index" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                Back to Dashboard
            </a>
        </div>

        <!-- Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="text-3xl font-bold text-blue-600"><?php echo $employeeStats['total_employees'] ?? 0; ?></div>
                <div class="text-sm text-gray-500">Total Employees</div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="text-3xl font-bold text-green-600"><?php echo $employeeStats['active_employees'] ?? 0; ?></div>
                <div class="text-sm text-gray-500">Active</div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="text-3xl font-bold text-yellow-600"><?php echo $employeeStats['pending_employees'] ?? 0; ?></div>
                <div class="text-sm text-gray-500">Pending</div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="text-3xl font-bold text-red-600"><?php echo $employeeStats['inactive_employees'] ?? 0; ?></div>
                <div class="text-sm text-gray-500">Inactive</div>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <div class="flex flex-col md:flex-row gap-4">
                <div class="flex-1">
                    <input type="text" id="searchInput" placeholder="Search employees..." 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex gap-2">
                    <select id="statusFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="pending">Pending</option>
                        <option value="inactive">Inactive</option>
                        <option value="suspended">Suspended</option>
                    </select>
                    <select id="categoryFilter" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">All Categories</option>
                        <option value="Unskilled Labor">Unskilled Labor</option>
                        <option value="Skilled Labor">Skilled Labor</option>
                        <option value="Technical">Technical</option>
                        <option value="Professional">Professional</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Employees Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold">All Employees</h2>
            </div>
            <?php if (!empty($employees)): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee Details</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact Info</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category & Rate</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="employeesTableBody">
                            <?php foreach ($employees as $employee): ?>
                                <tr class="employee-row" data-status="<?php echo $employee['status']; ?>" data-category="<?php echo $employee['job_categories']; ?>">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($employee['name']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($employee['location']); ?></div>
                                        <div class="text-xs text-gray-400">ID: <?php echo $employee['id']; ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($employee['email']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($employee['phone']); ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($employee['job_categories']); ?></div>
                                        <div class="text-sm font-semibold text-green-600">$<?php echo number_format($employee['hourly_rate'], 2); ?>/hr</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php 
                                            switch($employee['status']) {
                                                case 'active': echo 'bg-green-100 text-green-800'; break;
                                                case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                                case 'inactive': echo 'bg-red-100 text-red-800'; break;
                                                case 'suspended': echo 'bg-gray-100 text-gray-800'; break;
                                                default: echo 'bg-gray-100 text-gray-800';
                                            }
                                            ?>">
                                            <?php echo ucfirst($employee['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('M d, Y', strtotime($employee['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <button onclick="viewEmployee(<?php echo $employee['id']; ?>)" 
                                                    class="text-blue-600 hover:text-blue-900">View</button>
                                            <?php if ($employee['status'] == 'active'): ?>
                                                <button onclick="suspendEmployee(<?php echo $employee['id']; ?>)" 
                                                        class="text-orange-600 hover:text-orange-900">Suspend</button>
                                            <?php elseif ($employee['status'] == 'suspended'): ?>
                                                <button onclick="activateEmployee(<?php echo $employee['id']; ?>)" 
                                                        class="text-green-600 hover:text-green-900">Activate</button>
                                            <?php endif; ?>
                                            <button onclick="deactivateEmployee(<?php echo $employee['id']; ?>)" 
                                                    class="text-red-600 hover:text-red-900">Deactivate</button>
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
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No employees found</h3>
                    <p class="text-gray-500">No employees have been registered yet.</p>
                </div>
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
        // Search and filter functionality
        document.getElementById('searchInput').addEventListener('input', filterEmployees);
        document.getElementById('statusFilter').addEventListener('change', filterEmployees);
        document.getElementById('categoryFilter').addEventListener('change', filterEmployees);
        
        function filterEmployees() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;
            const categoryFilter = document.getElementById('categoryFilter').value;
            const rows = document.querySelectorAll('.employee-row');
            
            rows.forEach(row => {
                const name = row.querySelector('td:first-child').textContent.toLowerCase();
                const email = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                const status = row.getAttribute('data-status');
                const category = row.getAttribute('data-category');
                
                const matchesSearch = name.includes(searchTerm) || email.includes(searchTerm);
                const matchesStatus = !statusFilter || status === statusFilter;
                const matchesCategory = !categoryFilter || category === categoryFilter;
                
                if (matchesSearch && matchesStatus && matchesCategory) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
        
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
                                <p><strong>Category:</strong> ${employee.category}</p>
                                <p><strong>Location:</strong> ${employee.location}</p>
                                <p><strong>Hourly Rate:</strong> $${parseFloat(employee.hourly_rate).toFixed(2)}/hr</p>
                                <p><strong>Skills:</strong> ${employee.skills}</p>
                                <p><strong>Status:</strong> ${employee.status}</p>
                                <p><strong>Joined:</strong> ${new Date(employee.created_at).toLocaleDateString()}</p>
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
        
        function activateEmployee(employeeId) {
            if (confirm('Are you sure you want to activate this employee?')) {
                updateEmployeeStatus(employeeId, 'active');
            }
        }
        
        function suspendEmployee(employeeId) {
            if (confirm('Are you sure you want to suspend this employee?')) {
                updateEmployeeStatus(employeeId, 'suspended');
            }
        }
        
        function deactivateEmployee(employeeId) {
            if (confirm('Are you sure you want to deactivate this employee?')) {
                updateEmployeeStatus(employeeId, 'inactive');
            }
        }
        
        function updateEmployeeStatus(employeeId, status) {
            fetch('update-employee-status', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    employee_id: employeeId,
                    status: status
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Employee status updated successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating employee status.');
            });
        }
    </script>
</body>
</html> 