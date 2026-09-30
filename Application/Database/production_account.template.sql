-- Replace CHANGE_ME with a newly generated long random password before running.
-- Run as the database administrator, not as the application account.
CREATE USER 'riz_catering_app'@'localhost' IDENTIFIED BY 'CHANGE_ME';
GRANT SELECT, INSERT, UPDATE, DELETE, EXECUTE ON riz_catering.* TO 'riz_catering_app'@'localhost';
FLUSH PRIVILEGES;

-- Use this when importing/upgrading the schema: run migrations as a separate
-- deployment administrator, then keep the application account restricted to
-- ordinary application operations. Add CREATE/ALTER privileges only if this
-- application itself is extended to perform schema migrations (it currently is not).
