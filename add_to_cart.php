<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kue_id = $_POST['kue_id'];
    $jumlah = $_POST['jumlah'];

    // Get cake details
    $stmt = $db->prepare("SELECT * FROM kue WHERE id = ?");
    $stmt->execute([$kue_id]);
    $kue = $stmt->fetch();

    if ($kue) {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        // Check if item already exists in cart
        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['kue_id'] == $kue_id) {
                $item['jumlah'] += $jumlah;
                $found = true;
                break;
            }
        }

        if (!$found) {
            $_SESSION['cart'][] = [
                'kue_id' => $kue_id,
                'nama' => $kue['nama'],
                'harga' => $kue['harga'],
                'jumlah' => $jumlah
            ];
        }

        echo "<script>alert('Kue berhasil ditambahkan ke keranjang!'); window.location='dashboard.php';</script>";
    } else {
        echo "<script>alert('Kue tidak ditemukan!'); window.location='dashboard.php';</script>";
    }
}
?>