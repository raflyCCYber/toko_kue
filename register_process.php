<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = $_POST['nama'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        echo "<script>alert('Password tidak cocok!'); window.location='register.php';</script>";
        exit();
    }

    // Check if username already exists
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->rowCount() > 0) {
        echo "<script>alert('Username sudah digunakan!'); window.location='register.php';</script>";
        exit();
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        $stmt = $db->prepare("INSERT INTO users (nama, username, email, password) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nama, $username, $email, $hashed_password]);
        echo "<script>alert('Registrasi berhasil! Silakan login.'); window.location='index.html';</script>";
    } catch(PDOException $e) {
        echo "<script>alert('Registrasi gagal! Silakan coba lagi.'); window.location='register.php';</script>";
    }
}
?>