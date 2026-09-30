-- Run as the database administrator, not as the application account.
-- Replace CHANGE_ME with a long unique password and save it in a private
-- MySQL defaults file outside the website root for the scheduled-task identity.
CREATE USER 'riz_catering_backup'@'localhost' IDENTIFIED BY 'CHANGE_ME';
GRANT SELECT, SHOW VIEW, TRIGGER, EVENT, LOCK TABLES ON riz_catering.* TO 'riz_catering_backup'@'localhost';
FLUSH PRIVILEGES;
