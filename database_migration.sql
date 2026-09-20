USE sa_design;

-- ============================================
-- 1. Make order_number unique
-- ============================================

ALTER TABLE orders
DROP INDEX idx_orders_order_number;

ALTER TABLE orders
ADD UNIQUE KEY unique_order_number (order_number);


-- ============================================
-- 2. Add foreign key: orders.user_id -> customers.id
-- ============================================

ALTER TABLE orders
ADD CONSTRAINT fk_orders_customer
FOREIGN KEY (user_id)
REFERENCES customers(id)
ON UPDATE CASCADE
ON DELETE SET NULL;


-- ============================================
-- 3. Add foreign key: orders.product_id -> products.id
-- ============================================

ALTER TABLE orders
ADD CONSTRAINT fk_orders_product
FOREIGN KEY (product_id)
REFERENCES products(id)
ON UPDATE CASCADE
ON DELETE SET NULL;


-- ============================================
-- 4. Make important order fields required
-- ============================================

ALTER TABLE orders
MODIFY order_number VARCHAR(40) NOT NULL,
MODIFY quantity INT NOT NULL,
MODIFY unit_price DECIMAL(10,2) NOT NULL,
MODIFY order_total DECIMAL(10,2) NOT NULL,
MODIFY shipping_fee DECIMAL(10,2) NOT NULL,
MODIFY status VARCHAR(30) NOT NULL DEFAULT 'Pending',
MODIFY created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;


-- ============================================
-- 5. Make product information required
-- ============================================

ALTER TABLE products
MODIFY name VARCHAR(100) NOT NULL,
MODIFY price DECIMAL(10,2) NOT NULL,
MODIFY image VARCHAR(255) NULL,
MODIFY description TEXT NULL;


-- ============================================
-- 6. Add indexes for faster checkout/order history
-- ============================================

CREATE INDEX idx_orders_product_id
ON orders(product_id);

CREATE INDEX idx_orders_status
ON orders(status);

CREATE INDEX idx_orders_created_at
ON orders(created_at);


-- ============================================
-- 7. Ensure product catalogue is correct
-- ============================================

INSERT INTO products
(id, name, price, image, description)
VALUES
(1, 'Custom Self-Inking Stamp', 25.00, 'cop.png',
 'Custom self-inking stamp'),

(2, 'Custom Sublimation Apparel', 29.00, 'baju1.jpg',
 'Custom sublimation apparel'),

(3, 'Banner & Bunting Printing', 15.00, 'banting1.jpeg',
 'Banner and bunting printing'),

(4, 'Premium Wedding & Business Cards', 28.00, 'card.jpeg',
 'Premium business and wedding cards'),

(5, 'Outdoor Promotional Windflag', 190.00, 'windflag1.jpeg',
 'Outdoor promotional windflag'),

(6, 'Mirrokote Product Stickers', 62.00, 'sticker1.jpeg',
 'Mirrokote product stickers')

ON DUPLICATE KEY UPDATE
name = VALUES(name),
price = VALUES(price),
image = VALUES(image),
description = VALUES(description);

-- Allow customers to attach one product photo to a review.
ALTER TABLE product_reviews
ADD COLUMN review_image VARCHAR(255) NULL AFTER review_text;

-- Allow a customer to submit multiple reviews for the same product.
ALTER TABLE product_reviews
DROP INDEX unique_customer_product;

-- Store custom request payment choice and ToyyibPay result.
ALTER TABLE custom_request
ADD COLUMN status VARCHAR(30) NOT NULL DEFAULT 'Pending' AFTER price,
ADD COLUMN payment_method VARCHAR(30) NULL AFTER status,
ADD COLUMN payment_status VARCHAR(30) NOT NULL DEFAULT 'Pending' AFTER payment_method,
ADD COLUMN toyyibpay_billcode VARCHAR(100) NULL AFTER payment_status,
ADD COLUMN payment_reference VARCHAR(100) NULL AFTER toyyibpay_billcode;

-- Store ToyyibPay payment tracking details for product orders.
ALTER TABLE orders
ADD COLUMN toyyibpay_billcode VARCHAR(100) NULL AFTER payment_status,
ADD COLUMN payment_reference VARCHAR(100) NULL AFTER toyyibpay_billcode;
