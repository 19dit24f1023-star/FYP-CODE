USE sa_design;

CREATE TABLE IF NOT EXISTS wallet_accounts (
    customer_id INT NOT NULL,
    balance DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (customer_id),
    CONSTRAINT fk_wallet_customer FOREIGN KEY (customer_id) REFERENCES customers(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS wallet_transactions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    customer_id INT NOT NULL,
    type ENUM('topup','order_payment','refund','adjustment') NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    reference VARCHAR(100) NOT NULL,
    status ENUM('pending','completed','failed') NOT NULL DEFAULT 'pending',
    description VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_wallet_reference (reference),
    KEY idx_wallet_customer (customer_id, created_at),
    CONSTRAINT fk_wallet_tx_customer FOREIGN KEY (customer_id) REFERENCES customers(id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
