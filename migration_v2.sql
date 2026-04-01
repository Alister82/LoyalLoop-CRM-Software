-- =============================================
-- LoyalLoop v2 Migration: Suppliers & Replenishment
-- Run this ONCE in phpMyAdmin on your loyalloop DB
-- =============================================

-- 1. CREATE SUPPLIERS TABLE
CREATE TABLE IF NOT EXISTS suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shop_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    whatsapp_number VARCHAR(20) NOT NULL,
    company VARCHAR(100) DEFAULT '',
    notes TEXT DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
);

-- 2. ADD COLUMNS TO PRODUCTS TABLE (safe - only if they don't exist)
ALTER TABLE products
    ADD COLUMN IF NOT EXISTS default_supplier_id INT NULL,
    ADD COLUMN IF NOT EXISTS reorder_level INT DEFAULT 10,
    ADD COLUMN IF NOT EXISTS category VARCHAR(50) DEFAULT 'General';

-- 3. ADD FOREIGN KEY (only if not already present)
ALTER TABLE products
    ADD CONSTRAINT fk_product_supplier
    FOREIGN KEY (default_supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL;

-- Migration complete. You can now use the Suppliers and Replenishment AI features.
