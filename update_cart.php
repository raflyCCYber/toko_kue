<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['cart'])) {
    header("Location: index.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $index = $_POST['index'];
    $jumlah = (int)$_POST['jumlah'];

    if (isset($_SESSION['cart'][$index]) && $jumlah > 0) {
        $_SESSION['cart'][$index]['jumlah'] = $jumlah;
    }
}

header("Location: cart.php");
exit();
?>