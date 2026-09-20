-- Authentication schema required by index.php.
-- Safe to run more than once on MariaDB 10.4+.
USE sa_design;

ALTER TABLE customers
    ADD COLUMN IF NOT EXISTS remember_token VARCHAR(64) NULL,
    ADD COLUMN IF NOT EXISTS remember_expires DATETIME NULL,
    ADD COLUMN IF NOT EXISTS reset_token VARCHAR(64) NULL,
    ADD COLUMN IF NOT EXISTS reset_expires DATETIME NULL,
    ADD INDEX IF NOT EXISTS idx_customers_remember_token (remember_token),
    ADD INDEX IF NOT EXISTS idx_customers_reset_token (reset_token);

ALTER TABLE admin
    ADD COLUMN IF NOT EXISTS remember_token VARCHAR(64) NULL,
    ADD COLUMN IF NOT EXISTS remember_expires DATETIME NULL,
    ADD COLUMN IF NOT EXISTS reset_token VARCHAR(64) NULL,
    ADD COLUMN IF NOT EXISTS reset_expires DATETIME NULL,
    ADD INDEX IF NOT EXISTS idx_admin_remember_token (remember_token),
    ADD INDEX IF NOT EXISTS idx_admin_reset_token (reset_token);
