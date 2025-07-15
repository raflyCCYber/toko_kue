<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['cart'])) {
    header("Location: index.html");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['index'])) {
    $index = $_POST['index'];
    if (isset($_SESSION['cart'][$index])) {
        unset($_SESSION['cart'][$index]);
        $_SESSION['cart'] = array_values($_SESSION['cart']); // Re-index array
    }
}

header("Location: cart.php");
exit();
?>