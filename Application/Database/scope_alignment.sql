-- Scope-alignment migration for the Riz Catering Food Ordering System.
-- Apply once after importing riz_catering.sql. This adds no inventory or stock tables.

CREATE TABLE IF NOT EXISTS users (
  user_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  full_name VARCHAR(120) NOT NULL,
  email VARCHAR(254) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('Customer', 'Admin') NOT NULL DEFAULT 'Customer',
  status ENUM('Active', 'Disabled') NOT NULL DEFAULT 'Active',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id),
  UNIQUE KEY uq_users_email (email)
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
  ADD COLUMN cancelled_at DATETIME DEFAULT NULL AFTER cancellation_fee,
  ADD COLUMN cancelled_by INT UNSIGNED DEFAULT NULL AFTER cancelled_at,
  ADD COLUMN down_payment_due DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER total_price,
  ADD COLUMN amount_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER down_payment_due,
  ADD COLUMN balance_due DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER amount_paid,
  ADD COLUMN payment_status ENUM('Pending', 'Partially Paid', 'Paid in Full', 'Refunded') NOT NULL DEFAULT 'Pending' AFTER payment_method,
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
