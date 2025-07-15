<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    $username = isset($_POST['username']) ? $_POST['username'] : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($username) || empty($password)) {
        echo "<script>alert('Username dan password harus diisi!'); window.location='index.html';</script>";
        exit;
    }

    try {
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['is_admin'] = $user['is_admin'];

            if ($user['is_admin']) {
                header("Location: admin_payments.php");
            } else {
                header("Location: dashboard.php");
            }
            exit;
        } else {
            echo "<script>alert('Username atau password salah!'); window.location='index.html';</script>";
        }
    } catch(PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        echo "<script>alert('Error sistem!'); window.location='index.html';</script>";
    }
} else {
    header('HTTP/1.1 405 Method Not Allowed');
    echo "<script>alert('Method tidak diizinkan!'); window.location='index.html';</script>";
}
?>