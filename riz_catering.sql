-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 26, 2026 at 07:41 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `riz_catering`
--

-- --------------------------------------------------------

--
-- Table structure for table `menu_items`
--

CREATE TABLE `menu_items` (
  `id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `is_vegan` tinyint(1) DEFAULT 0,
  `is_gluten_free` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `client_name` varchar(255) NOT NULL,
  `client_email` varchar(255) DEFAULT NULL,
  `event_date` date NOT NULL,
  `delivery_address` text NOT NULL,
  `guest_count` int(11) NOT NULL,
  `dining_tier` varchar(100) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT 'Cash',
  `status` varchar(50) DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `menu_item_id` int(11) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `menu_item_id` (`menu_item_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- ============================================================
-- FULL APPLICATION SCHEMA MIGRATIONS (included for direct import)
-- ============================================================
-- Scope-alignment migration for the Riz Catering Food Ordering System.
-- Apply once after importing riz_catering.sql. This adds no inventory or stock tables.

CREATE TABLE IF NOT EXISTS users (
  user_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  full_name VARCHAR(120) NOT NULL,
  email VARCHAR(254) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('Customer', 'Admin') NOT NULL DEFAULT 'Customer',
  owner_account_slot TINYINT GENERATED ALWAYS AS (IF(role = 'Admin', 1, NULL)) STORED,
  status ENUM('Active', 'Disabled') NOT NULL DEFAULT 'Active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id),
  UNIQUE KEY uq_users_email (email),
  UNIQUE KEY uq_single_owner_account (owner_account_slot)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS customers (
  customer_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED DEFAULT NULL,
  full_name VARCHAR(120) NOT NULL,
  contact_number VARCHAR(30) NOT NULL,
  email VARCHAR(254) NOT NULL,
  address VARCHAR(500) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (customer_id),
  UNIQUE KEY uq_customers_user (user_id),
  KEY idx_customers_email (email),
  CONSTRAINT fk_customers_user FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS catering_packages (
  package_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  package_name VARCHAR(160) NOT NULL,
  description TEXT DEFAULT NULL,
  price_type ENUM('Per Person', 'Fixed') NOT NULL DEFAULT 'Fixed',
  package_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  minimum_guests INT UNSIGNED NOT NULL DEFAULT 1,
  status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (package_id),
  KEY idx_packages_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS package_items (
  package_id INT UNSIGNED NOT NULL,
  menu_item_id INT(11) NOT NULL,
  option_group VARCHAR(100) NOT NULL,
  selection_rule ENUM('Required One', 'Optional Many') NOT NULL DEFAULT 'Required One',
  PRIMARY KEY (package_id, menu_item_id, option_group),
  CONSTRAINT fk_package_items_package FOREIGN KEY (package_id) REFERENCES catering_packages (package_id) ON DELETE CASCADE,
  CONSTRAINT fk_package_items_menu FOREIGN KEY (menu_item_id) REFERENCES menu_items (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE menu_items
  ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER is_gluten_free;

ALTER TABLE orders
  ADD COLUMN customer_id INT UNSIGNED DEFAULT NULL AFTER id,
  ADD COLUMN package_id INT UNSIGNED DEFAULT NULL AFTER customer_id,
  ADD COLUMN client_mobile VARCHAR(30) DEFAULT NULL AFTER client_email,
  ADD COLUMN event_start_time TIME DEFAULT NULL AFTER event_date,
  ADD COLUMN event_end_time TIME DEFAULT NULL AFTER event_start_time,
  ADD COLUMN dining_time TIME DEFAULT NULL AFTER event_end_time,
  ADD COLUMN occasion VARCHAR(100) DEFAULT NULL AFTER guest_count,
  ADD COLUMN motif VARCHAR(160) DEFAULT NULL AFTER occasion,
  ADD COLUMN special_requests TEXT DEFAULT NULL AFTER motif,
  ADD COLUMN cancellation_reason VARCHAR(1000) DEFAULT NULL AFTER special_requests,
  ADD COLUMN cancellation_fee DECIMAL(10,2) DEFAULT NULL AFTER cancellation_reason,
  ADD COLUMN refund_due DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER cancellation_fee,
  ADD COLUMN cancelled_at DATETIME DEFAULT NULL AFTER cancellation_fee,
  ADD COLUMN cancelled_by INT UNSIGNED DEFAULT NULL AFTER cancelled_at,
  ADD COLUMN down_payment_due DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER total_price,
  ADD COLUMN amount_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER down_payment_due,
  ADD COLUMN balance_due DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER amount_paid,
  ADD COLUMN payment_status ENUM('Pending', 'Partially Paid', 'Paid in Full', 'Refunded') NOT NULL DEFAULT 'Pending' AFTER payment_method,
  ADD COLUMN delivery_status ENUM('Not Started','Preparing','On the Way','Delivered') NOT NULL DEFAULT 'Not Started' AFTER status,
  ADD KEY idx_orders_customer (customer_id),
  ADD KEY idx_orders_package (package_id),
  ADD KEY idx_orders_schedule (event_date, event_start_time, event_end_time),
  ADD CONSTRAINT fk_orders_customer FOREIGN KEY (customer_id) REFERENCES customers (customer_id) ON DELETE SET NULL,
  ADD CONSTRAINT fk_orders_package FOREIGN KEY (package_id) REFERENCES catering_packages (package_id) ON DELETE SET NULL,
  ADD CONSTRAINT fk_orders_cancelled_by FOREIGN KEY (cancelled_by) REFERENCES users (user_id) ON DELETE SET NULL;

ALTER TABLE order_items
  ADD COLUMN item_name_snapshot VARCHAR(255) DEFAULT NULL AFTER menu_item_id,
  ADD COLUMN unit_price_snapshot DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER item_name_snapshot,
  ADD COLUMN customization_details TEXT DEFAULT NULL AFTER unit_price_snapshot;

UPDATE orders
SET status = CASE
  WHEN status = 'Delivered' THEN 'Completed'
  WHEN status = 'Pending Payment' THEN 'Pending'
  WHEN status IN ('Pending', 'Confirmed', 'Processing', 'Completed', 'Cancelled', 'Rescheduled') THEN status
  ELSE 'Pending'
END;

ALTER TABLE orders
  MODIFY COLUMN status ENUM('Pending', 'Confirmed', 'Processing', 'Completed', 'Cancelled', 'Rescheduled') NOT NULL DEFAULT 'Pending';

CREATE TABLE IF NOT EXISTS payments (
  payment_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id INT(11) NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  payment_method ENUM('Cash', 'GCash', 'Bank Transfer', 'Check') NOT NULL,
  payment_date DATETIME NOT NULL,
  reference_number VARCHAR(120) DEFAULT NULL,
  receipt_details VARCHAR(500) DEFAULT NULL,
  status ENUM('Recorded', 'Voided') NOT NULL DEFAULT 'Recorded',
  recorded_by INT UNSIGNED DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (payment_id),
  KEY idx_payments_order (order_id),
  CONSTRAINT fk_payments_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
  CONSTRAINT fk_payments_user FOREIGN KEY (recorded_by) REFERENCES users (user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS events (
  event_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id INT(11) NOT NULL,
  event_date DATE NOT NULL,
  start_time TIME NOT NULL,
  end_time TIME NOT NULL,
  venue VARCHAR(500) NOT NULL,
  schedule_status ENUM('Tentative', 'Confirmed', 'Cancelled', 'Completed') NOT NULL DEFAULT 'Tentative',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (event_id),
  UNIQUE KEY uq_events_order (order_id),
  KEY idx_events_schedule (event_date, start_time, end_time, schedule_status),
  CONSTRAINT fk_events_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS notifications (
  notification_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED DEFAULT NULL,
  customer_id INT UNSIGNED DEFAULT NULL,
  order_id INT(11) DEFAULT NULL,
  notification_type VARCHAR(50) NOT NULL,
  message VARCHAR(1000) NOT NULL,
  read_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (notification_id),
  KEY idx_notifications_user_read (user_id, read_at),
  KEY idx_notifications_customer_read (customer_id, read_at),
  CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE,
  CONSTRAINT fk_notifications_customer FOREIGN KEY (customer_id) REFERENCES customers (customer_id) ON DELETE CASCADE,
  CONSTRAINT fk_notifications_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS feedback (
  feedback_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  order_id INT(11) NOT NULL,
  customer_id INT UNSIGNED NOT NULL,
  rating TINYINT UNSIGNED DEFAULT NULL,
  comments TEXT DEFAULT NULL,
  image_path VARCHAR(500) DEFAULT NULL,
  status ENUM('New', 'Reviewed', 'Hidden') NOT NULL DEFAULT 'New',
  submitted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (feedback_id),
  UNIQUE KEY uq_feedback_order_customer (order_id, customer_id),
  CONSTRAINT fk_feedback_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
  CONSTRAINT fk_feedback_customer FOREIGN KEY (customer_id) REFERENCES customers (customer_id) ON DELETE CASCADE,
  CONSTRAINT chk_feedback_rating CHECK (rating IS NULL OR rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
  audit_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED DEFAULT NULL,
  action VARCHAR(100) NOT NULL,
  entity_type VARCHAR(80) NOT NULL,
  entity_id VARCHAR(80) NOT NULL,
  old_value LONGTEXT DEFAULT NULL,
  new_value LONGTEXT DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (audit_id),
  KEY idx_audit_entity (entity_type, entity_id),
  KEY idx_audit_user_time (user_id, created_at),
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Preserve existing orders with an initial 50% deposit requirement and balance.
UPDATE orders
SET down_payment_due = ROUND(total_price * 0.50, 2),
    balance_due = GREATEST(total_price - amount_paid, 0.00),
    payment_status = CASE WHEN amount_paid >= total_price THEN 'Paid in Full'
                          WHEN amount_paid > 0 THEN 'Partially Paid'
                          ELSE 'Pending' END;

UPDATE orders
SET payment_method = 'Cash'
WHERE payment_method IS NULL OR payment_method NOT IN ('Cash', 'GCash', 'Bank Transfer', 'Check');

ALTER TABLE orders
  MODIFY COLUMN payment_method ENUM('Cash', 'GCash', 'Bank Transfer', 'Check') NOT NULL DEFAULT 'Cash',
  ADD CONSTRAINT chk_orders_money CHECK (
    total_price >= 0.00 AND down_payment_due >= 0.00 AND amount_paid >= 0.00
    AND amount_paid <= total_price AND balance_due = total_price - amount_paid
  );

ALTER TABLE payments
  ADD CONSTRAINT chk_payments_positive CHECK (amount > 0.00);

-- Snapshot current menu details on existing order lines before menu edits.
UPDATE order_items oi
JOIN menu_items mi ON mi.id = oi.menu_item_id
SET oi.item_name_snapshot = mi.name,
    oi.unit_price_snapshot = mi.price
WHERE oi.item_name_snapshot IS NULL;

-- Owner account and settings schema
-- Apply once after scope_alignment.sql to enable the full owner account.
-- This migration adds no inventory, stock, or delivery-tracking tables.

ALTER TABLE orders
  ADD COLUMN rejected_reason VARCHAR(1000) DEFAULT NULL AFTER cancellation_reason,
  ADD COLUMN rejected_at DATETIME DEFAULT NULL AFTER cancelled_at,
  ADD COLUMN rejected_by INT UNSIGNED DEFAULT NULL AFTER cancelled_by,
  ADD KEY idx_orders_rejected_by (rejected_by),
  ADD CONSTRAINT fk_orders_rejected_by FOREIGN KEY (rejected_by) REFERENCES users (user_id) ON DELETE SET NULL,
  MODIFY COLUMN status ENUM('Pending', 'Confirmed', 'Processing', 'Completed', 'Cancelled', 'Rejected') NOT NULL DEFAULT 'Pending';

ALTER TABLE events
  MODIFY COLUMN schedule_status ENUM('Tentative', 'Confirmed', 'Cancelled', 'Rejected', 'Completed') NOT NULL DEFAULT 'Tentative';

ALTER TABLE menu_items
  ADD COLUMN image_url VARCHAR(1000) DEFAULT NULL AFTER description;

ALTER TABLE catering_packages
  ADD COLUMN maximum_guests INT UNSIGNED DEFAULT NULL AFTER minimum_guests,
  ADD COLUMN image_url VARCHAR(1000) DEFAULT NULL AFTER description;

ALTER TABLE package_items
  ADD COLUMN additional_charge DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER selection_rule;

ALTER TABLE order_items
  ADD COLUMN customization_charge DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER unit_price_snapshot;

CREATE TABLE IF NOT EXISTS system_settings (
  setting_key VARCHAR(100) NOT NULL,
  setting_value TEXT DEFAULT NULL,
  updated_by INT UNSIGNED DEFAULT NULL,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (setting_key),
  CONSTRAINT fk_settings_user FOREIGN KEY (updated_by) REFERENCES users (user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO system_settings (setting_key, setting_value) VALUES
  ('business_name', 'Riz Catering Services'),
  ('business_email', ''),
  ('business_phone', ''),
  ('business_address', ''),
  ('cancellation_policy_note', 'For cancellations, the 50% deposit is non-refundable. The 10% cancellation fee is included within that deposit, not added on top. Amounts paid above the deposit are refunded manually outside this system.'),
  ('owner_account_created', 'false');

-- Analytics, stored routines, views, and triggers
-- Riz Catering analytics and automation objects. Apply after owner_account.sql.
-- Compatible with MySQL 8+ and MariaDB 10.4+.

CREATE TABLE IF NOT EXISTS database_activity_log (
  log_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  table_name VARCHAR(64) NOT NULL,
  record_id VARCHAR(80) NOT NULL,
  action_name ENUM('INSERT','UPDATE','DELETE') NOT NULL,
  details LONGTEXT DEFAULT NULL,
  logged_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (log_id), KEY idx_db_activity_time (logged_at), KEY idx_db_activity_record (table_name,record_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

DROP VIEW IF EXISTS vw_order_customer_package;
CREATE VIEW vw_order_customer_package AS
SELECT o.id AS order_id, o.client_name, o.client_email, o.event_date, o.guest_count,
       o.status, o.payment_status, o.total_price, o.amount_paid, o.balance_due,
       p.package_name, c.customer_id, c.full_name AS account_name
FROM orders o
LEFT JOIN catering_packages p ON p.package_id=o.package_id
LEFT JOIN customers c ON c.customer_id=o.customer_id;

DROP VIEW IF EXISTS vw_package_performance;
CREATE VIEW vw_package_performance AS
SELECT p.package_id, p.package_name, p.status AS package_status,
       COUNT(o.id) AS reservation_count,
       COALESCE(SUM(CASE WHEN o.status IN ('Confirmed','Processing','Completed') THEN o.total_price ELSE 0 END),0) AS booked_revenue,
       COALESCE(AVG(o.guest_count),0) AS average_guests,
       COALESCE(SUM(oi.item_count),0) AS sold_order_lines
FROM catering_packages p
LEFT JOIN orders o ON o.package_id=p.package_id
LEFT JOIN (SELECT order_id,COUNT(*) AS item_count FROM order_items GROUP BY order_id) oi ON oi.order_id=o.id
GROUP BY p.package_id,p.package_name,p.status;

DROP VIEW IF EXISTS vw_customer_order_summary;
CREATE VIEW vw_customer_order_summary AS
SELECT c.customer_id,c.full_name,c.email,COALESCE(u.status,'Guest') AS account_status,
       COUNT(o.id) AS order_count,
       COALESCE(SUM(o.total_price),0) AS lifetime_order_value
FROM customers c
LEFT JOIN users u ON u.user_id=c.user_id
LEFT JOIN orders o ON o.customer_id=c.customer_id
GROUP BY c.customer_id,c.full_name,c.email,u.status;

-- View 4: event monitoring using the two related event and order tables.
CREATE VIEW vw_event_reservation AS
SELECT e.event_id,e.order_id,e.event_date,e.start_time,e.end_time,e.venue,e.schedule_status,
       o.client_name,o.guest_count,o.status AS order_status,o.payment_status,o.total_price
FROM events e INNER JOIN orders o ON o.id=e.order_id;

DROP FUNCTION IF EXISTS fn_order_balance;
DROP FUNCTION IF EXISTS fn_customer_label;
DROP FUNCTION IF EXISTS fn_order_can_confirm;
DROP FUNCTION IF EXISTS fn_package_item_count;
DROP PROCEDURE IF EXISTS sp_riz_monthly_sales;
DROP PROCEDURE IF EXISTS sp_order_detail_bundle;
DROP PROCEDURE IF EXISTS sp_package_setup_bundle;
DROP PROCEDURE IF EXISTS sp_riz_sales_dashboard;
DROP PROCEDURE IF EXISTS sp_customer_activity_bundle;
DROP TRIGGER IF EXISTS trg_menu_validate_insert;
DROP TRIGGER IF EXISTS trg_menu_validate_update;
DROP TRIGGER IF EXISTS trg_order_items_quantity_insert;
DROP TRIGGER IF EXISTS trg_order_items_quantity_update;
DROP TRIGGER IF EXISTS trg_orders_rules_insert;
DROP TRIGGER IF EXISTS trg_orders_rules_update;
DROP TRIGGER IF EXISTS trg_orders_audit_update;
DROP TRIGGER IF EXISTS trg_payments_log_insert;

DELIMITER $$
CREATE FUNCTION fn_order_balance(p_order_id INT)
RETURNS DECIMAL(10,2)
READS SQL DATA
BEGIN
  DECLARE v_balance DECIMAL(10,2) DEFAULT 0.00;
  SELECT GREATEST(COALESCE(total_price,0.00)-COALESCE(amount_paid,0.00),0.00)
  INTO v_balance FROM orders WHERE id=p_order_id LIMIT 1;
  RETURN v_balance;
END$$

CREATE FUNCTION fn_customer_label(p_customer_id INT)
RETURNS VARCHAR(400)
READS SQL DATA
BEGIN
  DECLARE v_label VARCHAR(400) DEFAULT NULL;
  SELECT CONCAT(TRIM(full_name),' <',email,'>') INTO v_label
  FROM customers WHERE customer_id=p_customer_id LIMIT 1;
  RETURN v_label;
END$$

CREATE FUNCTION fn_order_can_confirm(p_order_id INT)
RETURNS TINYINT
READS SQL DATA
BEGIN
  DECLARE v_ready TINYINT DEFAULT 0;
  SELECT IF(status='Pending' AND guest_count>=1 AND amount_paid>=down_payment_due
            AND balance_due=total_price-amount_paid,1,0) INTO v_ready
  FROM orders WHERE id=p_order_id LIMIT 1;
  RETURN COALESCE(v_ready,0);
END$$

-- Three processes: order header, selected menu lines, and payment history.
CREATE PROCEDURE sp_order_detail_bundle(IN p_order_id INT)
BEGIN
  SELECT * FROM vw_order_customer_package WHERE order_id=p_order_id;
  SELECT oi.id,oi.menu_item_id,COALESCE(oi.item_name_snapshot,mi.name) AS item_name,
         oi.quantity,oi.unit_price_snapshot,oi.customization_charge,oi.customization_details
  FROM order_items oi LEFT JOIN menu_items mi ON mi.id=oi.menu_item_id
  WHERE oi.order_id=p_order_id ORDER BY oi.id;
  SELECT payment_id,amount,payment_method,payment_date,reference_number,status
  FROM payments WHERE order_id=p_order_id ORDER BY payment_date,payment_id;
END$$

-- Two processes: package details and its menu choices.
CREATE PROCEDURE sp_package_setup_bundle(IN p_package_id INT)
BEGIN
  SELECT * FROM vw_package_performance WHERE package_id=p_package_id;
  SELECT pi.option_group,pi.selection_rule,pi.additional_charge,mi.id AS menu_item_id,mi.name,mi.category
  FROM package_items pi INNER JOIN menu_items mi ON mi.id=pi.menu_item_id
  WHERE pi.package_id=p_package_id ORDER BY pi.option_group,mi.name;
END$$

-- Three processes: monthly totals, order-status totals, and payment-method totals.
CREATE PROCEDURE sp_riz_sales_dashboard(IN p_year INT)
BEGIN
  SELECT DATE_FORMAT(event_date,'%Y-%m') AS sales_month,COUNT(*) AS reservations,
         COALESCE(SUM(total_price),0) AS requested_value,COALESCE(SUM(amount_paid),0) AS collected
  FROM orders WHERE YEAR(event_date)=p_year GROUP BY DATE_FORMAT(event_date,'%Y-%m') ORDER BY sales_month;
  SELECT status,COUNT(*) AS reservations,COALESCE(SUM(total_price),0) AS order_value
  FROM orders WHERE YEAR(event_date)=p_year GROUP BY status ORDER BY status;
  SELECT payment_method,COUNT(*) AS payment_count,COALESCE(SUM(amount),0) AS collected
  FROM payments WHERE YEAR(payment_date)=p_year AND status='Recorded' GROUP BY payment_method ORDER BY payment_method;
END$$

-- Three processes: customer summary, reservation history, and feedback history.
CREATE PROCEDURE sp_customer_activity_bundle(IN p_customer_id INT)
BEGIN
  SELECT * FROM vw_customer_order_summary WHERE customer_id=p_customer_id;
  SELECT o.id AS order_id,o.event_date,o.status,o.payment_status,o.total_price,p.package_name
  FROM orders o LEFT JOIN catering_packages p ON p.package_id=o.package_id
  WHERE o.customer_id=p_customer_id ORDER BY o.created_at DESC;
  SELECT f.feedback_id,f.order_id,f.rating,f.comments,f.status,f.submitted_at
  FROM feedback f WHERE f.customer_id=p_customer_id ORDER BY f.submitted_at DESC;
END$$

-- Data validation: menu names and prices must be usable.
CREATE TRIGGER trg_menu_validate_insert BEFORE INSERT ON menu_items FOR EACH ROW
BEGIN
  IF CHAR_LENGTH(TRIM(NEW.name))=0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Menu item name cannot be blank'; END IF;
  IF NEW.price<=0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Menu item price must be greater than zero'; END IF;
END$$
CREATE TRIGGER trg_menu_validate_update BEFORE UPDATE ON menu_items FOR EACH ROW
BEGIN
  IF CHAR_LENGTH(TRIM(NEW.name))=0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Menu item name cannot be blank'; END IF;
  IF NEW.price<=0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Menu item price must be greater than zero'; END IF;
END$$

-- Product quantity validation: order line quantities must be positive integers.
CREATE TRIGGER trg_order_items_quantity_insert BEFORE INSERT ON order_items FOR EACH ROW
BEGIN
  IF NEW.quantity IS NULL OR NEW.quantity<1 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Product quantity must be at least 1';
  END IF;
END$$
CREATE TRIGGER trg_order_items_quantity_update BEFORE UPDATE ON order_items FOR EACH ROW
BEGIN
  IF NEW.quantity IS NULL OR NEW.quantity<1 THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Product quantity must be at least 1';
  END IF;
END$$

-- Business rules: sane reservation size, valid amounts, deposits before confirmation.
CREATE TRIGGER trg_orders_rules_insert BEFORE INSERT ON orders FOR EACH ROW
BEGIN
  IF NEW.guest_count<1 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='A reservation must have at least one guest'; END IF;
  IF NEW.total_price<0 OR NEW.amount_paid<0 OR NEW.amount_paid>NEW.total_price THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Reservation payment amounts are invalid'; END IF;
  IF NEW.status IN ('Confirmed','Processing','Completed') AND NEW.amount_paid<NEW.down_payment_due THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='A 50 percent deposit is required before confirmation'; END IF;
  IF NEW.status='Completed' AND NEW.balance_due>0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='The reservation must be fully paid before completion'; END IF;
END$$
CREATE TRIGGER trg_orders_rules_update BEFORE UPDATE ON orders FOR EACH ROW
BEGIN
  IF NEW.guest_count<1 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='A reservation must have at least one guest'; END IF;
  IF NEW.total_price<0 OR NEW.amount_paid<0 OR NEW.amount_paid>NEW.total_price THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Reservation payment amounts are invalid'; END IF;
  IF NEW.status IN ('Confirmed','Processing','Completed') AND NEW.amount_paid<NEW.down_payment_due THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='A 50 percent deposit is required before confirmation'; END IF;
  IF NEW.status='Completed' AND NEW.balance_due>0 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='The reservation must be fully paid before completion'; END IF;
END$$

-- Auditing changes: retain before/after status, price, and payment values.
CREATE TRIGGER trg_orders_audit_update AFTER UPDATE ON orders FOR EACH ROW
BEGIN
  IF NOT (OLD.status <=> NEW.status) OR NOT (OLD.total_price <=> NEW.total_price) OR NOT (OLD.amount_paid <=> NEW.amount_paid) OR NOT (OLD.balance_due <=> NEW.balance_due) THEN
    INSERT INTO audit_logs(user_id,action,entity_type,entity_id,old_value,new_value)
    VALUES(NULL,'DATABASE_UPDATE','Order',CAST(NEW.id AS CHAR),
      JSON_OBJECT('status',OLD.status,'total_price',OLD.total_price,'amount_paid',OLD.amount_paid,'balance_due',OLD.balance_due),
      JSON_OBJECT('status',NEW.status,'total_price',NEW.total_price,'amount_paid',NEW.amount_paid,'balance_due',NEW.balance_due));
  END IF;
END$$

-- Automatic data logging: every payment insert leaves an immutable activity record.
CREATE TRIGGER trg_payments_log_insert AFTER INSERT ON payments FOR EACH ROW
BEGIN
  INSERT INTO database_activity_log(table_name,record_id,action_name,details)
  VALUES('payments',CAST(NEW.payment_id AS CHAR),'INSERT',
    JSON_OBJECT('order_id',NEW.order_id,'amount',NEW.amount,'method',NEW.payment_method,'status',NEW.status));
END$$
DELIMITER ;
