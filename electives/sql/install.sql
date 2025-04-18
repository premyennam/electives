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
    year INT NOT NULL DEFAULT 2 CHECK (year BETWEEN 2 AND 4),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id)
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

-- Create queries table
CREATE TABLE IF NOT EXISTS queries (
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

-- Create notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_type VARCHAR(20) NOT NULL,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin (password: password)
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

-- Insert sample HODs (password: password)
INSERT INTO hod (username, password, name, email, department_id) VALUES 
('hod_cse', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'CSE HOD', 'hod_cse@jntua.ac.in', 1),
('hod_ece', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ECE HOD', 'hod_ece@jntua.ac.in', 2),
('hod_eee', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'EEE HOD', 'hod_eee@jntua.ac.in', 3);

-- Insert sample electives
INSERT INTO electives (name, department_id, capacity, year) VALUES 
('Machine Learning', 1, 60, 2),
('Deep Learning', 1, 60, 2),
('Cloud Computing', 1, 60, 2),
('Internet of Things', 2, 60, 2),
('Digital Signal Processing', 2, 60, 2),
('Power Electronics', 3, 60, 2),
('Control Systems', 3, 60, 2);

-- Insert sample students (CSE department - 2nd year)
INSERT INTO students (admission_no, name, email, department_id, year, cgpa, backlogs) VALUES 
('20B01A0501', 'John Doe', '20B01A0501@jntua.ac.in', 1, 2, 8.5, 0),
('20B01A0502', 'Jane Smith', '20B01A0502@jntua.ac.in', 1, 2, 8.2, 0),
('20B01A0503', 'Mike Johnson', '20B01A0503@jntua.ac.in', 1, 2, 7.8, 1),
('20B01A0504', 'Sarah Williams', '20B01A0504@jntua.ac.in', 1, 2, 8.7, 0),
('20B01A0505', 'David Brown', '20B01A0505@jntua.ac.in', 1, 2, 7.5, 2); 