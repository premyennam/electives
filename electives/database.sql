-- Create database
CREATE DATABASE IF NOT EXISTS electives_db;
USE electives_db;

-- Create departments table
CREATE TABLE IF NOT EXISTS departments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL UNIQUE,
    code VARCHAR(10) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create admin table
CREATE TABLE IF NOT EXISTS admin (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create HOD table
CREATE TABLE IF NOT EXISTS hod (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    department_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id)
);

-- Create students table
CREATE TABLE IF NOT EXISTS students (
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

-- Create electives table
CREATE TABLE IF NOT EXISTS electives (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    department_id INT NOT NULL,
    capacity INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id),
    year INT NOT NULL DEFAULT 2 CHECK (year BETWEEN 2 AND 4)
);

-- Create elective_allotments table
CREATE TABLE IF NOT EXISTS elective_allotments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    elective_id INT NOT NULL,
    status ENUM('pending', 'allotted', 'rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (elective_id) REFERENCES electives(id)
);

-- Create queries table
CREATE TABLE IF NOT EXISTS queries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    subject VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('pending', 'resolved', 'rejected') NOT NULL DEFAULT 'pending',
    response TEXT,
    resolved_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (resolved_by) REFERENCES hod(id)
);

-- Create student_preferences table
CREATE TABLE IF NOT EXISTS student_preferences (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    elective_id INT NOT NULL,
    preference_order INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (elective_id) REFERENCES electives(id),
    UNIQUE KEY unique_student_preference (student_id, preference_order)
);

-- Create notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'warning', 'success', 'error') DEFAULT 'info',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_read BOOLEAN DEFAULT FALSE,
    target_type ENUM('all', 'student', 'hod', 'admin') NOT NULL,
    target_id INT,
    FOREIGN KEY (target_id) REFERENCES users(id)
);

-- Insert default admin
INSERT INTO admin (username, password, name, email) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'admin@jntua.ac.in');

-- Insert departments
INSERT INTO departments (name, code) VALUES 
('COMPUTER SCIENCE AND ENGINEERING', 'CSE'),
('ELECTRONICS AND COMMUNICATION ENGINEERING', 'ECE'),
('ELECTRICAL AND ELECTRONICS ENGINEERING', 'EEE'),
('MECHANICAL ENGINEERING', 'MECH'),
('CIVIL ENGINEERING', 'CIVIL'),
('INFORMATION TECHNOLOGY', 'IT'),
('CHEMICAL ENGINEERING', 'CHEM'),
('METALLURGICAL ENGINEERING', 'MET'),
('MINING ENGINEERING', 'MIN'),
('PRODUCTION ENGINEERING', 'PROD');

-- Insert sample HODs
INSERT INTO hod (username, password, name, email, department_id) VALUES 
('hod_cse', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'CSE HOD', 'hod_cse@jntua.ac.in', 1),
('hod_ece', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ECE HOD', 'hod_ece@jntua.ac.in', 2),
('hod_eee', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'EEE HOD', 'hod_eee@jntua.ac.in', 3);

-- Insert sample electives
INSERT INTO electives (name, department_id, capacity) VALUES 
('Machine Learning', 1, 60),
('Deep Learning', 1, 60),
('Cloud Computing', 1, 60),
('Internet of Things', 2, 60),
('Digital Signal Processing', 2, 60),
('Power Electronics', 3, 60),
('Control Systems', 3, 60);

-- Insert sample students (CSE department - 2nd year)
INSERT INTO students (admission_no, name, email, department_id, year, cgpa, backlogs) VALUES 
('20B01A0501', 'John Doe', '20B01A0501@jntua.ac.in', 1, 2, 8.5, 0),
('20B01A0502', 'Jane Smith', '20B01A0502@jntua.ac.in', 1, 2, 8.2, 0),
('20B01A0503', 'Mike Johnson', '20B01A0503@jntua.ac.in', 1, 2, 7.8, 1),
('20B01A0504', 'Sarah Williams', '20B01A0504@jntua.ac.in', 1, 2, 8.7, 0),
('20B01A0505', 'David Brown', '20B01A0505@jntua.ac.in', 1, 2, 7.5, 2);

-- Insert sample students (CSE department - 3rd year)
INSERT INTO students (admission_no, name, email, department_id, year, cgpa, backlogs) VALUES 
('19B01A0501', 'Alice Johnson', '19B01A0501@jntua.ac.in', 1, 3, 8.3, 0),
('19B01A0502', 'Bob Wilson', '19B01A0502@jntua.ac.in', 1, 3, 8.0, 0),
('19B01A0503', 'Carol Davis', '19B01A0503@jntua.ac.in', 1, 3, 7.9, 1),
('19B01A0504', 'David Miller', '19B01A0504@jntua.ac.in', 1, 3, 8.1, 0),
('19B01A0505', 'Eve Anderson', '19B01A0505@jntua.ac.in', 1, 3, 7.7, 1);

-- Insert sample students (ECE department - 2nd year)
INSERT INTO students (admission_no, name, email, department_id, year, cgpa, backlogs) VALUES 
('20B01A0506', 'Emma Wilson', '20B01A0506@jntua.ac.in', 2, 2, 8.3, 0),
('20B01A0507', 'James Taylor', '20B01A0507@jntua.ac.in', 2, 2, 7.9, 1),
('20B01A0508', 'Lisa Anderson', '20B01A0508@jntua.ac.in', 2, 2, 8.1, 0),
('20B01A0509', 'Robert Martin', '20B01A0509@jntua.ac.in', 2, 2, 7.7, 1),
('20B01A0510', 'Mary Davis', '20B01A0510@jntua.ac.in', 2, 2, 8.4, 0);

-- Insert sample students (ECE department - 3rd year)
INSERT INTO students (admission_no, name, email, department_id, year, cgpa, backlogs) VALUES 
('19B01A0506', 'Frank Wilson', '19B01A0506@jntua.ac.in', 2, 3, 8.2, 0),
('19B01A0507', 'Grace Taylor', '19B01A0507@jntua.ac.in', 2, 3, 7.8, 1),
('19B01A0508', 'Henry Anderson', '19B01A0508@jntua.ac.in', 2, 3, 8.0, 0),
('19B01A0509', 'Ivy Martin', '19B01A0509@jntua.ac.in', 2, 3, 7.6, 1),
('19B01A0510', 'Jack Davis', '19B01A0510@jntua.ac.in', 2, 3, 8.3, 0);

-- Insert sample students (EEE department - 2nd year)
INSERT INTO students (admission_no, name, email, department_id, year, cgpa, backlogs) VALUES 
('20B01A0511', 'Thomas Wilson', '20B01A0511@jntua.ac.in', 3, 2, 8.0, 0),
('20B01A0512', 'Jennifer Brown', '20B01A0512@jntua.ac.in', 3, 2, 7.6, 1),
('20B01A0513', 'William Lee', '20B01A0513@jntua.ac.in', 3, 2, 8.2, 0),
('20B01A0514', 'Patricia Clark', '20B01A0514@jntua.ac.in', 3, 2, 7.8, 1),
('20B01A0515', 'Michael White', '20B01A0515@jntua.ac.in', 3, 2, 8.1, 0);

-- Insert sample students (EEE department - 3rd year)
INSERT INTO students (admission_no, name, email, department_id, year, cgpa, backlogs) VALUES 
('19B01A0511', 'Kevin Wilson', '19B01A0511@jntua.ac.in', 3, 3, 7.9, 0),
('19B01A0512', 'Linda Brown', '19B01A0512@jntua.ac.in', 3, 3, 7.5, 1),
('19B01A0513', 'Mark Lee', '19B01A0513@jntua.ac.in', 3, 3, 8.1, 0),
('19B01A0514', 'Nancy Clark', '19B01A0514@jntua.ac.in', 3, 3, 7.7, 1),
('19B01A0515', 'Oliver White', '19B01A0515@jntua.ac.in', 3, 3, 8.0, 0);

-- Add status column to elective_allotments if not exists
ALTER TABLE elective_allotments
MODIFY COLUMN status ENUM('pending', 'allotted', 'rejected') DEFAULT 'pending';

-- Add year column to electives table
ALTER TABLE electives
ADD COLUMN year INT NOT NULL DEFAULT 2 CHECK (year BETWEEN 2 AND 4);

-- Update existing electives with default year values
UPDATE electives SET year = 2; 