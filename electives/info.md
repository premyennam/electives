# JNTUA CEA Elective Management System Documentation

## Table of Contents
1. [Project Overview](#project-overview)
2. [System Requirements](#system-requirements)
3. [Installation Guide](#installation-guide)
4. [Database Structure](#database-structure)
5. [User Roles and Permissions](#user-roles-and-permissions)
6. [Core Features](#core-features)
7. [Technical Implementation](#technical-implementation)
8. [Security Measures](#security-measures)
9. [API Endpoints](#api-endpoints)
10. [Error Handling](#error-handling)
11. [Future Enhancements](#future-enhancements)

## Project Overview

The JNTUA CEA Elective Management System is a web-based application designed to streamline the process of elective course selection and allocation for students at JNTUA College of Engineering Anantapur. The system handles multiple user roles including administrators, Heads of Departments (HODs), and students, providing a comprehensive solution for managing elective courses across different departments.

### Key Objectives
- Automate elective course selection and allocation
- Implement merit-based allocation system
- Provide real-time tracking of elective preferences
- Enable efficient communication between students and HODs
- Generate comprehensive reports for decision-making

## System Requirements

### Server Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache with mod_rewrite enabled
- PDO PHP Extension
- MySQLi PHP Extension

### Client Requirements
- Modern web browser (Chrome, Firefox, Safari, Edge)
- JavaScript enabled
- Minimum screen resolution: 1024x768

## Installation Guide

1. **Prerequisites Setup**
   ```bash
   # Install XAMPP Server
   # Start Apache and MySQL services
   ```

2. **Project Setup**
   ```bash
   # Clone repository to C:\xampp\htdocs\electives
   # Navigate to http://localhost/electives/install.php
   # Follow installation wizard
   ```

3. **Database Configuration**
   - Default database name: electives_db
   - Default username: root
   - Default password: (empty)

4. **Default Credentials**
   - Admin: admin/password
   - HOD: hod_cse/password
   - Student: Use sample credentials

## Database Structure

### Tables Overview

1. **departments**
   ```sql
   CREATE TABLE departments (
       id INT PRIMARY KEY AUTO_INCREMENT,
       name VARCHAR(100) NOT NULL UNIQUE,
       code VARCHAR(10) NOT NULL UNIQUE,
       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
   );
   ```

2. **admin**
   ```sql
   CREATE TABLE admin (
       id INT PRIMARY KEY AUTO_INCREMENT,
       username VARCHAR(50) NOT NULL UNIQUE,
       password VARCHAR(255) NOT NULL,
       name VARCHAR(100) NOT NULL,
       email VARCHAR(100) NOT NULL UNIQUE,
       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
   );
   ```

3. **hod**
   ```sql
   CREATE TABLE hod (
       id INT PRIMARY KEY AUTO_INCREMENT,
       username VARCHAR(50) NOT NULL UNIQUE,
       password VARCHAR(255) NOT NULL,
       name VARCHAR(100) NOT NULL,
       email VARCHAR(100) NOT NULL UNIQUE,
       department_id INT NOT NULL,
       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
       FOREIGN KEY (department_id) REFERENCES departments(id)
   );
   ```

4. **students**
   ```sql
   CREATE TABLE students (
       id INT PRIMARY KEY AUTO_INCREMENT,
       admission_no VARCHAR(20) NOT NULL UNIQUE,
       name VARCHAR(100) NOT NULL,
       email VARCHAR(100) NOT NULL UNIQUE,
       department_id INT NOT NULL,
       year INT NOT NULL,
       cgpa DECIMAL(4,2) NOT NULL,
       backlogs INT NOT NULL DEFAULT 0,
       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
       FOREIGN KEY (department_id) REFERENCES departments(id)
   );
   ```

5. **electives**
   ```sql
   CREATE TABLE electives (
       id INT PRIMARY KEY AUTO_INCREMENT,
       name VARCHAR(100) NOT NULL,
       department_id INT NOT NULL,
       capacity INT NOT NULL,
       year INT NOT NULL DEFAULT 2 CHECK (year BETWEEN 2 AND 4),
       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
       FOREIGN KEY (department_id) REFERENCES departments(id)
   );
   ```

6. **elective_allotments**
   ```sql
   CREATE TABLE elective_allotments (
       id INT PRIMARY KEY AUTO_INCREMENT,
       student_id INT NOT NULL,
       elective_id INT NOT NULL,
       status ENUM('pending', 'allotted', 'rejected') NOT NULL DEFAULT 'pending',
       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
       FOREIGN KEY (student_id) REFERENCES students(id),
       FOREIGN KEY (elective_id) REFERENCES electives(id)
   );
   ```

7. **student_preferences**
   ```sql
   CREATE TABLE student_preferences (
       id INT PRIMARY KEY AUTO_INCREMENT,
       student_id INT NOT NULL,
       elective_id INT NOT NULL,
       preference_order INT NOT NULL,
       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
       FOREIGN KEY (student_id) REFERENCES students(id),
       FOREIGN KEY (elective_id) REFERENCES electives(id),
       UNIQUE KEY unique_student_preference (student_id, preference_order)
   );
   ```

8. **queries**
   ```sql
   CREATE TABLE queries (
       id INT PRIMARY KEY AUTO_INCREMENT,
       student_id INT NOT NULL,
       subject VARCHAR(255) NOT NULL,
       message TEXT NOT NULL,
       status ENUM('pending', 'resolved', 'rejected') DEFAULT 'pending',
       response TEXT,
       resolved_by INT,
       resolved_at DATETIME,
       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
       FOREIGN KEY (student_id) REFERENCES students(id),
       FOREIGN KEY (resolved_by) REFERENCES hod(id)
   );
   ```

9. **notifications**
   ```sql
   CREATE TABLE notifications (
       id INT PRIMARY KEY AUTO_INCREMENT,
       user_type VARCHAR(20) NOT NULL,
       user_id INT NOT NULL,
       message TEXT NOT NULL,
       is_read TINYINT(1) DEFAULT 0,
       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
   );
   ```

## User Roles and Permissions

### 1. Administrator
- Manage departments
- Manage HODs
- Manage students
- Manage electives
- View all reports
- Allot electives
- Access system-wide statistics

### 2. Head of Department (HOD)
- View department students
- Manage department electives
- Respond to student queries
- View department reports
- Access department-specific statistics

### 3. Student
- View profile
- Select elective preferences
- Submit queries
- View allotment status
- Access personal notifications

## Core Features

### 1. Elective Management
- Create and manage elective courses
- Set capacity limits
- Assign to departments
- Track enrollment status

### 2. Preference Selection
- Students can select multiple preferences
- Order preferences by priority
- View available electives
- Track selection status

### 3. Allotment System
- Merit-based allocation
- CGPA and backlog consideration
- Department-wise allocation
- Automatic fallback options

### 4. Query Management
- Student query submission
- HOD response system
- Query status tracking
- Email notifications

### 5. Reporting System
- Department-wise reports
- Year-wise statistics
- Allotment reports
- Query resolution reports

## Technical Implementation

### 1. Authentication System
```php
// Session management
session_start();
$_SESSION['user_type'] = 'admin';
$_SESSION['user_id'] = $user_id;

// Password hashing
password_hash($password, PASSWORD_DEFAULT);
password_verify($password, $hash);
```

### 2. Database Operations
```php
// PDO Connection
$pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Prepared Statements
$stmt = $pdo->prepare("SELECT * FROM students WHERE department_id = ?");
$stmt->execute([$department_id]);
```

### 3. Form Validation
```php
// Input validation
if (empty($_POST['username']) || empty($_POST['password'])) {
    $error = "All fields are required";
}

// Data sanitization
$username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
```

## Security Measures

1. **Password Security**
   - Bcrypt hashing
   - Salt implementation
   - Minimum length requirements

2. **Session Security**
   - Secure session handling
   - Session timeout
   - CSRF protection

3. **Input Validation**
   - Server-side validation
   - SQL injection prevention
   - XSS protection

4. **Access Control**
   - Role-based access
   - Permission checking
   - Secure routing

## API Endpoints

### Admin API
- GET /admin/departments
- POST /admin/departments
- GET /admin/hods
- POST /admin/hods
- GET /admin/students
- POST /admin/students

### HOD API
- GET /hod/students
- GET /hod/electives
- POST /hod/electives
- GET /hod/queries
- POST /hod/queries/response

### Student API
- GET /student/profile
- POST /student/preferences
- GET /student/allotment
- POST /student/queries

## Error Handling

1. **Database Errors**
   ```php
   try {
       // Database operation
   } catch (PDOException $e) {
       error_log($e->getMessage());
       $error = "Database error occurred";
   }
   ```

2. **Form Validation Errors**
   ```php
   if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
       $errors[] = "Invalid email format";
   }
   ```

3. **Authentication Errors**
   ```php
   if (!isset($_SESSION['user_id'])) {
       header("Location: login.php");
       exit();
   }
   ```

## Future Enhancements

1. **Planned Features**
   - Mobile application
   - Email notifications
   - Advanced reporting
   - API integration

2. **Technical Improvements**
   - Performance optimization
   - Code refactoring
   - Unit testing
   - Documentation updates

3. **User Experience**
   - UI/UX improvements
   - Accessibility features
   - Multi-language support
   - Dark mode

## Support and Maintenance

### Contact Information
- Technical Support: support@jntua.ac.in
- Project Manager: pm@jntua.ac.in

### Documentation Updates
- Last Updated: [Current Date]
- Version: 1.0.0

### Known Issues
1. None reported

### Bug Reporting
Please report bugs through the issue tracker or contact technical support.

---

This documentation is maintained by the JNTUA CEA Development Team. For questions or clarifications, please contact the technical support team. 