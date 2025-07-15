<?php
// Deteksi environment (online/local)
$is_online = $_SERVER['HTTP_HOST'] !== 'localhost';

if ($is_online) {
    // Konfigurasi untuk hosting online
    $host = 'nama_host_database_anda'; // Ganti dengan host database hosting
    $dbname = 'nama_database_anda';    // Ganti dengan nama database di hosting
    $username = 'username_database_anda'; // Ganti dengan username database hosting
    $password = 'password_database_anda'; // Ganti dengan password database hosting
} else {
    // Konfigurasi untuk development local
    $host = 'localhost';
    $dbname = 'toko_kue';
    $username = 'root';
    $password = '';
}

try {
    // First connect without database selected
    $db = new PDO("mysql:host=$host", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $db->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
    $db->exec("USE `$dbname`");
    
    // Check if is_admin column exists in users table
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'is_admin'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE users ADD COLUMN is_admin BOOLEAN DEFAULT FALSE");
    }
    
    // Create users table if it doesn't exist
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nama VARCHAR(100) NOT NULL,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        is_admin BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Create default admin user if not exists
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE is_admin = 1");
    if ($stmt->execute() && $stmt->fetchColumn() == 0) {
        $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (nama, username, email, password, is_admin) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['Administrator', 'admin', 'admin@tokokue.com', $admin_password, true]);
    }

    // Create kue table if it doesn't exist
    $db->exec("CREATE TABLE IF NOT EXISTS kue (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nama VARCHAR(100) NOT NULL,
        deskripsi TEXT,
        harga DECIMAL(10,2) NOT NULL,
        gambar VARCHAR(255),
        stok INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Insert sample cakes if the table is empty
    $stmt = $db->query("SELECT COUNT(*) FROM kue");
    if ($stmt->fetchColumn() == 0) {
        $db->exec("INSERT INTO kue (nama, deskripsi, harga, gambar, stok) VALUES
            ('Kue Tart Coklat', 'Kue tart dengan lapisan coklat premium', 250000, 'images/kue tart coklat.jpeg', 10),
            ('Kue Cupcake Vanilla', 'Cupcake dengan krim vanilla lembut', 25000, 'images/kue cupcake vanilla.jpeg', 50),
            ('Kue Black Forest', 'Kue tart dengan taburan coklat dan cherry', 300000, 'images/kue black forest.jpeg', 8),
            ('Kue Rainbow', 'Kue berlapis dengan warna pelangi', 200000, 'images/kue rainbow.jpeg', 15),
            ('Kue Brownies', 'Brownies coklat yang lembut dan moist', 150000, 'images/kue brownies.jpeg', 20)
        ");
    }

    // Update existing image paths if needed
    $db->exec("UPDATE kue SET 
        gambar = CASE 
            WHEN nama = 'Kue Tart Coklat' THEN 'images/kue tart coklat.jpeg'
            WHEN nama = 'Kue Cupcake Vanilla' THEN 'images/kue cupcake vanilla.jpeg'
            WHEN nama = 'Kue Black Forest' THEN 'images/kue black forest.jpeg'
            WHEN nama = 'Kue Rainbow' THEN 'images/kue rainbow.jpeg'
            WHEN nama = 'Kue Brownies' THEN 'images/kue brownies.jpeg'
        END
    ");

    // Create orders table if it doesn't exist
    $db->exec("CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        status ENUM('pending', 'paid', 'completed', 'cancelled') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");

    // Create order_items table if it doesn't exist
    $db->exec("CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        kue_id INT NOT NULL,
        jumlah INT NOT NULL,
        harga DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id),
        FOREIGN KEY (kue_id) REFERENCES kue(id)
    )");

    // Create payments table if it doesn't exist
    $db->exec("CREATE TABLE IF NOT EXISTS payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        payment_method ENUM('transfer', 'cash') NOT NULL,
        bank_name VARCHAR(50),
        account_number VARCHAR(50),
        account_name VARCHAR(100),
        proof_image VARCHAR(255),
        status ENUM('pending', 'confirmed', 'rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(id)
    )");
    
    // Reconnect with database selected
    $db = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Add category column to kue table if it doesn't exist
    $stmt = $db->query("SHOW COLUMNS FROM kue LIKE 'kategori'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE kue ADD COLUMN kategori VARCHAR(50) DEFAULT 'basah'");
    }

    // Update existing cakes to have 'basah' category
    $db->exec("UPDATE kue SET kategori = 'basah' WHERE kategori IS NULL");

    // Insert kue kering if not exists
    $stmt = $db->prepare("SELECT COUNT(*) FROM kue WHERE kategori = 'kering'");
    if ($stmt->execute() && $stmt->fetchColumn() == 0) {
        $db->exec("INSERT INTO kue (nama, deskripsi, harga, gambar, stok, kategori) VALUES
            ('Nastar', 'Kue kering dengan isian selai nanas premium', 100000, 'images/Nastar.jpeg', 50, 'kering'),
            ('Kastengel', 'Kue kering keju yang lezat dan gurih', 120000, 'images/kastengel.jpeg', 40, 'kering'),
            ('Putri Salju', 'Kue kering dengan taburan gula halus yang lembut', 90000, 'images/putri salju.jpeg', 45, 'kering'),
            ('Lidah Kucing', 'Kue kering renyah berbentuk lidah kucing', 80000, 'images/lidah kucing.jpeg', 60, 'kering'),
            ('Sagu Keju', 'Kue kering dengan taburan keju yang gurih', 85000, 'images/sagu keju.jpeg', 55, 'kering')
        ");
    }

    // Update existing kue kering image paths if needed
    $db->exec("UPDATE kue SET 
        gambar = CASE 
            WHEN nama = 'Nastar' THEN 'images/Nastar.jpeg'
            WHEN nama = 'Kastengel' THEN 'images/kastengel.jpeg'
            WHEN nama = 'Putri Salju' THEN 'images/putri salju.jpeg'
            WHEN nama = 'Lidah Kucing' THEN 'images/lidah kucing.jpeg'
            WHEN nama = 'Sagu Keju' THEN 'images/sagu keju.jpeg'
            ELSE gambar
        END
    WHERE kategori = 'kering'");
} catch(PDOException $e) {
    echo "Koneksi gagal: " . $e->getMessage();
    exit();
}
?>