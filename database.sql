-- 1. DISABLE FOREIGN KEY CHECKS (To allow resetting tables safely)
SET FOREIGN_KEY_CHECKS = 0;

-- 2. DROP TABLES IF THEY EXIST (Cleans up old data)
DROP TABLE IF EXISTS sale_items;
DROP TABLE IF EXISTS sales;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS shops;

-- 3. RE-ENABLE CHECKS
SET FOREIGN_KEY_CHECKS = 1;

-- 4. TABLE: SHOPS (For Login & Authentication)
CREATE TABLE shops (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shop_name VARCHAR(100) NOT NULL,
    owner_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 5. TABLE: PRODUCTS (Inventory - Linked to Shop)
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shop_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock_qty INT NOT NULL,
    expiry_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
);

-- 6. TABLE: CUSTOMERS (CRM - Linked to Shop with Email)
CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shop_id INT NOT NULL,
    name VARCHAR(100),
    phone VARCHAR(15) NOT NULL,
    email VARCHAR(100), 
    visit_count INT DEFAULT 1,
    last_visit DATE DEFAULT CURRENT_DATE,
    loyalty_points INT DEFAULT 0,
    -- Allows duplicate phones ONLY if they are in different shops
    UNIQUE KEY unique_customer_per_shop (shop_id, phone),
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
);

-- 7. TABLE: SALES (Invoice Headers)
CREATE TABLE sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shop_id INT NOT NULL,
    customer_id INT,
    total_amount DECIMAL(10,2),
    sale_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
);

-- 8. TABLE: SALE_ITEMS (Multi-Item Invoice Details)
CREATE TABLE sale_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- ==========================================
-- DUMMY DATA (Optional - For Testing)
-- ==========================================

-- Dummy Shop (Password: '123456' hashed)
INSERT INTO shops (shop_name, owner_name, email, password) VALUES 
('Demo Store', 'Admin', 'admin@test.com', '$2y$10$abcdefghijklmnopqrstuv'); 

-- Dummy Products 
INSERT INTO products (shop_id, name, price, stock_qty, expiry_date) VALUES 
(1, 'Fresh Milk (1L)', 60.00, 20, DATE_ADD(CURRENT_DATE, INTERVAL 2 DAY)), 
(1, 'Dairy Milk Silk', 80.00, 100, DATE_ADD(CURRENT_DATE, INTERVAL 1 YEAR));

-- Dummy Customer
INSERT INTO customers (shop_id, name, phone, email, visit_count, last_visit) VALUES 
(1, 'Rahul Sharma', '9876543210', 'rahul@test.com', 10, DATE_SUB(CURRENT_DATE, INTERVAL 40 DAY));