<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

// Fetch user's orders with payment information
$stmt = $db->prepare("
    SELECT o.*, 
           COUNT(oi.id) as total_items,
           p.status as payment_status,
           p.payment_method,
           p.created_at as payment_date
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN payments p ON o.id = p.order_id
    WHERE o.user_id = ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pesanan - Toko Kue</title>
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
                        <a class="nav-link active" href="orders.php">Pesanan Saya</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2 class="mb-4">Riwayat Pesanan</h2>
        
        <?php
        // Show success message if payment was just confirmed
        $payment_id = isset($_GET['payment_success']) ? $_GET['payment_success'] : null;
        if ($payment_id) {
            $stmt = $db->prepare("
                SELECT p.*, o.id as order_id 
                FROM payments p 
                JOIN orders o ON p.order_id = o.id 
                WHERE p.id = ? AND p.status = 'confirmed' AND o.user_id = ?
            ");
            $stmt->execute([$payment_id, $_SESSION['user_id']]);
            if ($stmt->fetch()) {
                echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                    Pembayaran Anda telah dikonfirmasi! Pesanan Anda akan segera diproses.
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>';
            }
        }
        ?>

        <?php if (empty($orders)): ?>
        <div class="alert alert-info">
            Anda belum memiliki pesanan. <a href="dashboard.php">Mulai belanja</a>
        </div>
        <?php else: ?>
        <div class="row">
            <?php foreach ($orders as $order): ?>
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Pesanan #<?php echo $order['id']; ?></h5>
                        <p class="card-text">
                            <strong>Tanggal:</strong> <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?><br>
                            <strong>Status Pesanan:</strong> 
                            <span class="badge bg-<?php 
                                echo match($order['status']) {
                                    'pending' => 'warning',
                                    'paid' => 'info',
                                    'completed' => 'success',
                                    'cancelled' => 'danger',
                                    default => 'secondary'
                                };
                            ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span><br>
                            <?php if (isset($order['payment_status'])): ?>
                            <strong>Status Pembayaran:</strong>
                            <span class="badge bg-<?php 
                                echo match($order['payment_status']) {
                                    'confirmed' => 'success',
                                    'rejected' => 'danger',
                                    'pending' => 'warning',
                                    default => 'secondary'
                                };
                            ?>">
                                <?php echo ucfirst($order['payment_status'] ?? 'Belum dibayar'); ?>
                            </span><br>
                            <?php if ($order['payment_method']): ?>
                            <strong>Metode Pembayaran:</strong> <?php echo ucfirst($order['payment_method']); ?><br>
                            <strong>Tanggal Pembayaran:</strong> <?php echo date('d/m/Y H:i', strtotime($order['payment_date'])); ?><br>
                            <?php endif; ?>
                            <?php endif; ?>
                            <strong>Total Item:</strong> <?php echo $order['total_items']; ?><br>
                            <strong>Total Pembayaran:</strong> Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?>
                        </p>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-primary" type="button" 
                                    data-bs-toggle="collapse" 
                                    data-bs-target="#orderDetails<?php echo $order['id']; ?>">
                                Lihat Detail
                            </button>
                            <?php if ($order['status'] === 'pending' || 
                                    ($order['payment_status'] ?? '') === 'rejected'): ?>
                            <a href="payment.php?order_id=<?php echo $order['id']; ?>" 
                               class="btn btn-sm btn-success">
                                <?php echo isset($order['payment_status']) ? 'Update Pembayaran' : 'Bayar Sekarang'; ?>
                            </a>
                            <?php endif; ?>
                        </div>
                        <div class="collapse mt-3" id="orderDetails<?php echo $order['id']; ?>">
                            <?php
                            $stmt = $db->prepare("
                                SELECT oi.*, k.nama
                                FROM order_items oi
                                JOIN kue k ON oi.kue_id = k.id
                                WHERE oi.order_id = ?
                            ");
                            $stmt->execute([$order['id']]);
                            $items = $stmt->fetchAll();
                            ?>
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Kue</th>
                                        <th>Jumlah</th>
                                        <th>Harga</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['nama']); ?></td>
                                        <td><?php echo $item['jumlah']; ?></td>
                                        <td>Rp <?php echo number_format($item['harga'], 0, ',', '.'); ?></td>
                                        <td>Rp <?php echo number_format($item['harga'] * $item['jumlah'], 0, ',', '.'); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>