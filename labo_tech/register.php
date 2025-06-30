<?php
session_start();
require_once 'includes/category-mapping.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | LaboTech</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/webp" href="assets/logo_without_bg.png">
    <link rel="stylesheet" href="main.css">
</head>

<body class="bg-gray-50">
    <!-- Return to Home Button -->
    <div class="absolute top-4 left-4">
      <a href="index.html" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 transition-colors">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
        </svg>
        Return to Home
      </a>
    </div>
    
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <a href="index.html">
                    <img class="mx-auto h-16 w-auto cursor-pointer" src="assets/logo_without_bg.png" alt="LaboTech">
                </a>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Create your account
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Join LaboTech and connect with skilled professionals
                </p>
            </div>

            <!-- Registration Type Selection -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-3">I want to register as:</label>
                    <div class="grid grid-cols-2 gap-4">
                        <button type="button" id="customer-btn" class="user-type-btn active bg-blue-600 text-white" onclick="selectUserType('customer')" style="display: flex
;
    align-items: center;
    flex-direction: column;
    padding: 10px;
    border-radius: 45px;">
                            <svg class="w-6 h-6 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            Customer
                        </button>
                        <button type="button" id="employee-btn" class="user-type-btn bg-gray-200 text-gray-700" onclick="selectUserType('employee')" style="display: flex
;
    align-items: center;
    flex-direction: column;
    padding: 10px;
    border-radius: 45px;">
                            <svg class="w-6 h-6 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2V6"></path>
                            </svg>
                            Professional
                        </button>
                    </div>
                </div>

                <!-- Customer Registration Form -->
                <form id="customer-form" class="space-y-4" action="actions/register" method="POST">
                    <input type="hidden" name="user_type" value="customer">
                    
                    <div class="form-group">
                        <label for="customer_name" class="form-label">Full Name</label>
                        <input type="text" name="name" id="customer_name" required class="form-input" placeholder="Enter your full name">
                    </div>

                    <div class="form-group">
                        <label for="customer_email" class="form-label">Email Address</label>
                        <input type="email" name="email" id="customer_email" required class="form-input" placeholder="Enter your email">
                    </div>

                    <div class="form-group">
                        <label for="customer_phone" class="form-label">Phone Number</label>
                        <input type="tel" name="phone" id="customer_phone" required class="form-input" placeholder="Enter your phone number">
                    </div>

                    <div class="form-group">
                        <label for="customer_location" class="form-label">Location</label>
                        <input type="text" name="location" id="customer_location" required class="form-input" placeholder="Enter your location">
                    </div>

                    <div class="form-group">
                        <label for="customer_password" class="form-label">Password</label>
                        <input type="password" name="password" id="customer_password" required class="form-input" placeholder="Create a password">
                    </div>

                    <div class="form-group">
                        <label for="customer_confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" name="confirm_password" id="customer_confirm_password" required class="form-input" placeholder="Confirm your password">
                    </div>

                    <button type="submit" class="w-full btn-primary text-white py-3 px-4 rounded-lg font-semibold">
                        Create Customer Account
                    </button>
                </form>

                <!-- Employee Registration Form -->
                <form id="employee-form" class="space-y-4 hidden" action="actions/register" method="POST">
                    <input type="hidden" name="user_type" value="employee">
                    
                    <div class="form-group">
                        <label for="employee_name" class="form-label">Full Name</label>
                        <input type="text" name="name" id="employee_name" class="form-input" placeholder="Enter your full name">
                    </div>

                    <div class="form-group">
                        <label for="employee_email" class="form-label">Email Address</label>
                        <input type="email" name="email" id="employee_email" class="form-input" placeholder="Enter your email">
                    </div>

                    <div class="form-group">
                        <label for="employee_phone" class="form-label">Phone Number</label>
                        <input type="tel" name="phone" id="employee_phone" class="form-input" placeholder="Enter your phone number">
                    </div>

                    <div class="form-group">
                        <label for="employee_categories" class="form-label">Job Categories *</label>
                        <div class="text-sm text-gray-500 mb-2">Select the job categories you can work on:</div>
                        <div class="grid grid-cols-2 gap-2 max-h-48 overflow-y-auto border border-gray-300 rounded-lg p-3">
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="job_categories[]" value="Plumbing" class="rounded" required>
                                <span class="text-sm">Plumbing</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="job_categories[]" value="Electrical" class="rounded">
                                <span class="text-sm">Electrical</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="job_categories[]" value="Carpentry" class="rounded">
                                <span class="text-sm">Carpentry</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="job_categories[]" value="Cleaning" class="rounded">
                                <span class="text-sm">Cleaning</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="job_categories[]" value="Gardening" class="rounded">
                                <span class="text-sm">Gardening</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="job_categories[]" value="Painting" class="rounded">
                                <span class="text-sm">Painting</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="job_categories[]" value="Moving" class="rounded">
                                <span class="text-sm">Moving</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="job_categories[]" value="Repair" class="rounded">
                                <span class="text-sm">Repair</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="job_categories[]" value="Installation" class="rounded">
                                <span class="text-sm">Installation</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="job_categories[]" value="Cooking" class="rounded">
                                <span class="text-sm">Cooking</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="job_categories[]" value="Driving" class="rounded">
                                <span class="text-sm">Driving</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="job_categories[]" value="Security" class="rounded">
                                <span class="text-sm">Security</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="job_categories[]" value="IT Support" class="rounded">
                                <span class="text-sm">IT Support</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="job_categories[]" value="Web Development" class="rounded">
                                <span class="text-sm">Web Development</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="job_categories[]" value="Graphic Design" class="rounded">
                                <span class="text-sm">Graphic Design</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="job_categories[]" value="Consulting" class="rounded">
                                <span class="text-sm">Consulting</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="job_categories[]" value="Legal Services" class="rounded">
                                <span class="text-sm">Legal Services</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="job_categories[]" value="Medical Services" class="rounded">
                                <span class="text-sm">Medical Services</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="job_categories[]" value="Teaching" class="rounded">
                                <span class="text-sm">Teaching</span>
                            </label>
                            <label class="flex items-center space-x-2">
                                <input type="checkbox" name="job_categories[]" value="Other" class="rounded">
                                <span class="text-sm">Other</span>
                            </label>
                        </div>
                        <div class="text-xs text-gray-500 mt-1">Select all categories you can work in</div>
                    </div>

                    <div class="form-group">
                        <label for="employee_description" class="form-label">Additional Information (Optional)</label>
                        <textarea name="description" id="employee_description" rows="3" class="form-input" placeholder="Tell customers about your experience, certifications, or special skills..."></textarea>
                        <div class="text-xs text-gray-500 mt-1">This helps customers understand your qualifications better</div>
                    </div>

                    <div class="form-group">
                        <label for="employee_location" class="form-label">Location</label>
                        <input type="text" name="location" id="employee_location" class="form-input" placeholder="Enter your location">
                    </div>

                    <div class="form-group">
                        <label for="employee_hourly_rate" class="form-label">Hourly Rate ($)</label>
                        <input type="number" name="hourly_rate" id="employee_hourly_rate" class="form-input" placeholder="Enter your hourly rate" min="0" step="0.01">
                    </div>

                    <div class="form-group">
                        <label for="employee_password" class="form-label">Password</label>
                        <input type="password" name="password" id="employee_password" class="form-input" placeholder="Create a password">
                    </div>

                    <div class="form-group">
                        <label for="employee_confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" name="confirm_password" id="employee_confirm_password" class="form-input" placeholder="Confirm your password">
                    </div>

                    <button type="submit" class="w-full btn-secondary text-white py-3 px-4 rounded-lg font-semibold">
                        Create Professional Account
                    </button>
                </form>
            </div>

            <div class="text-center">
                <p class="text-sm text-gray-600">
                    Already have an account? 
                    <a href="login" class="font-medium text-blue-600 hover:text-blue-500">
                        Sign in here
                    </a>
                </p>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <style>
        .user-type-btn {
            @apply flex flex-col items-center justify-center p-4 rounded-lg border-2 border-transparent transition-all duration-200;
        }
        
        .user-type-btn.active {
            @apply border-blue-600;
        }
        
        .user-type-btn:not(.active):hover {
            @apply bg-gray-100;
        }
    </style>

    <script>
        // Category mapping for JavaScript
        const categoryMapping = {
            'Unskilled Labor': ['Cleaning', 'Moving', 'Gardening', 'Driving', 'Security'],
            'Skilled Labor': ['Plumbing', 'Electrical', 'Carpentry', 'Painting', 'Repair', 'Installation', 'Cooking'],
            'Technical': ['IT Support', 'Web Development', 'Graphic Design'],
            'Professional': ['Consulting', 'Legal Services', 'Medical Services', 'Teaching']
        };

        function updateJobCategories() {
            const employeeCategory = document.getElementById('employee_category').value;
            const jobCategoriesDisplay = document.getElementById('job_categories_display');
            
            if (employeeCategory && categoryMapping[employeeCategory]) {
                const jobCategories = categoryMapping[employeeCategory];
                const categoryList = jobCategories.map(cat => `<span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded mr-2 mb-1">${cat}</span>`).join('');
                
                jobCategoriesDisplay.innerHTML = `
                    <p class="text-sm font-medium text-gray-700 mb-2">You can work on these job types:</p>
                    <div class="flex flex-wrap">
                        ${categoryList}
                    </div>
                    <p class="text-xs text-gray-500 mt-2">These jobs will automatically appear in your available jobs list</p>
                `;
            } else {
                jobCategoriesDisplay.innerHTML = '<p class="text-sm text-gray-500">Select your professional category above to see available job types</p>';
            }
        }

        function selectUserType(type) {
            const customerBtn = document.getElementById('customer-btn');
            const employeeBtn = document.getElementById('employee-btn');
            const customerForm = document.getElementById('customer-form');
            const employeeForm = document.getElementById('employee-form');

            if (type === 'customer') {
                customerBtn.classList.add('active', 'bg-blue-600', 'text-white');
                customerBtn.classList.remove('bg-gray-200', 'text-gray-700');
                employeeBtn.classList.remove('active', 'bg-blue-600', 'text-white');
                employeeBtn.classList.add('bg-gray-200', 'text-gray-700');
                customerForm.classList.remove('hidden');
                employeeForm.classList.add('hidden');
            } else {
                employeeBtn.classList.add('active', 'bg-blue-600', 'text-white');
                employeeBtn.classList.remove('bg-gray-200', 'text-gray-700');
                customerBtn.classList.remove('active', 'bg-blue-600', 'text-white');
                customerBtn.classList.add('bg-gray-200', 'text-gray-700');
                employeeForm.classList.remove('hidden');
                customerForm.classList.add('hidden');
            }
        }

        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('form');
            
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const password = form.querySelector('input[name="password"]');
                    const confirmPassword = form.querySelector('input[name="confirm_password"]');
                    
                    if (password.value !== confirmPassword.value) {
                        e.preventDefault();
                        alert('Passwords do not match!');
                        return false;
                    }
                    
                    if (password.value.length < 6) {
                        e.preventDefault();
                        alert('Password must be at least 6 characters long!');
                        return false;
                    }
                });
            });
        });
    </script>
</body>
</html> 