CREATE DATABASE hotel_reservation_system;

USE hotel_reservation_system;

-- Users table
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    PASSWORD VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    ROLE ENUM('admin', 'clerk', 'customer', 'travel_company') NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Customers table (extends users)
CREATE TABLE customers (
    customer_id INT PRIMARY KEY,
    credit_card_info VARCHAR(255),
    loyalty_points INT DEFAULT 0,
    FOREIGN KEY (customer_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Travel companies table (extends users)
CREATE TABLE travel_companies (
    company_id INT PRIMARY KEY,
    company_name VARCHAR(100) NOT NULL,
    discount_rate DECIMAL(5,2) DEFAULT 0.10,
    billing_address TEXT,
    FOREIGN KEY (company_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Room types
CREATE TABLE room_types (
    type_id INT AUTO_INCREMENT PRIMARY KEY,
    NAME VARCHAR(50) NOT NULL,
    DESCRIPTION TEXT,
    base_price DECIMAL(10,2) NOT NULL,
    capacity INT NOT NULL,
    image_path VARCHAR(255)
);

-- Rooms
CREATE TABLE rooms (
    room_id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(10) NOT NULL UNIQUE,
    type_id INT NOT NULL,
    FLOOR INT NOT NULL,
    STATUS ENUM('available', 'occupied', 'maintenance') DEFAULT 'available',
    FOREIGN KEY (type_id) REFERENCES room_types(type_id)
);

-- Residential suites
CREATE TABLE residential_suites (
    suite_id INT AUTO_INCREMENT PRIMARY KEY,
    suite_number VARCHAR(10) NOT NULL UNIQUE,
    type_id INT NOT NULL,
    FLOOR INT NOT NULL,
    weekly_rate DECIMAL(10,2) NOT NULL,
    monthly_rate DECIMAL(10,2) NOT NULL,
    STATUS ENUM('available', 'occupied', 'maintenance') DEFAULT 'available',
    FOREIGN KEY (type_id) REFERENCES room_types(type_id)
);

-- Reservations
CREATE TABLE reservations (
    reservation_id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    room_id INT,
    suite_id INT,
    check_in_date DATE NOT NULL,
    check_out_date DATE NOT NULL,
    adults INT NOT NULL DEFAULT 1,
    children INT NOT NULL DEFAULT 0,
    STATUS ENUM('pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    credit_card_info VARCHAR(255),
    is_company_booking BOOLEAN DEFAULT FALSE,
    company_id INT,
    special_requests TEXT,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
    FOREIGN KEY (room_id) REFERENCES rooms(room_id),
    FOREIGN KEY (suite_id) REFERENCES residential_suites(suite_id),
    FOREIGN KEY (company_id) REFERENCES travel_companies(company_id)
);

-- Block bookings by travel companies
CREATE TABLE block_bookings (
    block_id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    room_type_id INT NOT NULL,
    quantity INT NOT NULL,
    STATUS ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES travel_companies(company_id),
    FOREIGN KEY (room_type_id) REFERENCES room_types(type_id)
);

-- Billing
CREATE TABLE billing (
    bill_id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT NOT NULL,
    room_charges DECIMAL(10,2) NOT NULL,
    additional_charges DECIMAL(10,2) DEFAULT 0,
    tax DECIMAL(10,2) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'credit_card', 'company') NOT NULL,
    payment_status ENUM('pending', 'paid', 'refunded') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id)
);

-- Additional services
CREATE TABLE additional_services (
    service_id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT NOT NULL,
    service_type ENUM('restaurant', 'room_service', 'laundry', 'telephone', 'club') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    DESCRIPTION TEXT,
    DATE TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id)
);

-- Reports
CREATE TABLE reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    report_date DATE NOT NULL,
    report_type ENUM('daily_occupancy', 'financial', 'no_show') NOT NULL,
    total_occupancy INT NOT NULL,
    total_revenue DECIMAL(10,2) NOT NULL,
    details TEXT,
    generated_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (generated_by) REFERENCES users(user_id)
);


-- Insert admin user
INSERT INTO users (username, PASSWORD, email, ROLE, first_name, last_name, phone, address)
VALUES 
('admin', SHA2('admin123', 256), 'admin@gmail.com', 'admin', 'Prathap', 'Sivananthan', '1234567890', '123 Karainagar, Jaffna');

-- Insert clerk user
INSERT INTO users (username, PASSWORD, email, ROLE, first_name, last_name, phone,`users` address)
VALUES 
('clerk', SHA2('clerk123', 256), 'clerk@gmail.com', 'clerk', 'Ajith', 'Kumar', '9876543210', '456 Kokuvil, Jaffna');

-- Insert customer user
INSERT INTO users (username, PASSWORD, email, ROLE, first_name, last_name, phone, address)
VALUES 
('customer', SHA2('customer123', 256), 'customer@gmail.com', 'customer', 'Vijay', 'Anthony', '5551234567', '789 Inuvil, Jaffna');

-- Insert travel company user
INSERT INTO users (username, PASSWORD, email, ROLE, first_name, last_name, phone, address)
VALUES 
('travel', SHA2('travel123', 256), 'travelco@gmail.com', 'travel_company', 'Kamal', 'Hasan', '4449876543', '321 Kondavil, Jaffna');