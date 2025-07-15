<?php
session_start();
require_once 'config.php';

// Verify admin login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: index.html");
    exit();
}

// Handle payment confirmation/rejection
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $payment_id = $_POST['payment_id'];
    $action = $_POST['action'];
    $order_id = $_POST['order_id'];

    try {
        $db->beginTransaction();

        if ($action === 'confirm') {
            // Update payment status
            $stmt = $db->prepare("UPDATE payments SET status = 'confirmed' WHERE id = ?");
            $stmt->execute([$payment_id]);

            // Update order status
            $stmt = $db->prepare("UPDATE orders SET status = 'paid' WHERE id = ?");
            $stmt->execute([$order_id]);

            // Get user_id for redirection
            $stmt = $db->prepare("SELECT user_id FROM orders WHERE id = ?");
            $stmt->execute([$order_id]);
            $user_id = $stmt->fetchColumn();

            $message = "Pembayaran berhasil dikonfirmasi!";
            
            // Send email notification (if needed)
            // ... email code here ...

            // Redirect back to admin page
            echo "<script>alert('$message'); window.location='admin_payments.php';</script>";
            exit();
        } else if ($action === 'reject') {
            // Update payment status
            $stmt = $db->prepare("UPDATE payments SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$payment_id]);

            $message = "Pembayaran ditolak!";
        }

        $db->commit();
        echo "<script>alert('$message'); window.location='admin_payments.php';</script>";
        exit();
    } catch (PDOException $e) {
        $db->rollBack();
        echo "<script>alert('Terjadi kesalahan!'); window.location='admin_payments.php';</script>";
        exit();
    }
}

// Fetch pending payments
$stmt = $db->prepare("
    SELECT p.*, o.total_amount as order_amount, u.nama as customer_name
    FROM payments p
    JOIN orders o ON p.order_id = o.id
    JOIN users u ON o.user_id = u.id
    WHERE p.status = 'pending'
    ORDER BY p.created_at DESC
");
$stmt->execute();
$pending_payments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Kelola Pembayaran - Toko Kue</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="admin_dashboard.php">Admin Toko Kue</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="admin_payments.php">Kelola Pembayaran</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2 class="mb-4">Kelola Pembayaran</h2>
        
        <?php if (empty($pending_payments)): ?>
        <div class="alert alert-info">
            Tidak ada pembayaran yang menunggu konfirmasi.
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID Pembayaran</th>
                        <th>Pelanggan</th>
                        <th>Metode</th>
                        <th>Bank</th>
                        <th>Total</th>
                        <th>Bukti</th>
                        <th>Tanggal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_payments as $payment): ?>
                    <tr>
                        <td>#<?php echo $payment['id']; ?></td>
                        <td><?php echo htmlspecialchars($payment['customer_name']); ?></td>
                        <td><?php echo ucfirst($payment['payment_method']); ?></td>
                        <td>
                            <?php if ($payment['payment_method'] === 'transfer'): ?>
                            <?php echo htmlspecialchars($payment['bank_name']); ?><br>
                            <?php echo htmlspecialchars($payment['account_number']); ?><br>
                            <?php echo htmlspecialchars($payment['account_name']); ?>
                            <?php else: ?>
                            -
                            <?php endif; ?>
                        </td>
                        <td>Rp <?php echo number_format($payment['amount'], 0, ',', '.'); ?></td>
                        <td>
                            <?php if ($payment['proof_image']): ?>
                            <a href="<?php echo htmlspecialchars($payment['proof_image']); ?>" target="_blank">
                                Lihat Bukti
                            </a>
                            <?php else: ?>
                            -
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('d/m/Y H:i', strtotime($payment['created_at'])); ?></td>
                        <td>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                <input type="hidden" name="order_id" value="<?php echo $payment['order_id']; ?>">
                                <input type="hidden" name="action" value="confirm">
                                <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Konfirmasi pembayaran ini?')">
                                    Konfirmasi
                                </button>
                            </form>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="payment_id" value="<?php echo $payment['id']; ?>">
                                <input type="hidden" name="order_id" value="<?php echo $payment['order_id']; ?>">
                                <input type="hidden" name="action" value="reject">
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Tolak pembayaran ini?')">
                                    Tolak
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>