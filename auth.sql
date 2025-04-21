CREATE TABLE licenses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    license_key VARCHAR(255) UNIQUE NOT NULL,
    customer_email VARCHAR(255) NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    max_users INT NOT NULL,
    valid_until DATETIME NOT NULL,
    is_disabled BOOLEAN DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_verified DATETIME NULL
);

-- 示例数据
INSERT INTO licenses (license_key, customer_email, product_name, max_users, valid_until)
VALUES ('ABC-123-XYZ-789', 'user@example.com', 'Premium Pro', 10, '2024-12-31 23:59:59');
