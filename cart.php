<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

$total = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang - Toko Kue</title>
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
                        <a class="nav-link active" href="cart.php">Keranjang</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="orders.php">Pesanan Saya</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h2 class="mb-4">Keranjang Belanja</h2>
        <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nama Kue</th>
                            <th>Harga</th>
                            <th>Jumlah</th>
                            <th>Subtotal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($_SESSION['cart'] as $index => $item): 
                            $subtotal = $item['harga'] * $item['jumlah'];
                            $total += $subtotal;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['nama']); ?></td>
                            <td>Rp <?php echo number_format($item['harga'], 0, ',', '.'); ?></td>
                            <td>
                                <form action="update_cart.php" method="POST" class="d-inline">
                                    <input type="hidden" name="index" value="<?php echo $index; ?>">
                                    <input type="number" name="jumlah" value="<?php echo $item['jumlah']; ?>" min="1" class="form-control d-inline-block" style="width: 80px">
                                    <button type="submit" class="btn btn-sm btn-info">Update</button>
                                </form>
                            </td>
                            <td>Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></td>
                            <td>
                                <form action="remove_from_cart.php" method="POST" class="d-inline">
                                    <input type="hidden" name="index" value="<?php echo $index; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Total:</strong></td>
                            <td><strong>Rp <?php echo number_format($total, 0, ',', '.'); ?></strong></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="text-end mt-3">
                <a href="dashboard.php" class="btn btn-secondary">Lanjut Belanja</a>
                <a href="checkout.php" class="btn btn-primary">Checkout</a>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                Keranjang belanja Anda kosong. <a href="dashboard.php">Lanjut belanja</a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>