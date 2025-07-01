-- Database setup for Class Booking System

-- Create database (uncomment if needed)
-- CREATE DATABASE testbook;
-- USE testbook;

-- Classes table
CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    date DATE NOT NULL,
    time TIME NOT NULL,
    capacity INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bookings table
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
);

-- Admin table
CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin user (username: admin, password: admin123)
INSERT INTO admin (username, password_hash) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')
ON DUPLICATE KEY UPDATE username = username;

-- Insert sample classes
INSERT INTO classes (name, description, date, time, capacity) VALUES
('Morning Yoga', 'A relaxing yoga session to start your day', CURDATE() + INTERVAL 1 DAY, '09:00:00', 15),
('Evening Pilates', 'Core strengthening pilates class', CURDATE() + INTERVAL 2 DAY, '18:00:00', 12),
('Weekend Meditation', 'Mindfulness and meditation practice', CURDATE() + INTERVAL 3 DAY, '10:00:00', 20),
('HIIT Training', 'High-intensity interval training', CURDATE() + INTERVAL 4 DAY, '17:00:00', 10),
('Beginner Yoga', 'Perfect for yoga beginners', CURDATE() + INTERVAL 5 DAY, '11:00:00', 18)
ON DUPLICATE KEY UPDATE name = name; 