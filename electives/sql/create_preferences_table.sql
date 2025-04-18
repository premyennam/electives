CREATE TABLE IF NOT EXISTS student_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    elective_id INT NOT NULL,
    preference_order INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (elective_id) REFERENCES electives(id) ON DELETE CASCADE,
    UNIQUE KEY unique_student_preference (student_id, preference_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; 

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_type VARCHAR(20) NOT NULL,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;