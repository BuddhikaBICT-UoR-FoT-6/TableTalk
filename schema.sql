CREATE DATABASE IF NOT EXISTS tabletalk;
USE tabletalk;

-- users (id, name, email, password_hash, role: customer/chef/admin)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE,
    password_hash VARCHAR(255),
    role ENUM('customer', 'chef', 'admin') NOT NULL DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- menu_items (id, name, price, category, is_available)
CREATE TABLE IF NOT EXISTS menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    category VARCHAR(100) NOT NULL,
    image_url VARCHAR(255),
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- orders (id, table_id, status: pending/preparing/ready/served/paid, estimated_wait_minutes, total_amount, created_at)
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_id VARCHAR(50) NOT NULL,
    status ENUM('pending', 'preparing', 'ready', 'served', 'paid') DEFAULT 'pending',
    estimated_wait_minutes INT DEFAULT 15,
    total_amount DECIMAL(10, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- order_items (id, order_id, menu_item_id, quantity, subtotal)
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    menu_item_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    subtotal DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE
);

-- payments (id, order_id, amount, method, status, paid_at)
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    method VARCHAR(50) DEFAULT 'credit_card',
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    paid_at TIMESTAMP NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- feedback (id, order_id, rating 1-5, comment, created_at)
CREATE TABLE IF NOT EXISTS feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- Seed Data

-- Users (password is 'password123' for chef/admin)
INSERT INTO users (name, email, password_hash, role) VALUES 
('Chef Gordon', 'chef@tabletalk.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'chef'),
('Admin Boss', 'admin@tabletalk.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Menu Items
INSERT INTO menu_items (name, description, price, category, image_url, is_available) VALUES
('Truffle Fries', 'Crispy fries with truffle oil and parmesan', 8.50, 'Appetizers', 'https://images.unsplash.com/photo-1573080496219-bb080dd4f877?auto=format&fit=crop&q=80&w=400', TRUE),
('Garlic Bread', 'Toasted baguette with garlic butter', 5.00, 'Appetizers', 'https://images.unsplash.com/photo-1573140247632-f8fd74997d5c?auto=format&fit=crop&q=80&w=400', TRUE),
('Margherita Pizza', 'Classic tomato, mozzarella, and basil', 14.00, 'Mains', 'https://images.unsplash.com/photo-1574071318508-1cdbab80d002?auto=format&fit=crop&q=80&w=400', TRUE),
('Wagyu Burger', 'Premium beef patty, cheddar, lettuce, tomato', 18.50, 'Mains', 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&q=80&w=400', TRUE),
('Caesar Salad', 'Romaine, croutons, parmesan, caesar dressing', 12.00, 'Salads', 'https://images.unsplash.com/photo-1550304943-4f24f54ddde9?auto=format&fit=crop&q=80&w=400', TRUE),
('Chocolate Lava Cake', 'Warm chocolate cake with a molten center', 9.00, 'Desserts', 'https://images.unsplash.com/photo-1624353365286-3f8d62daad51?auto=format&fit=crop&q=80&w=400', TRUE),
('Craft Cola', 'Artisanal organic cola', 4.00, 'Beverages', 'https://images.unsplash.com/photo-1622483767028-3f66f32aef97?auto=format&fit=crop&q=80&w=400', TRUE),
('Lemonade', 'Freshly squeezed lemonade', 4.50, 'Beverages', 'https://images.unsplash.com/photo-1513558161293-cdaf765ed2fd?auto=format&fit=crop&q=80&w=400', TRUE);
