-- Apply once after scope_alignment.sql to enable the full owner account.
-- This migration adds no inventory, stock, or delivery-tracking tables.

-- Database-level single-owner guard. Admin represents the owner role in this app.
-- Resolve any duplicate Admin rows before applying this migration.
ALTER TABLE users
  ADD COLUMN owner_account_slot TINYINT GENERATED ALWAYS AS (IF(role = 'Admin', 1, NULL)) STORED,
  ADD UNIQUE KEY uq_single_owner_account (owner_account_slot);

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
  ('cancellation_policy_note', 'The cancellation fee arithmetic is not configured. Confirm the business rule before calculating refunds.'),
  ('owner_account_created', 'false');

UPDATE system_settings
SET setting_value='true'
WHERE setting_key='owner_account_created'
  AND EXISTS (SELECT 1 FROM users WHERE role='Admin');
