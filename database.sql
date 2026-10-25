-- Online Freelancing and Service Marketplace - database schema
-- Import this file in phpMyAdmin (or: mysql -u root < database.sql)

CREATE DATABASE IF NOT EXISTS freelance_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE freelance_db;

DROP TABLE IF EXISTS reviews, messages, payments, bookings, portfolio, services, categories, freelancer_profiles, users;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('client','freelancer','admin') NOT NULL DEFAULT 'client',
  phone VARCHAR(20) DEFAULT NULL,
  city VARCHAR(80) DEFAULT NULL,
  avatar VARCHAR(255) DEFAULT NULL,
  is_verified TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('active','blocked') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE freelancer_profiles (
  user_id INT PRIMARY KEY,
  title VARCHAR(150) DEFAULT NULL,
  bio TEXT,
  skills VARCHAR(255) DEFAULT NULL,
  hourly_rate DECIMAL(10,2) DEFAULT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE services (
  id INT AUTO_INCREMENT PRIMARY KEY,
  freelancer_id INT NOT NULL,
  category_id INT NOT NULL,
  title VARCHAR(150) NOT NULL,
  description TEXT NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  delivery_days INT NOT NULL DEFAULT 3,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (freelancer_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB;

CREATE TABLE portfolio (
  id INT AUTO_INCREMENT PRIMARY KEY,
  freelancer_id INT NOT NULL,
  title VARCHAR(150) NOT NULL,
  description VARCHAR(255) DEFAULT NULL,
  image VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (freelancer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  service_id INT NOT NULL,
  client_id INT NOT NULL,
  freelancer_id INT NOT NULL,
  details TEXT NOT NULL,
  needed_by DATE DEFAULT NULL,
  amount DECIMAL(10,2) NOT NULL,
  status ENUM('pending','accepted','in_progress','completed','cancelled','rejected') NOT NULL DEFAULT 'pending',
  payment_status ENUM('unpaid','paid') NOT NULL DEFAULT 'unpaid',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
  FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (freelancer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  booking_id INT NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  method VARCHAR(30) NOT NULL,
  transaction_ref VARCHAR(40) NOT NULL,
  status ENUM('success','failed','refunded') NOT NULL DEFAULT 'success',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  booking_id INT NOT NULL,
  sender_id INT NOT NULL,
  message TEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
  FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE reviews (
  id INT AUTO_INCREMENT PRIMARY KEY,
  booking_id INT NOT NULL UNIQUE,
  service_id INT NOT NULL,
  client_id INT NOT NULL,
  freelancer_id INT NOT NULL,
  rating TINYINT NOT NULL,
  comment VARCHAR(500) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
  FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
  FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (freelancer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Seed data
INSERT INTO categories (name) VALUES
('Web Development'),('Graphic Design'),('Content Writing'),('Digital Marketing'),
('Video Editing'),('Mobile Apps'),('Tutoring'),('Home Services');

-- Default admin account: admin@market.com / Admin@123  (change after first login)
INSERT INTO users (name,email,password,role,is_verified) VALUES
('Administrator','admin@market.com','$2y$10$Mfpy6EGmABS86ltOPssFHeeywuBb422VSixU7.pHHzvGgOhrQd56W','admin',1);
