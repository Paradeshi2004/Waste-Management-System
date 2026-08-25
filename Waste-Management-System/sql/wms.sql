-- Waste Management System Database Schema
-- Import via PHPMyAdmin or: mysql -u root -p < wms.sql

CREATE DATABASE IF NOT EXISTS waste_management_db;
USE waste_management_db;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    role ENUM('resident', 'admin') DEFAULT 'resident',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Complaints table
CREATE TABLE complaints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    category ENUM('garbage', 'illegal_dumping', 'recycling', 'hazardous', 'other') DEFAULT 'garbage',
    location VARCHAR(255) NOT NULL,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    image_path VARCHAR(255),
    status ENUM('pending', 'in_progress', 'resolved', 'rejected') DEFAULT 'pending',
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    admin_notes TEXT,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Status updates / activity log
CREATE TABLE complaint_updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT NOT NULL,
    updated_by INT NOT NULL,
    old_status VARCHAR(50),
    new_status VARCHAR(50),
    old_priority VARCHAR(50),
    new_priority VARCHAR(50),
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(id)
);

-- Recycling tips / educational content
CREATE TABLE recycling_tips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    category VARCHAR(100),
    image_path VARCHAR(255),
    published TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Seed admin user (password: admin123)
INSERT INTO users (name, email, password, role) VALUES
('Admin', 'admin@wms.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Seed sample recycling tips
INSERT INTO recycling_tips (title, content, category) VALUES
('How to Recycle Plastic Bottles', 'Rinse plastic bottles before placing them in the recycling bin. Remove caps and labels when possible. Check the recycling number on the bottom — numbers 1 and 2 are widely accepted.', 'Plastic'),
('Paper Recycling Guide', 'Flatten cardboard boxes. Keep paper dry and clean. Avoid recycling greasy pizza boxes — compost them instead.', 'Paper'),
('Electronic Waste Disposal', 'Never throw electronics in regular trash. Drop off old phones, laptops, and batteries at designated e-waste collection points. Many retailers offer take-back programs.', 'Electronics'),
('Composting Basics', 'Composting food scraps reduces landfill waste by up to 30%. Add fruit peels, vegetable scraps, and coffee grounds to your compost bin. Avoid meat and dairy.', 'Organic');
