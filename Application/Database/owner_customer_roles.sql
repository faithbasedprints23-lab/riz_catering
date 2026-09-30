-- Apply once to an existing database after deploying the owner/customer-only app.
-- Preserve orders and payments, but remove old staff accounts as transaction
-- actors and route their unreadable staff-only notices to the owner.
UPDATE payments p JOIN users u ON u.user_id=p.recorded_by SET p.recorded_by=NULL WHERE u.role='Staff';
UPDATE audit_logs a JOIN users u ON u.user_id=a.user_id SET a.user_id=NULL WHERE u.role='Staff';
UPDATE notifications n JOIN users u ON u.user_id=n.user_id SET n.user_id=(SELECT user_id FROM users WHERE role='Admin' AND status='Active' LIMIT 1) WHERE u.role='Staff';
-- Disable legacy staff credentials and change their role without deleting history.
UPDATE users SET status='Disabled', role='Customer' WHERE role='Staff';
ALTER TABLE users MODIFY COLUMN role ENUM('Customer', 'Admin') NOT NULL DEFAULT 'Customer';
