<?php
session_start();

require_once '../includes/db.php';
require_once '../includes/category-mapping.php';

// Check if customer is logged in
if (!isset($_SESSION['customer'])) {
    header('Location: ../login');
    exit();
}

$customerId = $_SESSION['customer'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = new Database();
        $conn = $pdo->open();
        
        $title = $_POST['title'];
        $description = $_POST['description'];
        $category = $_POST['category'];
        $location = $_POST['location'];
        $amount = $_POST['amount'];
        $priority = $_POST['priority'];
        $scheduledDate = $_POST['scheduled_date'] ?: null;
        
        // Validate category
        if (!CategoryMapping::isValidJobCategory($category)) {
            throw new Exception("Invalid job category selected.");
        }
        
        // Insert new job
        $stmt = $conn->prepare("INSERT INTO jobs (customer_id, title, description, category, location, amount, priority, scheduled_date, status, created_at) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
        $stmt->execute([$customerId, $title, $description, $category, $location, $amount, $priority, $scheduledDate]);
        
        $success = "Job posted successfully! Professionals will be able to see and apply for your job.";
        
    } catch (Exception $e) {
        $error = "Error posting job: " . $e->getMessage();
    }
}

// Get available categories from the mapping system
$categories = CategoryMapping::getJobCategories();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post New Job | LaboTech</title>
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
                <h1 class="text-3xl font-bold text-gray-900">Post New Job</h1>
                <p class="text-gray-600">Create a new job request and find the perfect professional</p>
            </div>
            <a href="index" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition-colors">
                Back to Dashboard
            </a>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Job Form -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <form method="POST" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Job Title *</label>
                        <input type="text" id="title" name="title" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="e.g., Fix leaking faucet">
                    </div>
                    
                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
                        <select id="category" name="category" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">Select a category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category; ?>"><?php echo $category; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">Choose the category that best describes your job</p>
                    </div>
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Job Description *</label>
                    <textarea id="description" name="description" rows="4" required
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                              placeholder="Describe the job in detail..."></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="location" class="block text-sm font-medium text-gray-700 mb-2">Location *</label>
                        <input type="text" id="location" name="location" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="e.g., New York, NY">
                    </div>
                    
                    <div>
                        <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">Budget Amount ($) *</label>
                        <input type="number" id="amount" name="amount" step="0.01" min="0" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="0.00">
                    </div>
                    
                    <div>
                        <label for="priority" class="block text-sm font-medium text-gray-700 mb-2">Priority</label>
                        <select id="priority" name="priority"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="scheduled_date" class="block text-sm font-medium text-gray-700 mb-2">Preferred Date (Optional)</label>
                    <input type="date" id="scheduled_date" name="scheduled_date"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h3 class="text-sm font-medium text-blue-900 mb-2">Tips for a great job post:</h3>
                    <ul class="text-sm text-blue-700 space-y-1">
                        <li>• Be specific about what needs to be done</li>
                        <li>• Include any special requirements or preferences</li>
                        <li>• Set a realistic budget for the work</li>
                        <li>• Provide clear location details</li>
                    </ul>
                </div>

                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <h3 class="text-sm font-medium text-gray-900 mb-2">Job Categories Guide:</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs text-gray-600">
                        <div>
                            <h4 class="font-medium text-gray-800 mb-1">Unskilled Labor:</h4>
                            <p>Cleaning, Moving, Gardening, Driving, Security</p>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-800 mb-1">Skilled Labor:</h4>
                            <p>Plumbing, Electrical, Carpentry, Painting, Repair, Installation, Cooking</p>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-800 mb-1">Technical:</h4>
                            <p>IT Support, Web Development, Graphic Design</p>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-800 mb-1">Professional:</h4>
                            <p>Consulting, Legal Services, Medical Services, Teaching</p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-4">
                    <a href="index" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition-colors">
                        Cancel
                    </a>
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        Post Job
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Set minimum date to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('scheduled_date').min = today;
        
        // Auto-resize textarea
        document.getElementById('description').addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
    </script>
</body>
</html> 