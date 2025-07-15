<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.html");
    exit();
}

if (!isset($_GET['order_id'])) {
    header("Location: orders.php");
    exit();
}

$order_id = $_GET['order_id'];

// Verify order belongs to user
$stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    header("Location: orders.php");
    exit();
}

// Check if payment already exists
$stmt = $db->prepare("SELECT * FROM payments WHERE order_id = ?");
$stmt->execute([$order_id]);
$existing_payment = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $payment_method = $_POST['payment_method'];
    $bank_name = $_POST['bank_name'] ?? null;
    $account_number = $_POST['account_number'] ?? null;
    $account_name = $_POST['account_name'] ?? null;
    
    // Handle file upload
    $proof_image = null;
    if (isset($_FILES['proof_image']) && $_FILES['proof_image']['error'] == 0) {
        $upload_dir = 'uploads/payments/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['proof_image']['name'], PATHINFO_EXTENSION);
        $proof_image = $upload_dir . uniqid('payment_') . '.' . $file_extension;
        
        move_uploaded_file($_FILES['proof_image']['tmp_name'], $proof_image);
    }

    try {
        $db->beginTransaction();

        if ($existing_payment) {
            // Update existing payment
            $stmt = $db->prepare("UPDATE payments SET 
                payment_method = ?,
                bank_name = ?,
                account_number = ?,
                account_name = ?,
                proof_image = COALESCE(?, proof_image),
                status = 'pending',
                created_at = CURRENT_TIMESTAMP
                WHERE order_id = ?");
            $stmt->execute([
                $payment_method,
                $bank_name,
                $account_number,
                $account_name,
                $proof_image,
                $order_id
            ]);
        } else {
            // Create new payment
            $stmt = $db->prepare("INSERT INTO payments 
                (order_id, amount, payment_method, bank_name, account_number, account_name, proof_image) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $order_id,
                $order['total_amount'],
                $payment_method,
                $bank_name,
                $account_number,
                $account_name,
                $proof_image
            ]);
        }

        $db->commit();
        echo "<script>alert('Pembayaran berhasil disubmit!'); window.location='orders.php';</script>";
        exit();
    } catch (PDOException $e) {
        $db->rollBack();
        echo "<script>alert('Terjadi kesalahan saat memproses pembayaran.'); window.location='payment.php?order_id=" . $order_id . "';</script>";
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - Toko Kue</title>
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
        <h2 class="mb-4">Pembayaran Pesanan #<?php echo $order_id; ?></h2>
        
        <?php if ($existing_payment && $existing_payment['status'] === 'pending'): ?>
        <div class="alert alert-info">
            <h5>Status Pembayaran: MENUNGGU KONFIRMASI</h5>
            <p>Pembayaran Anda sedang dalam proses verifikasi. Mohon tunggu konfirmasi dari admin.</p>
            <p>Waktu Submit: <?php echo date('d/m/Y H:i', strtotime($existing_payment['created_at'])); ?></p>
        </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Langkah-langkah Pembayaran</h5>
                    </div>
                    <div class="card-body">
                        <ol>
                            <li>Pilih metode pembayaran (Transfer Bank atau Tunai)</li>
                            <li>Untuk pembayaran transfer:
                                <ul>
                                    <li>Transfer ke rekening yang tertera</li>
                                    <li>Simpan bukti transfer</li>
                                    <li>Upload bukti transfer pada form di bawah</li>
                                    <li>Isi data rekening pengirim</li>
                                </ul>
                            </li>
                            <li>Klik tombol "Submit Pembayaran"</li>
                            <li>Tunggu konfirmasi dari admin (1x24 jam)</li>
                        </ol>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Detail Pembayaran</h5>
                        <p><strong>Total Pembayaran:</strong> <span class="text-primary fs-4">Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></span></p>
                        
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-4">
                                <label class="form-label">Metode Pembayaran</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="transfer" value="transfer" checked>
                                    <label class="form-check-label" for="transfer">Transfer Bank</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="cash" value="cash">
                                    <label class="form-check-label" for="cash">Tunai</label>
                                </div>
                            </div>

                            <div id="transfer-details">
                                <div class="alert alert-info mb-4" role="alert">
                                    <h6 class="alert-heading fw-bold mb-2">Informasi Rekening Toko:</h6>
                                    <p class="mb-0">
                                        Bank: <strong>BCA</strong><br>
                                        No. Rekening: <strong>1234567890</strong><br>
                                        Atas Nama: <strong>Toko Kue</strong>
                                    </p>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Nama Bank Pengirim</label>
                                    <input type="text" class="form-control" name="bank_name" placeholder="Contoh: BCA, Mandiri, BNI">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nomor Rekening Pengirim</label>
                                    <input type="text" class="form-control" name="account_number" placeholder="Masukkan nomor rekening Anda">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nama Pemilik Rekening</label>
                                    <input type="text" class="form-control" name="account_name" placeholder="Nama sesuai di buku tabungan">
                                </div>
                                <div class="mb-4">
                                    <label class="form-label">Bukti Transfer</label>
                                    <input type="file" class="form-control" name="proof_image" accept="image/*">
                                    <div class="form-text">Format yang diterima: JPG, PNG, GIF. Maksimal 2MB.</div>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">Submit Pembayaran</button>
                                <a href="orders.php" class="btn btn-secondary">Kembali ke Pesanan</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <?php if ($existing_payment): ?>
                <div class="card mb-3">
                    <div class="card-header <?php echo $existing_payment['status'] === 'confirmed' ? 'bg-success' : ($existing_payment['status'] === 'rejected' ? 'bg-danger' : 'bg-warning'); ?> text-white">
                        <h5 class="card-title mb-0">Status Pembayaran</h5>
                    </div>
                    <div class="card-body">
                        <p class="card-text">
                            <strong>Status:</strong> 
                            <span class="badge bg-<?php 
                                echo match($existing_payment['status']) {
                                    'confirmed' => 'success',
                                    'rejected' => 'danger',
                                    default => 'warning'
                                };
                            ?>">
                                <?php 
                                echo match($existing_payment['status']) {
                                    'confirmed' => 'DIKONFIRMASI',
                                    'rejected' => 'DITOLAK',
                                    default => 'MENUNGGU KONFIRMASI'
                                };
                                ?>
                            </span>
                        </p>
                        <p><strong>Metode:</strong> <?php echo ucfirst($existing_payment['payment_method']); ?></p>
                        <p><strong>Tanggal:</strong> <?php echo date('d/m/Y H:i', strtotime($existing_payment['created_at'])); ?></p>
                        
                        <?php if ($existing_payment['status'] === 'rejected'): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            Pembayaran ditolak. Silakan submit ulang pembayaran Anda dengan informasi yang benar.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">Bantuan</h5>
                    </div>
                    <div class="card-body">
                        <p>Jika mengalami kesulitan dalam pembayaran, silakan hubungi:</p>
                        <ul class="list-unstyled">
                            <li><i class="bi bi-whatsapp"></i> WA: 081234567890</li>
                            <li><i class="bi bi-envelope"></i> Email: support@tokokue.com</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const transferDetails = document.getElementById('transfer-details');
        const paymentMethods = document.querySelectorAll('input[name="payment_method"]');

        function toggleTransferDetails() {
            const selectedMethod = document.querySelector('input[name="payment_method"]:checked').value;
            transferDetails.style.display = selectedMethod === 'transfer' ? 'block' : 'none';
        }

        paymentMethods.forEach(method => {
            method.addEventListener('change', toggleTransferDetails);
        });

        toggleTransferDetails();
    });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>