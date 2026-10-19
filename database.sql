CREATE DATABASE IF NOT EXISTS hostel_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hostel_management;

CREATE TABLE IF NOT EXISTS students (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    enrollment_no VARCHAR(30) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    gender VARCHAR(20) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100) DEFAULT NULL,
    course VARCHAR(60) DEFAULT NULL,
    semester TINYINT UNSIGNED DEFAULT NULL,
    address VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS rooms (
    room_id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(20) NOT NULL UNIQUE,
    room_type VARCHAR(30) NOT NULL,
    capacity TINYINT UNSIGNED NOT NULL,
    status ENUM('Available','Maintenance') NOT NULL DEFAULT 'Available'
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS allocations (
    allocation_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    room_id INT NOT NULL,
    allocation_date DATE NOT NULL,
    release_date DATE DEFAULT NULL,
    CONSTRAINT fk_alloc_student FOREIGN KEY (student_id) REFERENCES students(student_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_alloc_room FOREIGN KEY (room_id) REFERENCES rooms(room_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    INDEX idx_allocation_active (student_id, release_date),
    INDEX idx_room_active (room_id, release_date)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS fees (
    fee_id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_status ENUM('Paid','Pending') NOT NULL DEFAULT 'Paid',
    remarks VARCHAR(255) DEFAULT NULL,
    CONSTRAINT fk_fees_student FOREIGN KEY (student_id) REFERENCES students(student_id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- Optional sample rooms
INSERT IGNORE INTO rooms (room_number, room_type, capacity, status) VALUES
('101','Single',1,'Available'),
('102','Double',2,'Available'),
('103','Triple',3,'Available'),
('201','Double',2,'Available'),
('202','Triple',3,'Available');
