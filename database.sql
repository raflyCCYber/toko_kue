-- Create the database
CREATE DATABASE IF NOT EXISTS toko_kue;
USE toko_kue;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create kue (cake) table
CREATE TABLE IF NOT EXISTS kue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    harga DECIMAL(10,2) NOT NULL,
    gambar VARCHAR(255),
    stok INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample cakes
INSERT INTO kue (nama, deskripsi, harga, gambar, stok) VALUES
('Kue Tart Coklat', 'Kue tart dengan lapisan coklat premium', 250000, 'images/tart-coklat.jpg', 10),
('Kue Cupcake Vanilla', 'Cupcake dengan krim vanilla lembut', 25000, 'images/cupcake-vanilla.jpg', 50),
('Kue Black Forest', 'Kue tart dengan taburan coklat dan cherry', 300000, 'images/black-forest.jpg', 8),
('Kue Rainbow', 'Kue berlapis dengan warna pelangi', 200000, 'images/rainbow-cake.jpg', 15),
('Kue Brownies', 'Brownies coklat yang lembut dan moist', 150000, 'images/brownies.jpg', 20);

-- Create orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'paid', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Create order_items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    kue_id INT NOT NULL,
    jumlah INT NOT NULL,
    harga DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (kue_id) REFERENCES kue(id)
);