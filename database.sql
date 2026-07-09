-- Official AI Review SaaS schema
-- Run this on the review_system database before using customer login, plans, OTP, and payments.

CREATE DATABASE IF NOT EXISTS review_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE review_system;

CREATE TABLE IF NOT EXISTS settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(100) NOT NULL UNIQUE,
  setting_value TEXT,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS admin_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT IGNORE INTO admin_users (username, password_hash)
VALUES ('admin', '0192023a7bbd73250516f069df18b500');

CREATE TABLE IF NOT EXISTS customers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  phone VARCHAR(20) NOT NULL UNIQUE,
  email VARCHAR(190) NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  phone_verified_at DATETIME NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS customer_otps (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  phone VARCHAR(20) NOT NULL,
  otp_hash VARCHAR(255) NOT NULL,
  purpose VARCHAR(30) NOT NULL DEFAULT 'register',
  attempts INT NOT NULL DEFAULT 0,
  expires_at DATETIME NOT NULL,
  verified_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_customer_otps_customer (customer_id),
  CONSTRAINT fk_customer_otps_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS clients (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NULL,
  slug VARCHAR(100) NOT NULL UNIQUE,
  company_name VARCHAR(200) NOT NULL,
  tagline VARCHAR(300),
  logo_path VARCHAR(500),
  google_review_link TEXT NOT NULL,
  google_place_id VARCHAR(255) NULL,
  business_location TEXT NULL,
  chatgpt_instructions TEXT,
  service_options TEXT NULL,
  link_expire_at DATETIME NULL,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_clients_customer (customer_id),
  CONSTRAINT fk_clients_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
);

ALTER TABLE clients ADD COLUMN IF NOT EXISTS customer_id INT NULL AFTER id;
ALTER TABLE clients ADD COLUMN IF NOT EXISTS google_place_id VARCHAR(255) NULL AFTER google_review_link;
ALTER TABLE clients ADD COLUMN IF NOT EXISTS business_location TEXT NULL AFTER google_place_id;
ALTER TABLE clients ADD COLUMN IF NOT EXISTS service_options TEXT NULL AFTER chatgpt_instructions;
ALTER TABLE clients ADD COLUMN IF NOT EXISTS link_expire_at DATETIME NULL AFTER service_options;

CREATE TABLE IF NOT EXISTS plans (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  duration_days INT NOT NULL,
  description TEXT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS addons (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  description TEXT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS customer_subscriptions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  plan_id INT NOT NULL,
  order_id INT NULL,
  starts_at DATETIME NOT NULL,
  expires_at DATETIME NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  status ENUM('active','expired','cancelled') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_sub_customer (customer_id),
  INDEX idx_sub_plan (plan_id),
  CONSTRAINT fk_sub_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  CONSTRAINT fk_sub_plan FOREIGN KEY (plan_id) REFERENCES plans(id)
);

CREATE TABLE IF NOT EXISTS addon_purchases (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  addon_id INT NOT NULL,
  order_id INT NULL,
  amount DECIMAL(10,2) NOT NULL,
  status ENUM('paid','cancelled') NOT NULL DEFAULT 'paid',
  purchased_at DATETIME NOT NULL,
  INDEX idx_addon_purchase_customer (customer_id),
  CONSTRAINT fk_addon_purchase_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  CONSTRAINT fk_addon_purchase_addon FOREIGN KEY (addon_id) REFERENCES addons(id)
);

CREATE TABLE IF NOT EXISTS payment_orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT NOT NULL,
  item_type ENUM('plan','addon') NOT NULL,
  item_id INT NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  currency VARCHAR(10) NOT NULL DEFAULT 'INR',
  razorpay_order_id VARCHAR(100) NULL,
  razorpay_payment_id VARCHAR(100) NULL,
  razorpay_signature VARCHAR(255) NULL,
  status ENUM('created','paid','failed') NOT NULL DEFAULT 'created',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  paid_at DATETIME NULL,
  INDEX idx_payment_customer (customer_id),
  INDEX idx_payment_razorpay (razorpay_order_id),
  CONSTRAINT fk_payment_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

INSERT IGNORE INTO plans (id, name, price, duration_days, description, is_active)
VALUES
  (1, 'Monthly Plan', 1000.00, 30, 'Official AI Review monthly access for one business profile.', 1),
  (2, 'Yearly Plan', 10000.00, 365, 'Official AI Review yearly access for one business profile.', 1);

INSERT IGNORE INTO addons (id, name, price, description, is_active)
VALUES
  (1, 'Print Card', 500.00, 'One-time printed QR review card. This addon does not expire and can be purchased multiple times.', 1);

INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
  ('ai_provider', 'openai'),
  ('openai_api_key', ''),
  ('openai_model', 'gpt-4o-mini'),
  ('gemini_api_key', ''),
  ('gemini_model', 'gemini-2.0-flash'),
  ('reviews_per_click', '5'),
  ('review_word_limit', '60'),
  ('whatsapp_api_base_url', 'https://site10.officialdigitalmarketing.in/api'),
  ('whatsapp_api_token', ''),
  ('whatsapp_otp_template', 'test_demo'),
  ('whatsapp_otp_language', 'en_US'),
  ('razorpay_key_id', ''),
  ('razorpay_key_secret', ''),
  ('google_maps_api_key', '');
