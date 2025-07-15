<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit();
}

$total = 0;
foreach ($_SESSION['cart'] as $item) {
    $total += $item['harga'] * $item['jumlah'];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $db->beginTransaction();

        // Create order
        $stmt = $db->prepare("INSERT INTO orders (user_id, total_amount) VALUES (?, ?)");
        $stmt->execute([$_SESSION['user_id'], $total]);
        $order_id = $db->lastInsertId();

        // Create order items
        $stmt = $db->prepare("INSERT INTO order_items (order_id, kue_id, jumlah, harga) VALUES (?, ?, ?, ?)");
        foreach ($_SESSION['cart'] as $item) {
            $stmt->execute([$order_id, $item['kue_id'], $item['jumlah'], $item['harga']]);
            
            // Update stock
            $update_stock = $db->prepare("UPDATE kue SET stok = stok - ? WHERE id = ?");
            $update_stock->execute([$item['jumlah'], $item['kue_id']]);
        }

        $db->commit();
        unset($_SESSION['cart']); // Clear the cart
        
        echo "<script>alert('Pesanan berhasil dibuat! Silakan lanjutkan ke pembayaran.'); window.location='payment.php?order_id=" . $order_id . "';</script>";
        exit();
    } catch (PDOException $e) {
        $db->rollBack();
        echo "<script>alert('Terjadi kesalahan saat membuat pesanan.'); window.location='cart.php';</script>";
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Toko Kue</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Toko Kue</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="nav-link">Selamat datang, <?php echo htmlspecialchars($_SESSION['nama']); ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php">Keranjang</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2 class="mb-4">Checkout</h2>
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Ringkasan Pesanan</h5>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nama Kue</th>
                                    <th>Jumlah</th>
                                    <th>Harga</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($_SESSION['cart'] as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['nama']); ?></td>
                                    <td><?php echo $item['jumlah']; ?></td>
                                    <td>Rp <?php echo number_format($item['harga'], 0, ',', '.'); ?></td>
                                    <td>Rp <?php echo number_format($item['harga'] * $item['jumlah'], 0, ',', '.'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                    <td><strong>Rp <?php echo number_format($total, 0, ',', '.'); ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Konfirmasi Pesanan</h5>
                        <p>Silakan periksa pesanan Anda sebelum melanjutkan pembayaran.</p>
                        <form method="POST">
                            <button type="submit" class="btn btn-primary w-100">Buat Pesanan</button>
                        </form>
                        <a href="cart.php" class="btn btn-secondary w-100 mt-2">Kembali ke Keranjang</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>