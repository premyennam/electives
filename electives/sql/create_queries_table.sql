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