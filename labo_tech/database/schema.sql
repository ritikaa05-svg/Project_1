-- LaboTech Database Schema with Sample Data
-- Create database if not exists
CREATE DATABASE IF NOT EXISTS labotech_db;
USE labotech_db;

-- Admins table
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Managers table
CREATE TABLE IF NOT EXISTS managers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Customers table
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20) NOT NULL,
    location VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    profile_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Employees table
CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20) NOT NULL,
    location VARCHAR(255) NOT NULL,
    job_categories VARCHAR(255) NOT NULL,
    skills TEXT,
    hourly_rate DECIMAL(10,2) NOT NULL,
    password VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive', 'pending', 'suspended') DEFAULT 'pending',
    profile_image VARCHAR(255),
    rating DECIMAL(3,2) DEFAULT 0.00,
    total_reviews INT DEFAULT 0,
    verification_status ENUM('unverified', 'verified', 'rejected') DEFAULT 'unverified',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Jobs table
CREATE TABLE IF NOT EXISTS jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(100) NOT NULL,
    location VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    customer_id INT NOT NULL,
    employee_id INT,
    status ENUM('pending', 'active', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    scheduled_date DATE,
    scheduled_time TIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE SET NULL
);

-- Job Applications table
CREATE TABLE IF NOT EXISTS job_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    employee_id INT NOT NULL,
    proposed_amount DECIMAL(10,2),
    message TEXT,
    status ENUM('pending', 'accepted', 'rejected', 'withdrawn') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    UNIQUE KEY unique_application (job_id, employee_id)
);

-- Reviews table
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    customer_id INT NOT NULL,
    employee_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- Payments table
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    customer_id INT NOT NULL,
    employee_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'card', 'bank_transfer', 'digital_wallet') DEFAULT 'cash',
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    transaction_id VARCHAR(255),
    payment_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);

-- Messages table
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_type ENUM('customer', 'employee', 'admin') NOT NULL,
    sender_id INT NOT NULL,
    receiver_type ENUM('customer', 'employee', 'admin') NOT NULL,
    receiver_id INT NOT NULL,
    job_id INT,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE SET NULL
);

-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_type ENUM('customer', 'employee', 'admin') NOT NULL,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Settings table
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default admin user
INSERT INTO admins (name, email, password) VALUES 
('Admin', 'admin@labotech.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'); -- password: password

-- Insert default manager
INSERT INTO managers (name, email, password) VALUES 
('Manager', 'manager@labotech.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'); -- password: password

-- Insert sample customers
INSERT INTO customers (name, email, phone, location, password, status) VALUES 
('John Smith', 'john.smith@email.com', '1234567890', 'New York, NY', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active'),
('Sarah Johnson', 'sarah.johnson@email.com', '2345678901', 'Los Angeles, CA', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active');

-- Insert sample employees
INSERT INTO employees (name, email, phone, location, job_categories, skills, hourly_rate, password, status) VALUES 
('Alex Rodriguez', 'alex.r@email.com', '6789012345', 'New York, NY', 'Plumbing,Electrical', 'Experienced plumber and electrician', 35.00, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active'),
('Maria Garcia', 'maria.g@email.com', '7890123456', 'Los Angeles, CA', 'Cleaning,Landscaping', 'Professional cleaning and landscaping', 25.00, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active');

-- Insert sample jobs
INSERT INTO jobs (customer_id, title, description, category, location, amount, status, priority) VALUES 
(1, 'Fix Leaky Faucet', 'Need to fix a leaky kitchen faucet', 'Plumbing', 'New York, NY', 80.00, 'pending', 'medium'),
(2, 'Office Cleaning', 'Regular office cleaning service needed', 'Cleaning', 'Los Angeles, CA', 120.00, 'pending', 'low');

-- Insert sample job applications
INSERT INTO job_applications (job_id, employee_id, proposed_amount, message, status) VALUES 
(1, 1, 75.00, 'I can fix this quickly', 'pending'),
(2, 2, 110.00, 'Professional cleaning service', 'accepted');

-- Insert sample reviews
INSERT INTO reviews (job_id, customer_id, employee_id, rating, comment) VALUES 
(1, 1, 1, 5, 'Great job!'),
(2, 2, 2, 4, 'Very clean and professional.');

-- Insert sample payments
INSERT INTO payments (job_id, customer_id, employee_id, amount, payment_method, status) VALUES 
(1, 1, 1, 80.00, 'card', 'completed'),
(2, 2, 2, 120.00, 'cash', 'completed');

-- Insert sample messages
INSERT INTO messages (sender_type, sender_id, receiver_type, receiver_id, job_id, message) VALUES 
('customer', 1, 'employee', 1, 1, 'Hi, can you come tomorrow?'),
('employee', 1, 'customer', 1, 1, 'Yes, I am available.');

-- Insert sample notifications
INSERT INTO notifications (user_type, user_id, title, message, type) VALUES 
('customer', 1, 'Job Completed', 'Your plumbing job has been completed', 'success'),
('employee', 2, 'Payment Received', 'Payment of $120 has been received', 'success');

-- Insert default settings
INSERT INTO settings (setting_key, setting_value, description) VALUES
('site_name', 'LaboTech', 'Website name'),
('site_description', 'One Platform, Every Skill', 'Website description'),
('commission_rate', '10', 'Platform commission rate in percentage'),
('min_job_amount', '5.00', 'Minimum job amount'),
('max_job_amount', '10000.00', 'Maximum job amount'),
('auto_approve_employees', 'false', 'Auto approve employee registrations'),
('require_verification', 'true', 'Require employee verification');

-- Create indexes for better performance
CREATE INDEX idx_employees_job_categories ON employees(job_categories);
CREATE INDEX idx_employees_status ON employees(status);
CREATE INDEX idx_jobs_status ON jobs(status);
CREATE INDEX idx_jobs_category ON jobs(category);
CREATE INDEX idx_jobs_customer ON jobs(customer_id);
CREATE INDEX idx_jobs_employee ON jobs(employee_id);
CREATE INDEX idx_payments_status ON payments(status);
CREATE INDEX idx_reviews_employee ON reviews(employee_id);
CREATE INDEX idx_messages_job ON messages(job_id);
CREATE INDEX idx_notifications_user ON notifications(user_type, user_id); 