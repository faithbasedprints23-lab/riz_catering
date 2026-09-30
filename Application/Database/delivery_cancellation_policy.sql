-- Apply once after the current scope and owner account migrations.
-- Adds delivery progress to customer orders and applies the documented cancellation arithmetic.
-- Cancellation policy: retain the non-refundable 50% deposit plus a 10% fee on order total.
-- Any paid amount above those retained amounts is a refund due, paid manually outside the system.
ALTER TABLE orders
  ADD COLUMN delivery_status ENUM('Not Started','Preparing','On the Way','Delivered') NOT NULL DEFAULT 'Not Started' AFTER status,
  ADD COLUMN refund_due DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER cancellation_fee;

UPDATE system_settings
SET setting_value='For cancellations, the 50% deposit is non-refundable. The 10% cancellation fee is included within that deposit, not added on top. Amounts paid above the deposit are refunded manually outside this system.'
WHERE setting_key='cancellation_policy_note';
