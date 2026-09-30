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

DROP VIEW IF EXISTS vw_event_reservation;
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
