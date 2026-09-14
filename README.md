# LaboTech

LaboTech is a PHP and MySQL service marketplace that connects customers with skilled professionals. Customers can post jobs, professionals can apply for available work, and managers and administrators can coordinate users, jobs, approvals, payments, and reports.

## Features

- Customer registration and account dashboard
- Professional registration with job categories, skills, and hourly rates
- Job posting, browsing, applications, assignment, and status updates
- Customer approval or rejection of applications
- Manager workflow for employee approvals and job coordination
- Administrator dashboards for users, jobs, employees, and reports
- Reviews, payments, messages, notifications, and configurable platform settings
- Responsive interface using Tailwind CSS and Flowbite through CDNs

## Requirements

- PHP 7.4 or newer with PDO and `pdo_mysql` enabled
- MySQL 5.7+ or MariaDB 10.4+
- Apache, XAMPP, WampServer, or another PHP-capable web server
- A modern web browser
- Internet access for the Tailwind CSS, Flowbite, and Chart.js CDN assets used by the pages

## Installation

1. Copy the project into the web server document root. For XAMPP, use:

   ```text
   C:\xampp\htdocs\Project_1
   ```

2. Start Apache and MySQL from the server control panel.

3. Create the database and seed data by importing [`database/schema.sql`](database/schema.sql) into MySQL. The script creates the `labotech_db` database automatically.

   Using the MySQL client:

   ```bash
   mysql -u root -p < database/schema.sql
   ```

4. Check the database connection in [`includes/db.php`](includes/db.php). The default local configuration is:

   ```text
   Host: localhost
   Database: labotech_db
   User: root
   Password: empty
   ```

   Update that file if your MySQL installation uses a different username, password, host, or port.

5. Open the application in a browser. With the default XAMPP layout, use:

   ```text
   http://localhost/Project_1/
   ```

   The project uses extensionless PHP URLs such as `/login` and `/register`. If those URLs do not resolve on Apache, enable `MultiViews` or configure an equivalent rewrite rule for your local server.

## Demo Accounts

The database seed script creates these accounts. The password for each account is `password`.

| Role | Email | Dashboard |
| --- | --- | --- |
| Administrator | `admin@labotech.com` | `/admin/` |
| Manager | `manager@labotech.com` | `/manager/` |
| Customer | `john.smith@email.com` | `/customer/` |
| Professional | `alex.r@email.com` | `/employ/` |

Change or remove these credentials before deploying the application outside a local development environment.

## User Workflows

### Customers

Customers can create an account, post jobs, review available applications, accept or reject professionals, track job history, cancel jobs, and leave reviews.

### Professionals

Professionals can register with their skills and categories, browse available jobs, apply with a proposed amount and message, and update the status of assigned work.

### Managers

Managers can review professional approvals, manage employees, inspect jobs, assign work, and update job or employee statuses.

### Administrators

Administrators can view platform statistics, manage customers and employees, inspect jobs, and access reports.

## Project Structure

```text
.
├── index.html                 Public landing page
├── services.html              Services page
├── login.php                  Login form and role-based redirect
├── register.php               Customer and professional registration
├── actions/                   Login, registration, logout, and test actions
├── admin/                     Administrator dashboard and management pages
├── manager/                   Manager dashboard and management pages
├── customer/                  Customer dashboard and job workflows
├── employ/                    Professional dashboard and job workflows
├── includes/                  Database, session, and category helpers
├── database/schema.sql        Database schema, indexes, and seed data
├── assets/                    Images and other frontend assets
└── main.css                   Shared stylesheet
```

## Database Overview

The schema includes tables for:

- Administrators, managers, customers, and employees
- Jobs and job applications
- Reviews and payments
- Messages and notifications
- Application settings

Foreign keys and indexes are defined in [`database/schema.sql`](database/schema.sql).

## Development Notes

- Authentication is session-based and handled by [`actions/login.php`](actions/login.php).
- New passwords are hashed with PHP's `password_hash()` function.
- Database access is centralized in [`includes/db.php`](includes/db.php) using PDO.
- Tailwind CSS, Flowbite, and Chart.js are loaded from public CDNs rather than bundled locally.
- The current project does not include an automated test suite. Test registration, login, role redirects, job application flows, and database error handling manually after setup.

## Security Before Production

This project is configured for local development. Before production deployment:

- Replace all seeded passwords and remove demo accounts.
- Move database credentials into environment variables or protected configuration.
- Disable detailed database errors in user-facing responses.
- Add CSRF protection and stricter server-side authorization checks to state-changing actions.
- Enable HTTPS and secure session-cookie settings.
- Review input validation, file upload handling, payment processing, and access control.
- Pin or self-host frontend dependencies instead of relying on CDN assets.
