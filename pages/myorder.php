<?php
// Memulai sesi untuk memeriksa status login pengguna
session_start();
// Menyertakan file konfigurasi untuk koneksi database dan pengaturan lainnya
include '../includes/config.php';

// Memeriksa apakah pengguna sudah login, jika tidak, arahkan ke halaman login
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit();
}

// Mengambil ID pengguna dari sesi dan memastikan tipe data integer untuk keamanan
$user_id = intval($_SESSION['id_user']);
// Mengambil ID reservasi dari parameter GET, default 0 jika tidak ada
$reservation_id = isset($_GET['rid']) ? intval($_GET['rid']) : 0;
// Mengambil metode pembayaran dari parameter GET, null jika tidak ada
$payment_method = isset($_GET['method']) ? $_GET['method'] : null;

// Menangani permintaan pembatalan reservasi (OPERASI CRUD: UPDATE)
if (isset($_POST['cancel_reservation']) && isset($_POST['id_reservasi'])) {
    // Mengambil ID reservasi yang akan dibatalkan dan memastikan tipe data integer
    $cancel_reservation_id = intval($_POST['id_reservasi']);
    
    // Memeriksa apakah reservasi dapat dibatalkan (OPERASI CRUD: READ)
    // Hanya reservasi dengan status 'Pending Payment' atau 'Deposit Paid' yang bisa dibatalkan
    $stmt = $conn->prepare("
        SELECT r.id_area, p.status_payment 
        FROM reservasi r 
        LEFT JOIN pembayaran p ON r.id_reservasi = p.id_reservasi 
        WHERE r.id_reservasi = ? AND r.id_user = ? 
        AND (p.status_payment = 'Pending Payment' OR p.status_payment = 'Deposit Paid')
        AND r.payment_status IN ('Pending Payment', 'Deposit Paid')
    ");
    $stmt->bind_param("ii", $cancel_reservation_id, $user_id);
    $stmt->execute();
    $cancel_result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($cancel_result) {
        // Mengambil ID area untuk pembaruan status ketersediaan
        $area_id = $cancel_result['id_area'];
        
        // Memulai transaksi database untuk memastikan konsistensi data
        $conn->begin_transaction();
        try {
            // Memperbarui status pembayaran reservasi menjadi 'Cancelled' (OPERASI CRUD: UPDATE)
            $stmt = $conn->prepare("UPDATE reservasi SET payment_status = 'Cancelled' WHERE id_reservasi = ? AND payment_status IN ('Pending Payment', 'Deposit Paid')");
            $stmt->bind_param("i", $cancel_reservation_id);
            $stmt->execute();
            $stmt->close();

            // Memperbarui status pembayaran, mereset jumlah dibayar, dan memperbarui tanggal bayar (OPERASI CRUD: UPDATE)
            $stmt = $conn->prepare("UPDATE pembayaran SET status_payment = 'Cancelled', jumlah_dibayar = 0, tanggal_bayar = NOW() WHERE id_reservasi = ?");
            $stmt->bind_param("i", $cancel_reservation_id);
            $stmt->execute();
            $stmt->close();

            // Memperbarui status pesanan menjadi 'Cancelled' (OPERASI CRUD: UPDATE)
            $stmt = $conn->prepare("UPDATE pesanan SET status_pesanan = 'Cancelled' WHERE id_reservasi = ?");
            $stmt->bind_param("i", $cancel_reservation_id);
            $stmt->execute();
            $stmt->close();

            // Mengubah status area menjadi tersedia (OPERASI CRUD: UPDATE)
            $stmt = $conn->prepare("UPDATE area SET tersedia = 1 WHERE id_area = ?");
            $stmt->bind_param("i", $area_id);
            $stmt->execute();
            $stmt->close();

            // Menyelesaikan transaksi jika semua operasi berhasil
            $conn->commit();
            // Menampilkan pesan sukses
            echo '<div class="alert alert-success text-center" role="alert">Reservation cancelled successfully.</div>';
        } catch (Exception $e) {
            // Membatalkan transaksi jika terjadi kesalahan
            $conn->rollback();
            // Menampilkan pesan error
            echo '<div class="alert alert-danger text-center" role="alert">Error cancelling reservation: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    } else {
        // Menampilkan pesan jika reservasi tidak dapat dibatalkan
        echo '<div class="alert alert-danger text-center" role="alert">This reservation cannot be cancelled. It may already be cancelled, fully paid, or invalid.</div>';
    }
}

// Mengambil detail reservasi spesifik (OPERASI CRUD: READ)
$stmt = $conn->prepare("
    SELECT r.*, p.status_payment, p.total_tagihan, p.jumlah_dibayar, p.tanggal_bayar, 
           a.tersedia, a.nama_area, COALESCE(ir.decoration_theme, 'Not selected') AS decoration_theme
    FROM reservasi r 
    JOIN area a ON r.id_area = a.id_area 
    LEFT JOIN pembayaran p ON r.id_reservasi = p.id_reservasi
    LEFT JOIN interiorrequest ir ON r.id_reservasi = ir.id_reservasi
    WHERE r.id_reservasi = ? AND r.id_user = ?
");
$stmt->bind_param("ii", $reservation_id, $user_id);
$stmt->execute();
$reservation = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Menyimpan data reservasi untuk digunakan di HTML
$status_payment = $reservation['status_payment'] ?? 'Pending Payment';
$total_tagihan = $reservation['total_tagihan'] ?? 0;
$jumlah_dibayar = $reservation['jumlah_dibayar'] ?? 0;
$deposit_amount = $total_tagihan * 0.3;
$tersedia = $reservation['tersedia'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Bagian meta dan link untuk styling -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/assets/img/logo.png" type="image/gif" sizes="16x16">
    <title>My Orders || FineDining</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,400;1,700;1,900&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <!-- CSS untuk styling halaman -->
    <style>
        :root {
            --primary-gold: #d4af37;
            --accent-gold: #e6c74a;
            --dark-bg: #0f172a;
            --light-bg: #1e293b;
            --text-light: #ffffff;
            --text-muted: #d3d4db;
            --text-highlight: #ffcc00;
            --shadow: 0 6px 15px rgba(0, 0, 0, 0.4);
            --glass-bg: rgba(255, 255, 255, 0.1);
            --button-bg: #ff8c00;
            --button-bg-hover: #ffa500;
            --error-bg: #dc3545;
            --success-bg: #28a745;
            --gold-line: 1px solid rgba(212, 175, 55, 0.3);
        }

        html, body { margin: 0; padding: 0; overflow-x: hidden; }
        * { box-sizing: border-box; }
        body::-webkit-scrollbar { display: none; }

        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(to bottom, var(--dark-bg), var(--light-bg));
            color: var(--text-light);
            overflow-y: auto;
            min-height: 100vh;
        }

        .overlay { 
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            background: rgba(0, 0, 0, 0.05); 
            z-index: -1; 
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            font-weight: 900;
            color: var(--text-highlight);
            text-shadow: 0 2px 10px rgba(255, 204, 0, 0.2);
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .section-title::after {
            content: '';
            display: block;
            width: 80px;
            height: 2px;
            background: var(--primary-gold);
            margin: 0.75rem auto;
        }

        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding: 0 1rem;
        }

        .card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: var(--gold-line);
            border-radius: 10px;
            box-shadow: var(--shadow);
            margin-bottom: 1rem;
            padding: 1rem;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(212, 175, 55, 0.2);
        }

        .card-header {
            background: transparent;
            border-bottom: var(--gold-line);
            padding: 0.75rem 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .text-anchor { 
            color: var(--text-highlight); 
            font-weight: 600; 
            font-family: 'Playfair Display', serif; 
        }

        .alert {
            padding: 0.5rem;
            border-radius: 6px;
            font-size: 0.9rem;
            margin: 0.5rem 0;
        }

        .checklist { 
            background: rgba(255, 255, 255, 0.05); 
            border: var(--gold-line); 
            border-radius: 6px; 
            padding: 0.75rem; 
            margin-top: 0.5rem; 
        }

        .checklist-item { 
            display: flex; 
            align-items: center; 
            margin-bottom: 0.25rem; 
            font-size: 0.9rem; 
            color: var(--text-muted); 
        }

        .checklist-item i { 
            color: var(--success-bg); 
            margin-right: 0.5rem; 
            font-size: 0.9rem; 
        }

        p { 
            color: var(--text-light); 
            font-size: 0.9rem; 
            line-height: 1.4; 
            margin: 0.25rem 0; 
        }

        p:hover { 
            color: var(--accent-gold); 
        }

        p strong { 
            color: var(--text-highlight); 
            font-weight: 600; 
        }

        .btn-primary {
            background: var(--button-bg);
            border: none;
            color: #fff;
            border-radius: 6px;
            padding: 0.5rem 1rem;
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 3px 10px rgba(212, 175, 55, 0.4);
        }

        .btn-primary:hover {
            background: var(--button-bg-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(212, 175, 55, 0.5);
        }

        .btn-primary:active {
            transform: translateY(0);
            box-shadow: 0 2px 5px rgba(212, 175, 55, 0.3);
        }

        .btn-primary:disabled { 
            background: #666; 
            cursor: not-allowed; 
            box-shadow: none; 
        }

        .btn-success {
            background-color: var(--success-bg);
            border: none;
            color: #fff;
            border-radius: 6px;
            padding: 0.5rem 1rem;
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 3px 10px rgba(40, 167, 69, 0.4);
        }

        .btn-success:hover {
            background-color: #218838;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(40, 167, 69, 0.5);
        }

        .btn-success:active {
            transform: translateY(0);
            box-shadow: 0 2px 5px rgba(40, 167, 69, 0.3);
        }

        .btn-success:disabled { 
            background: #666; 
            cursor: not-allowed; 
            box-shadow: none; 
        }

        .btn-danger {
            background-color: var(--error-bg);
            border: none;
            color: #fff;
            border-radius: 6px;
            padding: 0.5rem 1rem;
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 3px 10px rgba(220, 53, 69, 0.4);
        }

        .btn-danger:hover {
            background-color: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(220, 53, 69, 0.5);
        }

        .btn-danger:active {
            transform: translateY(0);
            box-shadow: 0 2px 5px rgba(220, 53, 69, 0.3);
        }

        .btn-danger:disabled { 
            background: #666; 
            cursor: not-allowed; 
            box-shadow: none; 
        }

        .nav-tabs {
            border-bottom: var(--gold-line);
            margin-bottom: 1rem;
        }

        .nav-tabs .nav-link {
            color: var(--text-muted);
            font-family: 'Roboto', sans-serif;
            font-weight: 500;
            font-size: 1rem;
            padding: 0.5rem 1rem;
            border: none;
            border-bottom: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .nav-tabs .nav-link.active {
            color: var(--text-highlight);
            border-bottom: 2px solid var(--primary-gold);
            background: transparent;
        }

        .nav-tabs .nav-link:hover {
            color: var(--accent-gold);
            border-bottom: 2px solid var(--accent-gold);
        }

        .badge {
            font-size: 0.8rem;
            padding: 0.25em 0.6em;
            border-radius: 0.4rem;
        }

        .input-group .form-control {
            border-radius: 0.4rem 0 0 0.4rem;
            background: rgba(255, 255, 255, 0.05);
            border: var(--gold-line);
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .input-group .btn-primary {
            border-radius: 0 0.4rem 0.4rem 0;
            padding: 0.4rem 0.8rem;
            font-size: 0.9rem;
        }

        .container {
            padding: 1.5rem;
            min-height: 100vh;
        }

        .timer {
            font-size: 1rem;
            color: var(--text-light);
            text-align: center;
            margin-top: 1rem;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .section-title { font-size: 1.8rem; }
            .btn-primary, .btn-success, .btn-danger { padding: 0.4rem 0.8rem; font-size: 0.9rem; }
            .nav-tabs .nav-link { font-size: 0.9rem; padding: 0.4rem 0.8rem; }
        }

        @media (max-width: 576px) {
            .section-title { font-size: 1.5rem; }
            .btn-primary, .btn-success, .btn-danger { padding: 0.3rem 0.6rem; font-size: 0.8rem; }
            .nav-tabs .nav-link { font-size: 0.8rem; padding: 0.3rem 0.6rem; }
            .card { padding: 0.75rem; }
        }
    </style>
</head>
<body>
    <div class="overlay"></div>

    <section class="container">
        <!-- Header dengan judul dan tombol kembali -->
        <div class="header-row">
            <h1 class="section-title m-0" data-aos="zoom-in" data-aos-duration="800">My Orders</h1>
            <a href="../customer.php" class="btn btn-primary"><i class="fas fa-arrow-left me-1"></i> Back to Dashboard</a>
        </div>

        <!-- Navigasi tab untuk pesanan aktif, riwayat, dan dibatalkan -->
        <ul class="nav nav-tabs" id="orderTabs" role="tablist" data-aos="fade-up" data-aos-duration="800">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="active-tab" data-bs-toggle="tab" data-bs-target="#active-orders" type="button" role="tab" aria-controls="active-orders" aria-selected="true">Active Orders</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#order-history" type="button" role="tab" aria-controls="order-history" aria-selected="false">Order History</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="cancelled-tab" data-bs-toggle="tab" data-bs-target="#cancelled-orders" type="button" role="tab" aria-controls="cancelled-orders" aria-selected="false">Cancelled Orders</button>
            </li>
        </ul>

        <div class="tab-content">
            <!-- Tab untuk pesanan aktif -->
            <div class="tab-pane fade show active" id="active-orders" role="tabpanel" aria-labelledby="active-tab">
                <?php
                // Menampilkan detail reservasi spesifik jika ID reservasi diberikan dan bukan dibatalkan
                if ($reservation_id && $reservation && $status_payment !== 'Cancelled') {
                    // Menentukan status tampilan berdasarkan status pembayaran
                    $display_status = ($status_payment === 'Pending Payment') ? 'Pending' : ($status_payment === 'Deposit Paid' ? 'Deposit Paid' : 'Fully Paid');
                    // Menghitung sisa tagihan
                    $remaining_balance = $total_tagihan - $jumlah_dibayar;
                    ?>
                    <div class="card" data-aos="slide-up" data-aos-duration="800">
                        <div class="card-header">
                            <h5 class="text-anchor mb-0">Reservation #<?php echo htmlspecialchars($reservation_id); ?> (<?php echo htmlspecialchars($display_status); ?>)</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-2">
                                <!-- Detail reservasi -->
                                <div class="col-md-4">
                                    <h6 class="text-anchor mb-2">Reservation</h6>
                                    <p><strong>Area:</strong> <?php echo htmlspecialchars($reservation['nama_area'] ?? 'Not specified'); ?></p>
                                    <p><strong>Table:</strong> <?php echo htmlspecialchars($reservation['lokasi_meja'] ?? 'Not specified'); ?></p>
                                    <p><strong>Capacity:</strong> <?php echo htmlspecialchars($reservation['jumlah_orang'] ?? 0); ?> persons</p>
                                    <p><strong>Date:</strong> <?php echo htmlspecialchars($reservation['tanggal'] ?? 'Not specified'); ?></p>
                                    <p><strong>Time:</strong> <?php echo htmlspecialchars($reservation['waktu'] ?? 'Not specified'); ?></p>
                                    <p><strong>Theme:</strong> <?php echo htmlspecialchars($reservation['decoration_theme']); ?></p>
                                </div>
                                <!-- Daftar pesanan (OPERASI CRUD: READ) -->
                                <div class="col-md-4">
                                    <h6 class="text-anchor mb-2">Orders</h6>
                                    <div class="checklist">
                                        <?php
                                        // Mengambil daftar pesanan untuk reservasi ini dari tabel pesanan dan menu
                                        $stmt_order = $conn->prepare("SELECT p.*, m.nama_menu, m.kategori FROM pesanan p JOIN menu m ON p.id_menu = m.id_menu WHERE p.id_reservasi = ?");
                                        $stmt_order->bind_param("i", $reservation_id);
                                        $stmt_order->execute();
                                        $order_result = $stmt_order->get_result();
                                        if ($order_result && $order_result->num_rows > 0) {
                                            while ($order = $order_result->fetch_assoc()) {
                                                echo '<div class="checklist-item"><i class="fas fa-check"></i> ' . htmlspecialchars($order['nama_menu']) . ' (' . htmlspecialchars($order['kategori']) . ') - Qty: ' . htmlspecialchars($order['jumlah']) . '</div>';
                                            }
                                        } else {
                                            echo '<div class="checklist-item"><i class="fas fa-check"></i> No items ordered</div>';
                                        }
                                        $stmt_order->close();
                                        ?>
                                    </div>
                                </div>
                                <!-- Detail pembayaran -->
                                <div class="col-md-4">
                                    <h6 class="text-anchor mb-2">Payment</h6>
                                    <p><strong>Total:</strong> IDR <?php echo number_format($total_tagihan, 2, ',', '.'); ?></p>
                                    <p><strong>Deposit (30%):</strong> IDR <?php echo number_format($deposit_amount, 2, ',', '.'); ?></p>
                                    <p><strong>Paid:</strong> IDR <?php echo number_format($jumlah_dibayar, 2, ',', '.'); ?></p>
                                    <p><strong>Remaining:</strong> IDR <?php echo number_format($remaining_balance, 2, ',', '.'); ?></p>
                                    <?php if ($payment_method === 'Tunai' && $status_payment === 'Pending Payment'): ?>
                                        <!-- Timer untuk pembayaran tunai -->
                                        <div id="timer" class="timer">Time left: <span id="countdown">60</span> sec</div>
                                        <div id="cancelMessage" class="alert alert-danger text-center" role="alert" style="display: none;">Order cancelled due to timeout.</div>
                                    <?php endif; ?>
                                    <?php if ($status_payment === 'Pending Payment' && $jumlah_dibayar == 0): ?>
                                        <!-- Tombol untuk melakukan pembayaran -->
                                        <div class="text-center mt-2">
                                            <a href="pembayaran.php?id_reservasi=<?php echo $reservation_id; ?>" class="btn btn-primary pay-btn">Pay Now</a>
                                        </div>
                                    <?php elseif ($status_payment === 'Deposit Paid' && $remaining_balance > 0): ?>
                                        <!-- Tombol untuk melunasi pembayaran -->
                                        <div class="text-center mt-2">
                                            <a href="pembayaran_pelunasan.php?id_reservasi=<?php echo $reservation_id; ?>" class="btn btn-primary pay-btn">Settle Payment</a>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($status_payment === 'Pending Payment' || $status_payment === 'Deposit Paid'): ?>
                                        <!-- Form untuk membatalkan reservasi -->
                                        <form method="post" class="mt-2">
                                            <input type="hidden" name="id_reservasi" value="<?php echo $reservation_id; ?>">
                                            <button type="submit" name="cancel_reservation" class="btn btn-danger w-100 cancel-btn">Cancel Reservation</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                }

                // Mengambil semua pesanan aktif (OPERASI CRUD: READ)
                $stmt_active = $conn->prepare("
                    SELECT r.*, p.total_tagihan, p.status_payment, p.tanggal_bayar, p.jumlah_dibayar, 
                           a.tersedia, a.nama_area, COALESCE(ir.decoration_theme, 'Not selected') AS decoration_theme
                    FROM reservasi r
                    JOIN area a ON r.id_area = a.id_area
                    LEFT JOIN pembayaran p ON r.id_reservasi = p.id_reservasi
                    LEFT JOIN interiorrequest ir ON r.id_reservasi = ir.id_reservasi
                    WHERE r.id_user = ? 
                    AND (p.status_payment = 'Pending Payment' OR p.status_payment = 'Deposit Paid' OR (p.status_payment = 'Fully Paid' AND a.tersedia = 0))
                    ORDER BY r.tanggal DESC
                ");
                $stmt_active->bind_param("i", $user_id);
                $stmt_active->execute();
                $active_result = $stmt_active->get_result();

                if ($active_result->num_rows > 0) {
                    while ($reservation = $active_result->fetch_assoc()) {
                        // Melewati reservasi spesifik untuk menghindari duplikasi
                        if ($reservation['id_reservasi'] != $reservation_id) {
                            $res_id = $reservation['id_reservasi'];
                            $total_tagihan = $reservation['total_tagihan'] ?? 0;
                            $status_payment = $reservation['status_payment'] ?? 'Pending Payment';
                            $jumlah_dibayar = $reservation['jumlah_dibayar'] ?? 0;
                            $deposit_amount = $total_tagihan * 0.3;
                            $remaining_balance = $total_tagihan - $jumlah_dibayar;
                            $display_status = ($status_payment === 'Pending Payment') ? 'Pending' : ($status_payment === 'Deposit Paid' ? 'Deposit Paid' : 'Fully Paid');
                            ?>
                            <div class="card" data-aos="slide-up" data-aos-duration="800">
                                <div class="card-header">
                                    <h5 class="text-anchor mb-0">
                                        Reservation #<?php echo htmlspecialchars($res_id); ?> (<?php echo htmlspecialchars($display_status); ?>)
                                        <span class="badge bg-<?php echo $status_payment === 'Fully Paid' ? 'success' : ($status_payment === 'Deposit Paid' ? 'warning' : 'danger'); ?> ms-2">
                                            <?php echo htmlspecialchars($display_status); ?>
                                        </span>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row g-2">
                                        <!-- Detail pembayaran -->
                                        <div class="col-md-4">
                                            <h6 class="text-anchor mb-2">Payment</h6>
                                            <p><strong>Total:</strong> IDR <?php echo number_format($total_tagihan, 2, ',', '.'); ?></p>
                                            <p><strong>Paid:</strong> IDR <?php echo number_format($jumlah_dibayar, 2, ',', '.'); ?></p>
                                            <p><strong>Deposit:</strong> IDR <?php echo number_format($deposit_amount, 2, ',', '.'); ?></p>
                                            <p><strong>Remaining:</strong> IDR <?php echo number_format($remaining_balance, 2, ',', '.'); ?></p>
                                            <?php if ($status_payment === 'Pending Payment' && $jumlah_dibayar == 0): ?>
                                                <div class="text-center mt-2">
                                                    <a href="pembayaran.php?id_reservasi=<?php echo $res_id; ?>" class="btn btn-primary pay-btn">Pay Now</a>
                                                </div>
                                            <?php elseif ($status_payment === 'Deposit Paid' && $remaining_balance > 0): ?>
                                                <div class="text-center mt-2">
                                                    <a href="pembayaran_pelunasan.php?id_reservasi=<?php echo $res_id; ?>" class="btn btn-primary pay-btn">Settle Payment</a>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($status_payment === 'Pending Payment' || $status_payment === 'Deposit Paid'): ?>
                                                <form method="post" class="mt-2">
                                                    <input type="hidden" name="id_reservasi" value="<?php echo $res_id; ?>">
                                                    <button type="submit" name="cancel_reservation" class="btn btn-danger w-100 cancel-btn">Cancel Reservation</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                        <!-- Detail reservasi -->
                                        <div class="col-md-4">
                                            <h6 class="text-anchor mb-2">Reservation</h6>
                                            <p><strong>Area:</strong> <?php echo htmlspecialchars($reservation['nama_area'] ?? 'Not specified'); ?></p>
                                            <p><strong>Table:</strong> <?php echo htmlspecialchars($reservation['lokasi_meja'] ?? 'Not specified'); ?></p>
                                            <p><strong>Capacity:</strong> <?php echo htmlspecialchars($reservation['jumlah_orang'] ?? 0); ?> persons</p>
                                            <p><strong>Date:</strong> <?php echo htmlspecialchars($reservation['tanggal'] ?? 'Not specified'); ?></p>
                                            <p><strong>Time:</strong> <?php echo htmlspecialchars($reservation['waktu'] ?? 'Not specified'); ?></p>
                                            <p><strong>Theme:</strong> <?php echo htmlspecialchars($reservation['decoration_theme']); ?></p>
                                        </div>
                                        <!-- Daftar pesanan (OPERASI CRUD: READ) -->
                                        <div class="col-md-4">
                                            <h6 class="text-anchor mb-2">Orders</h6>
                                            <div class="checklist">
                                                <?php
                                                // Mengambil daftar pesanan untuk reservasi ini dari tabel pesanan dan menu
                                                $stmt_order = $conn->prepare("SELECT p.*, m.nama_menu, m.kategori FROM pesanan p JOIN menu m ON p.id_menu = m.id_menu WHERE p.id_reservasi = ?");
                                                $stmt_order->bind_param("i", $res_id);
                                                $stmt_order->execute();
                                                $order_result = $stmt_order->get_result();
                                                if ($order_result && $order_result->num_rows > 0) {
                                                    while ($order = $order_result->fetch_assoc()) {
                                                        ?>
                                                        <div class="checklist-item">
                                                            <i class="fas fa-check"></i> 
                                                            <?php echo htmlspecialchars($order['nama_menu']); ?> 
                                                            (Qty: <?php echo htmlspecialchars($order['jumlah']); ?>, <?php echo htmlspecialchars($order['kategori'] ?? 'N/A'); ?>)
                                                        </div>
                                                        <?php
                                                    }
                                                } else {
                                                    ?>
                                                    <div class="checklist-item"><i class="fas fa-check"></i> No items ordered</div>
                                                    <?php
                                                }
                                                $stmt_order->close();
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                    }
                } else if (!$reservation_id || !$reservation || $status_payment === 'Cancelled') {
                    // Menampilkan pesan jika tidak ada pesanan aktif
                    echo '<div class="card p-2" data-aos="zoom-in" data-aos-duration="800"><p class="text-center m-0">No active orders.</p></div>';
                }
                $stmt_active->close();
                ?>
            </div>

            <!-- Tab untuk riwayat pesanan -->
            <div class="tab-pane fade" id="order-history" role="tabpanel" aria-labelledby="history-tab">
                <?php
                // Mengambil riwayat pesanan yang sudah lunas (OPERASI CRUD: READ)
                $stmt_history = $conn->prepare("
                    SELECT r.*, p.total_tagihan, p.status_payment, p.tanggal_bayar, p.jumlah_dibayar, 
                           a.tersedia, a.nama_area, COALESCE(ir.decoration_theme, 'Not selected') AS decoration_theme
                    FROM reservasi r
                    JOIN area a ON r.id_area = a.id_area
                    JOIN pembayaran p ON r.id_reservasi = p.id_reservasi
                    LEFT JOIN interiorrequest ir ON r.id_reservasi = ir.id_reservasi
                    WHERE r.id_user = ? 
                    AND p.status_payment = 'Fully Paid' 
                    AND a.tersedia = 1 
                    ORDER BY r.tanggal DESC
                ");
                $stmt_history->bind_param("i", $user_id);
                $stmt_history->execute();
                $history_result = $stmt_history->get_result();

                if ($history_result && $history_result->num_rows > 0) {
                    while ($reservation = $history_result->fetch_assoc()) {
                        $res_id = $reservation['id_reservasi'];
                        $total_tagihan = $reservation['total_tagihan'] ?? 0;
                        $jumlah_dibayar = $reservation['jumlah_dibayar'] ?? 0;
                        $tanggal_bayar = $reservation['tanggal_bayar'] ?? 'N/A';
                        ?>
                        <div class="card" data-aos="slide-up" data-aos-duration="800">
                            <div class="card-header">
                                <h5 class="text-anchor mb-0">Reservation #<?php echo htmlspecialchars($res_id); ?> (Completed)</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-2">
                                    <!-- Detail pembayaran -->
                                    <div class="col-md-4">
                                        <h6 class="text-anchor mb-2">Payment</h6>
                                        <p><strong>Total:</strong> IDR <?php echo number_format($total_tagihan, 2, ',', '.'); ?></p>
                                        <p><strong>Paid:</strong> IDR <?php echo number_format($jumlah_dibayar, 2, ',', '.'); ?></p>
                                        <p><strong>Status:</strong> Fully Paid</p>
                                        <p><strong>Date Paid:</strong> <?php echo htmlspecialchars($tanggal_bayar); ?></p>
                                    </div>
                                    <!-- Detail reservasi -->
                                    <div class="col-md-4">
                                        <h6 class="text-anchor mb-2">Reservation</h6>
                                        <p><strong>Area:</strong> <?php echo htmlspecialchars($reservation['nama_area'] ?? 'Not specified'); ?> <span class="badge bg-success ms-1">Available</span></p>
                                        <p><strong>Table:</strong> <?php echo htmlspecialchars($reservation['lokasi_meja'] ?? 'Not specified'); ?></p>
                                        <p><strong>Capacity:</strong> <?php echo htmlspecialchars($reservation['jumlah_orang'] ?? 0); ?> persons</p>
                                        <p><strong>Date:</strong> <?php echo htmlspecialchars($reservation['tanggal'] ?? 'Not specified'); ?></p>
                                        <p><strong>Time:</strong> <?php echo htmlspecialchars($reservation['waktu'] ?? 'Not specified'); ?></p>
                                        <p><strong>Theme:</strong> <?php echo htmlspecialchars($reservation['decoration_theme']); ?></p>
                                    </div>
                                    <!-- Daftar pesanan (OPERASI CRUD: READ) -->
                                    <div class="col-md-4">
                                        <h6 class="text-anchor mb-2">Orders</h6>
                                        <div class="checklist">
                                            <?php
                                            // Mengambil daftar pesanan untuk reservasi ini dari tabel pesanan dan menu
                                            $stmt_order = $conn->prepare("SELECT p.*, m.nama_menu, m.kategori FROM pesanan p JOIN menu m ON p.id_menu = m.id_menu WHERE p.id_reservasi = ?");
                                            $stmt_order->bind_param("i", $res_id);
                                            $stmt_order->execute();
                                            $order_result = $stmt_order->get_result();
                                            if ($order_result && $order_result->num_rows > 0) {
                                                while ($order = $order_result->fetch_assoc()) {
                                                    ?>
                                                    <div class="checklist-item">
                                                        <i class="fas fa-check"></i> 
                                                        <?php echo htmlspecialchars($order['nama_menu']); ?> 
                                                        (Qty: <?php echo htmlspecialchars($order['jumlah']); ?>, <?php echo htmlspecialchars($order['kategori'] ?? 'N/A'); ?>)
                                                    </div>
                                                    <?php
                                                }
                                            } else {
                                                ?>
                                                <div class="checklist-item"><i class="fas fa-check"></i> No items ordered</div>
                                                <?php
                                            }
                                            $stmt_order->close();
                                            ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-center mt-2">
                                    <button class="btn btn-success" disabled>Completed</button>
                                    <p class="text-muted mt-1">Thank You and See You!</p>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    // Menampilkan pesan jika tidak ada riwayat pesanan
                    echo '<div class="card p-2" data-aos="zoom-in" data-aos-duration="800"><p class="text-center m-0">No history found.</p></div>';
                }
                $stmt_history->close();
                ?>
            </div>

            <!-- Tab untuk pesanan yang dibatalkan -->
            <div class="tab-pane fade" id="cancelled-orders" role="tabpanel" aria-labelledby="cancelled-tab">
                <?php
                // Mengambil pesanan yang dibatalkan (OPERASI CRUD: READ)
                $stmt_cancelled = $conn->prepare("
                    SELECT r.*, p.total_tagihan, p.status_payment, p.tanggal_bayar, p.jumlah_dibayar, 
                           a.nama_area, COALESCE(ir.decoration_theme, 'Not selected') AS decoration_theme
                    FROM reservasi r 
                    JOIN area a ON r.id_area = a.id_area
                    LEFT JOIN pembayaran p ON r.id_reservasi = p.id_reservasi 
                    LEFT JOIN interiorrequest ir ON r.id_reservasi = ir.id_reservasi
                    WHERE r.id_user = ? 
                    AND p.status_payment = 'Cancelled' 
                    ORDER BY r.tanggal DESC
                ");
                $stmt_cancelled->bind_param("i", $user_id);
                $stmt_cancelled->execute();
                $cancelled_result = $stmt_cancelled->get_result();

                if ($cancelled_result && $cancelled_result->num_rows > 0) {
                    while ($reservation = $cancelled_result->fetch_assoc()) {
                        $res_id = $reservation['id_reservasi'];
                        $total_tagihan = $reservation['total_tagihan'] ?? 0;
                        $jumlah_dibayar = $reservation['jumlah_dibayar'] ?? 0;
                        $tanggal_bayar = $reservation['tanggal_bayar'] ?? 'N/A';
                        ?>
                        <div class="card" data-aos="slide-up" data-aos-duration="800">
                            <div class="card-header">
                                <h5 class="text-anchor mb-0">Reservation #<?php echo htmlspecialchars($res_id); ?> (Cancelled)</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-2">
                                    <!-- Detail pembayaran -->
                                    <div class="col-md-4">
                                        <h6 class="text-anchor mb-2">Payment</h6>
                                        <p><strong>Total:</strong> IDR <?php echo number_format($total_tagihan, 2, ',', '.'); ?></p>
                                        <p><strong>Paid:</strong> IDR <?php echo number_format($jumlah_dibayar, 2, ',', '.'); ?></p>
                                        <p><strong>Status:</strong> Cancelled</p>
                                        <p><strong>Date Cancelled:</strong> <?php echo htmlspecialchars($tanggal_bayar); ?></p>
                                    </div>
                                    <!-- Detail reservasi -->
                                    <div class="col-md-4">
                                        <h6 class="text-anchor mb-2">Reservation</h6>
                                        <p><strong>Area:</strong> <?php echo htmlspecialchars($reservation['nama_area'] ?? 'Not specified'); ?></p>
                                        <p><strong>Table:</strong> <?php echo htmlspecialchars($reservation['lokasi_meja'] ?? 'Not specified'); ?></p>
                                        <p><strong>Capacity:</strong> <?php echo htmlspecialchars($reservation['jumlah_orang'] ?? 0); ?> persons</p>
                                        <p><strong>Date:</strong> <?php echo htmlspecialchars($reservation['tanggal'] ?? 'Not specified'); ?></p>
                                        <p><strong>Time:</strong> <?php echo htmlspecialchars($reservation['waktu'] ?? 'Not specified'); ?></p>
                                        <p><strong>Theme:</strong> <?php echo htmlspecialchars($reservation['decoration_theme']); ?></p>
                                    </div>
                                    <!-- Daftar pesanan (OPERASI CRUD: READ) -->
                                    <div class="col-md-4">
                                        <h6 class="text-anchor mb-2">Orders</h6>
                                        <div class="checklist">
                                            <?php
                                            // Mengambil daftar pesanan untuk reservasi ini dari tabel pesanan dan menu
                                            $stmt_order = $conn->prepare("SELECT p.*, m.nama_menu, m.kategori FROM pesanan p JOIN menu m ON p.id_menu = m.id_menu WHERE p.id_reservasi = ?");
                                            $stmt_order->bind_param("i", $res_id);
                                            $stmt_order->execute();
                                            $order_result = $stmt_order->get_result();
                                            if ($order_result && $order_result->num_rows > 0) {
                                                while ($order = $order_result->fetch_assoc()) {
                                                    ?>
                                                    <div class="checklist-item">
                                                        <i class="fas fa-check"></i> 
                                                        <?php echo htmlspecialchars($order['nama_menu']); ?> 
                                                        (Qty: <?php echo htmlspecialchars($order['jumlah']); ?>, <?php echo htmlspecialchars($order['kategori'] ?? 'N/A'); ?>)
                                                    </div>
                                                    <?php
                                                }
                                            } else {
                                                ?>
                                                <div class="checklist-item"><i class="fas fa-check"></i> No items ordered</div>
                                                <?php
                                            }
                                            $stmt_order->close();
                                            ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-center mt-2">
                                    <button class="btn btn-danger" disabled>Cancelled</button>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    // Menampilkan pesan jika tidak ada pesanan yang dibatalkan
                    echo '<div class="card p-2" data-aos="zoom-in" data-aos-duration="800"><p class="text-center m-0">No cancelled orders found.</p></div>';
                }
                $stmt_cancelled->close();
                ?>
            </div>
        </div>
    </section>

    <!-- Script untuk Bootstrap, AOS, dan jQuery -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        // Inisialisasi animasi AOS
        AOS.init({ duration: 800, once: true });

        // Menangani timer untuk pembayaran tunai
        const timer = document.getElementById('timer');
        const countdown = document.getElementById('countdown');
        const cancelMessage = document.getElementById('cancelMessage');
        const payButton = document.querySelector('.pay-btn');
        const cancelButton = document.querySelector('.cancel-btn');
        if (timer && <?php echo $payment_method === 'Tunai' && $status_payment === 'Pending Payment' ? 'true' : 'false'; ?>) {
            timer.style.display = 'block';
            if (payButton) payButton.disabled = true;
            if (cancelButton) cancelButton.disabled = true;
            let timeLeft = 60;
            countdown.textContent = timeLeft;

            // Mengatur interval untuk menghitung mundur
            const countdownInterval = setInterval(() => {
                timeLeft--;
                countdown.textContent = timeLeft;
                if (timeLeft <= 0) {
                    clearInterval(countdownInterval);
                    timer.style.display = 'none';
                    cancelMessage.style.display = 'block';
                    // Mengirim permintaan AJAX untuk membatalkan reservasi (OPERASI CRUD: UPDATE)
                    $.ajax({
                        url: window.location.pathname, // Kirim ke halaman yang sama
                        method: 'POST',
                        data: { 
                            cancel_reservation: true, 
                            id_reservasi: <?php echo $reservation_id; ?> 
                        },
                        success: function(response) {
                            // Jika pembatalan berhasil, aktifkan tab "Cancelled Orders" dan refresh halaman
                            const cancelledTab = document.getElementById('cancelled-tab');
                            if (cancelledTab) {
                                cancelledTab.click(); // Aktifkan tab Cancelled Orders
                            }
                            setTimeout(() => {
                                window.location.href = window.location.pathname + '#cancelled-orders';
                            }, 2000); // Refresh setelah 2 detik untuk menampilkan pesan
                        },
                        error: function(xhr, status, error) {
                            cancelMessage.textContent = 'Error cancelling reservation: ' + error;
                            cancelMessage.style.display = 'block';
                        }
                    });
                }
            }, 1000);
        }
    </script>
</body>
</html>