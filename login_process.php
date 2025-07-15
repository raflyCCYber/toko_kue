<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

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
        } else {
            echo "<script>alert('Username atau password salah!'); window.location='index.html';</script>";
        }
    } catch(PDOException $e) {
        echo "<script>alert('Error sistem!'); window.location='index.html';</script>";
    }
}
?>