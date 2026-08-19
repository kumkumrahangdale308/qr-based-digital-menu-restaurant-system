-- Restaurant System V1 Final migration
-- Run after importing the provided restaurant_system.sql dump.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

CREATE TABLE IF NOT EXISTS restaurant_tables (
  id INT(11) NOT NULL AUTO_INCREMENT,
  table_number INT(11) NOT NULL,
  capacity INT(11) NOT NULL DEFAULT 4,
  qr_code VARCHAR(255) NOT NULL,
  status ENUM('AVAILABLE','OCCUPIED','RESERVED','OUT_OF_SERVICE') NOT NULL DEFAULT 'AVAILABLE',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_restaurant_tables_number (table_number),
  KEY idx_restaurant_tables_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO restaurant_tables (table_number, capacity, qr_code, status)
SELECT n.table_number, 4, CONCAT('menu.php?table=', n.table_number), 'AVAILABLE'
FROM (
  SELECT 1 table_number UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
  UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10
  UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION SELECT 15
  UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION SELECT 20
) n
LEFT JOIN restaurant_tables rt ON rt.table_number = n.table_number
WHERE rt.id IS NULL;

ALTER TABLE orders
  ADD COLUMN IF NOT EXISTS table_id INT(11) NULL AFTER id,
  ADD COLUMN IF NOT EXISTS payment_method VARCHAR(30) NOT NULL DEFAULT 'Counter' AFTER total_amount,
  ADD COLUMN IF NOT EXISTS payment_status ENUM('Pending','Requested','Paid','Cancelled') NOT NULL DEFAULT 'Pending' AFTER payment_method,
  ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP NULL DEFAULT NULL AFTER start_time;

UPDATE orders o
LEFT JOIN restaurant_tables rt ON rt.table_number = o.table_number
SET o.table_id = rt.id
WHERE o.table_id IS NULL AND rt.id IS NOT NULL;

ALTER TABLE orders
  MODIFY status ENUM('New','Accepted','Preparing','Ready','Served','Completed') NOT NULL DEFAULT 'New';

ALTER TABLE orders
  ADD INDEX IF NOT EXISTS idx_orders_status (status),
  ADD INDEX IF NOT EXISTS idx_orders_table_number_id (table_number, id),
  ADD INDEX IF NOT EXISTS idx_orders_table_id (table_id),
  ADD INDEX IF NOT EXISTS idx_orders_time (order_time);

ALTER TABLE orders
  ADD CONSTRAINT fk_orders_restaurant_table
  FOREIGN KEY (table_id) REFERENCES restaurant_tables(id)
  ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE order_items
  ADD COLUMN IF NOT EXISTS menu_item_id INT(11) NULL AFTER order_id,
  ADD INDEX IF NOT EXISTS idx_order_items_category (category),
  ADD INDEX IF NOT EXISTS idx_order_items_menu_item (menu_item_id);

ALTER TABLE menu_items
  ADD INDEX IF NOT EXISTS idx_menu_items_availability (availability),
  ADD INDEX IF NOT EXISTS idx_menu_items_name (item_name);

CREATE TABLE IF NOT EXISTS waiter_requests (
  id INT(11) NOT NULL AUTO_INCREMENT,
  table_id INT(11) NOT NULL,
  request_type ENUM('Need Water','Need Spoon','Need Tissue','Need Assistance','Need Bill') NOT NULL,
  status ENUM('Open','Accepted','Completed','Cancelled') NOT NULL DEFAULT 'Open',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_waiter_requests_table (table_id),
  KEY idx_waiter_requests_status (status),
  CONSTRAINT fk_waiter_requests_table FOREIGN KEY (table_id)
    REFERENCES restaurant_tables(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS waiter_actions (
  id INT(11) NOT NULL AUTO_INCREMENT,
  order_id INT(11) NOT NULL,
  action VARCHAR(100) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_waiter_actions_order (order_id),
  CONSTRAINT fk_waiter_actions_order FOREIGN KEY (order_id)
    REFERENCES orders(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS bills (
  id INT(11) NOT NULL AUTO_INCREMENT,
  order_id INT(11) NOT NULL,
  bill_number VARCHAR(40) NOT NULL,
  subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  gst_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  payment_method VARCHAR(30) NOT NULL DEFAULT 'Counter',
  payment_status ENUM('Pending','Paid','Cancelled') NOT NULL DEFAULT 'Pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_bills_order (order_id),
  UNIQUE KEY uq_bills_number (bill_number),
  KEY idx_bills_payment_status (payment_status),
  CONSTRAINT fk_bills_order FOREIGN KEY (order_id)
    REFERENCES orders(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

UPDATE order_items
SET category = 'NonVeg'
WHERE LOWER(category) IN ('non-veg', 'nonveg', 'non veg');

UPDATE order_items
SET category = 'Veg'
WHERE LOWER(category) = 'veg';

COMMIT;
