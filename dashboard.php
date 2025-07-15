<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Toko Kue</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .cake-card {
            margin-bottom: 20px;
        }
        .cake-img {
            height: 200px;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Toko Kue Rafly syach</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="nav-link">Selamat datang, <?php echo htmlspecialchars($_SESSION['nama']); ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php">Keranjang</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="orders.php">Pesanan Saya</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Log out</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php
        // Check for pending payments
        $stmt = $db->prepare("
            SELECT o.id as order_id, o.total_amount, o.created_at
            FROM orders o
            LEFT JOIN payments p ON o.id = p.order_id
            WHERE o.user_id = ? AND o.status = 'pending'
            AND (p.id IS NULL OR p.status = 'rejected')
            ORDER BY o.created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $pending_order = $stmt->fetch();

        if ($pending_order): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <strong>Pembayaran Tertunda!</strong> 
            Anda memiliki pesanan #<?php echo $pending_order['id']; ?> yang belum dibayar 
            (Rp <?php echo number_format($pending_order['total_amount'], 0, ',', '.'); ?>).
            <a href="payment.php?order_id=<?php echo $pending_order['order_id']; ?>" class="alert-link">Bayar sekarang</a>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <h2 class="mb-4">Daftar Kue</h2>

        <!-- Category Tabs -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link active" id="basah-tab" data-bs-toggle="tab" href="#basah">Kue Basah</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="kering-tab" data-bs-toggle="tab" href="#kering">Kue Kering</a>
            </li>
        </ul>

        <div class="tab-content">
            <!-- Kue Basah -->
            <div class="tab-pane fade show active" id="basah">
                <div class="row">
                    <?php
                    $stmt = $db->prepare("SELECT * FROM kue WHERE kategori = 'basah'");
                    $stmt->execute();
                    while ($kue = $stmt->fetch()) {
                    ?>
                    <div class="col-md-4">
                        <div class="card cake-card">
                            <img src="<?php echo htmlspecialchars($kue['gambar']); ?>" class="card-img-top cake-img" alt="<?php echo htmlspecialchars($kue['nama']); ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($kue['nama']); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars($kue['deskripsi']); ?></p>
                                <p class="card-text">
                                    <strong>Rp <?php echo number_format($kue['harga'], 0, ',', '.'); ?></strong>
                                    <small class="text-muted float-end">Stok: <?php echo $kue['stok']; ?></small>
                                </p>
                                <form action="add_to_cart.php" method="POST">
                                    <input type="hidden" name="kue_id" value="<?php echo $kue['id']; ?>">
                                    <div class="input-group mb-3">
                                        <input type="number" class="form-control" name="jumlah" value="1" min="1" max="<?php echo $kue['stok']; ?>">
                                        <button class="btn btn-primary" type="submit">Tambah ke Keranjang</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php
                    }
                    ?>
                </div>
            </div>

            <!-- Kue Kering -->
            <div class="tab-pane fade" id="kering">
                <div class="row">
                    <?php
                    $stmt = $db->prepare("SELECT * FROM kue WHERE kategori = 'kering'");
                    $stmt->execute();
                    while ($kue = $stmt->fetch()) {
                    ?>
                    <div class="col-md-4">
                        <div class="card cake-card">
                            <img src="<?php echo htmlspecialchars($kue['gambar']); ?>" class="card-img-top cake-img" alt="<?php echo htmlspecialchars($kue['nama']); ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($kue['nama']); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars($kue['deskripsi']); ?></p>
                                <p class="card-text">
                                    <strong>Rp <?php echo number_format($kue['harga'], 0, ',', '.'); ?></strong>
                                    <small class="text-muted float-end">Stok: <?php echo $kue['stok']; ?></small>
                                </p>
                                <form action="add_to_cart.php" method="POST">
                                    <input type="hidden" name="kue_id" value="<?php echo $kue['id']; ?>">
                                    <div class="input-group mb-3">
                                        <input type="number" class="form-control" name="jumlah" value="1" min="1" max="<?php echo $kue['stok']; ?>">
                                        <button class="btn btn-primary" type="submit">Tambah ke Keranjang</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>