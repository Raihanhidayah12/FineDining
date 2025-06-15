<?php
session_start();
include '../includes/config.php';

// Set time zone to WIB (Asia/Jakarta)
date_default_timezone_set('Asia/Jakarta');

// Ensure the user is a cashier
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'kasir') {
    header("Location: login.php");
    exit();
}

// Track processed reservations to disable buttons
$processed_reservations = [];

if (isset($_POST['process_payment']) && isset($_POST['id_reservasi']) && isset($_POST['amount_paid'])) {
    $reservation_id = intval($_POST['id_reservasi']);
    $amount_paid = floatval($_POST['amount_paid']);

    $stmt = $conn->prepare("SELECT total_tagihan, jumlah_dibayar, status_payment FROM pembayaran WHERE id_reservasi = ? LIMIT 1");
    $stmt->bind_param("i", $reservation_id);
    $stmt->execute();
    $payment_result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($payment_result) {
        $total_tagihan = $payment_result['total_tagihan'];
        $jumlah_dibayar = $payment_result['jumlah_dibayar'] ?? 0;
        $current_status = $payment_result['status_payment'] ?? 'Pending Payment';
        $remaining_balance = $total_tagihan - $jumlah_dibayar;
        $deposit_amount = $total_tagihan * 0.3;

        $new_jumlah_dibayar = min($jumlah_dibayar + $amount_paid, $total_tagihan);
        $change = $amount_paid - ($remaining_balance > 0 ? $remaining_balance : 0);

        if ($new_jumlah_dibayar >= $total_tagihan) {
            $status_payment = 'Fully Paid';
        } elseif ($new_jumlah_dibayar >= $deposit_amount) {
            $status_payment = 'Deposit Paid';
        } else {
            $status_payment = 'Pending Payment';
        }

        $stmt = $conn->prepare("UPDATE pembayaran SET jumlah_dibayar = ?, status_payment = ?, tanggal_bayar = NOW() WHERE id_reservasi = ?");
        $stmt->bind_param("dsi", $new_jumlah_dibayar, $status_payment, $reservation_id);
        if ($stmt->execute()) {
            $processed_reservations[$reservation_id] = true;
            if ($change >= 0) {
                echo '<div class="alert alert-success text-center" role="alert"><strong>Change:</strong> IDR ' . number_format($change, 2, ',', '.') . '</div>';
            }
        } else {
            error_log("Failed to update pembayaran: " . $stmt->error);
        }
        $stmt->close();
    } else {
        echo '<div class="alert alert-danger text-center" role="alert">Payment record not found.</div>';
    }
}

if (isset($_POST['mark_success']) && isset($_POST['id_reservasi'])) {
    $reservation_id = intval($_POST['id_reservasi']);
    if (!isset($processed_reservations[$reservation_id])) {
        $stmt = $conn->prepare("SELECT total_tagihan, jumlah_dibayar, status_payment FROM pembayaran WHERE id_reservasi = ? LIMIT 1");
        $stmt->bind_param("i", $reservation_id);
        $stmt->execute();
        $payment_result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($payment_result) {
            $total_tagihan = $payment_result['total_tagihan'];
            $jumlah_dibayar = $payment_result['jumlah_dibayar'] ?? 0;
            $current_status = $payment_result['status_payment'] ?? 'Pending Payment';

            if ($current_status === 'Fully Paid' && $jumlah_dibayar >= $total_tagihan) {
                $stmt = $conn->prepare("SELECT id_area FROM reservasi WHERE id_reservasi = ?");
                $stmt->bind_param("i", $reservation_id);
                $stmt->execute();
                $area_result = $stmt->get_result()->fetch_assoc();
                if ($area_result) {
                    $area_id = $area_result['id_area'];
                    $stmt->close();

                    $stmt = $conn->prepare("SELECT tersedia FROM area WHERE id_area = ?");
                    $stmt->bind_param("i", $area_id);
                    $stmt->execute();
                    $area_status = $stmt->get_result()->fetch_assoc()['tersedia'];
                    $stmt->close();

                    if ($area_status == 0) {
                        $stmt = $conn->prepare("UPDATE area SET tersedia = 1 WHERE id_area = ?");
                        $stmt->bind_param("i", $area_id);
                        $stmt->execute();
                        $area_updated = $stmt->affected_rows > 0;
                        $stmt->close();

                        $stmt = $conn->prepare("UPDATE pesanan SET status_pesanan = 'selesai', tanggal_selesai = NOW() WHERE id_reservasi = ?");
                        $stmt->bind_param("i", $reservation_id);
                        if ($stmt->execute()) {
                            $affected_rows = $stmt->affected_rows;
                            if ($affected_rows > 0) {
                                $pesanan_updated = true;
                                error_log("Successfully updated $affected_rows rows in pesanan for id_reservasi: $reservation_id");
                            } else {
                                $pesanan_updated = false;
                                error_log("No rows updated in pesanan for id_reservasi: $reservation_id");
                            }
                        } else {
                            $pesanan_updated = false;
                            error_log("Failed to update pesanan: " . $stmt->error);
                        }
                        $stmt->close();

                        if ($area_updated && $pesanan_updated) {
                            $processed_reservations[$reservation_id] = true;
                            echo '<div class="alert alert-success text-center" role="alert">Area marked as available and orders completed.</div>';
                        } else {
                            echo '<div class="alert alert-danger text-center" role="alert">Error updating area or order status. Check logs.</div>';
                        }
                    } else {
                        echo '<div class="alert alert-danger text-center" role="alert">Area is already available.</div>';
                    }
                } else {
                    echo '<div class="alert alert-danger text-center" role="alert">Reservation not found.</div>';
                }
            } else {
                echo '<div class="alert alert-danger text-center" role="alert">Cannot mark area as available: Payment not fully completed.</div>';
            }
        } else {
            echo '<div class="alert alert-danger text-center" role="alert">Payment details not found.</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/assets/img/logo.png" type="image/gif" sizes="16x16">
    <title>Cashier Dashboard || FineDining</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,400;1,700;1,900&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
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
            border-radius: 4px;
        }

        .input-group .form-control {
            border-radius: 6px 0 0 6px;
            background: rgba(255, 255, 255, 0.05);
            border: var(--gold-line);
            color: var(--text-light);
            font-size: 0.9rem;
        }

        .input-group .btn-primary {
            border-radius: 0 6px 6px 0;
            padding: 0.4rem 0.8rem;
            font-size: 0.9rem;
        }

        .container {
            padding: 1.5rem;
            min-height: 100vh;
        }

        @media (max-width: 768px) {
            .section-title { font-size: 1.8rem; }
            .btn-primary, .btn-success { padding: 0.4rem 0.8rem; font-size: 0.9rem; }
            .nav-tabs .nav-link { font-size: 0.9rem; padding: 0.4rem 0.8rem; }
        }

        @media (max-width: 576px) {
            .section-title { font-size: 1.5rem; }
            .btn-primary, .btn-success { padding: 0.3rem 0.6rem; font-size: 0.8rem; }
            .nav-tabs .nav-link { font-size: 0.8rem; padding: 0.3rem 0.6rem; }
            .card { padding: 0.75rem; }
        }
    </style>
</head>
<body>
    <div class="overlay"></div>

    <section class="container">
        <div class="header-row">
            <h1 class="section-title m-0" data-aos="zoom-in" data-aos-duration="800">Cashier Dashboard</h1>
            <a href="../logout.php" class="btn btn-primary"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
        </div>

        <ul class="nav nav-tabs" id="cashierTabs" role="tablist" data-aos="fade-up" data-aos-duration="800">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="active-tab" data-bs-toggle="tab" data-bs-target="#active" type="button" role="tab">Active Reservations</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history" type="button" role="tab">History</button>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="active" role="tabpanel">
                <?php
                $stmt = $conn->prepare("SELECT r.*, u.nama, u.username, p.total_tagihan, p.jumlah_dibayar, p.status_payment, p.tanggal_bayar, a.tersedia AS area_tersedia 
                                      FROM reservasi r 
                                      JOIN user u ON r.id_user = u.id_user 
                                      LEFT JOIN pembayaran p ON r.id_reservasi = p.id_reservasi 
                                      JOIN area a ON r.id_area = a.id_area 
                                      WHERE u.aktif = 1 AND (p.status_payment != 'Fully Paid' OR a.tersedia = 0)
                                      ORDER BY r.tanggal DESC");
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result && $result->num_rows > 0) {
                    while ($reservation = $result->fetch_assoc()) {
                        $reservation_id = $reservation['id_reservasi'];
                        $total_tagihan = $reservation['total_tagihan'] ?? 0;
                        $jumlah_dibayar = $reservation['jumlah_dibayar'] ?? 0;
                        $status_payment = $reservation['status_payment'] ?? 'Pending Payment';
                        $tanggal_bayar = $reservation['tanggal_bayar'] ?? 'Not set';
                        $area_status = $reservation['area_tersedia'];
                        $deposit_amount = $total_tagihan * 0.3;
                        $remaining_balance = $total_tagihan - $jumlah_dibayar;

                        $display_status = 'Unknown';
                        if ($status_payment === 'Pending Payment') {
                            $display_status = 'Pending';
                        } elseif ($status_payment === 'Deposit Paid') {
                            $display_status = 'Deposit';
                        } elseif ($status_payment === 'Fully Paid') {
                            $display_status = 'Lunas';
                        }

                        ?>
                        <div class="card" data-aos="slide-up" data-aos-duration="800">
                            <div class="card-header">
                                <h5 class="text-anchor mb-0">
                                    #<?php echo htmlspecialchars($reservation_id); ?> - <?php echo htmlspecialchars($reservation['nama']); ?>
                                    <span class="badge bg-<?php echo $status_payment === 'Fully Paid' ? 'success' : ($status_payment === 'Deposit Paid' ? 'warning' : 'danger'); ?> ms-2">
                                        <?php echo htmlspecialchars($display_status); ?>
                                    </span>
                                </h5>
                                <button class="btn btn-link text-light p-0" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo $reservation_id; ?>">
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                            </div>
                            <div class="collapse show" id="collapse-<?php echo $reservation_id; ?>">
                                <div class="card-body">
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <h6 class="text-anchor mb-2">Payment</h6>
                                            <p><strong>Total:</strong> IDR <?php echo number_format($total_tagihan, 2, ',', '.'); ?></p>
                                            <p><strong>Paid:</strong> IDR <?php echo number_format($jumlah_dibayar, 2, ',', '.'); ?></p>
                                            <p><strong>Remaining:</strong> IDR <?php echo number_format(max($remaining_balance, 0), 2, ',', '.'); ?></p>
                                            <p><strong>Deposit (30%):</strong> IDR <?php echo number_format($deposit_amount, 2, ',', '.'); ?></p>
                                            <?php if ($status_payment === 'Pending Payment' || ($status_payment === 'Deposit Paid' && $remaining_balance > 0)) { ?>
                                                <form method="post" class="mt-2">
                                                    <input type="hidden" name="id_reservasi" value="<?php echo $reservation_id; ?>">
                                                    <div class="input-group mb-2">
                                                        <input type="number" name="amount_paid" class="form-control" placeholder="Amount (IDR)" step="0.01" required>
                                                        <button type="submit" name="process_payment" class="btn btn-primary"><i class="fas fa-money-bill-wave"></i></button>
                                                    </div>
                                                </form>
                                            <?php } else { ?>
                                                <button class="btn btn-success w-100 mb-2" disabled><i class="fas fa-check me-1"></i>Completed</button>
                                            <?php } ?>
                                            <?php if (($status_payment === 'Fully Paid' || $status_payment === 'Deposit Paid') && $area_status == 0 && !isset($processed_reservations[$reservation_id])) { ?>
                                                <form method="post">
                                                    <input type="hidden" name="id_reservasi" value="<?php echo $reservation_id; ?>">
                                                    <button type="submit" name="mark_success" class="btn btn-success w-100"><i class="fas fa-check me-1"></i>Mark Available</button>
                                                </form>
                                            <?php } elseif (isset($processed_reservations[$reservation_id]) || $area_status == 1) { ?>
                                                <button class="btn btn-success w-100" disabled><i class="fas fa-check me-1"></i>Available</button>
                                            <?php } ?>
                                        </div>
                                        <div class="col-md-4">
                                            <h6 class="text-anchor mb-2">Reservation</h6>
                                            <p><strong>Area:</strong> <?php 
                                                $stmt_area = $conn->prepare("SELECT nama_area FROM area WHERE id_area = ?");
                                                $stmt_area->bind_param("i", $reservation['id_area']);
                                                $stmt_area->execute();
                                                $area_name = $stmt_area->get_result()->fetch_assoc()['nama_area'] ?? 'Not specified';
                                                $stmt_area->close();
                                                echo htmlspecialchars($area_name); ?> 
                                                <span class="badge bg-<?php echo $area_status == 0 ? 'danger' : 'success'; ?> ms-1">
                                                    <?php echo $area_status == 0 ? 'Unavailable' : 'Available'; ?>
                                                </span>
                                            </p>
                                            <p><strong>Table:</strong> <?php echo htmlspecialchars($reservation['lokasi_meja'] ?? 'Not specified'); ?></p>
                                            <p><strong>Capacity:</strong> <?php echo htmlspecialchars($reservation['jumlah_orang'] ?? 0); ?> persons</p>
                                            <p><strong>Date:</strong> <?php echo htmlspecialchars($reservation['tanggal'] ?? 'Not specified'); ?></p>
                                            <p><strong>Time:</strong> <?php echo htmlspecialchars($reservation['waktu'] ?? 'Not specified'); ?></p>
                                            <?php
                                            $stmt_interior = $conn->prepare("SELECT decoration_theme FROM interiorrequest WHERE id_request = ?");
                                            $stmt_interior->bind_param("i", $reservation_id);
                                            $stmt_interior->execute();
                                            $interior_result = $stmt_interior->get_result()->fetch_assoc();
                                            $decoration_theme = $interior_result ? $interior_result['decoration_theme'] : 'Not selected';
                                            $stmt_interior->close();
                                            ?>
                                            <p><strong>Theme:</strong> <?php echo htmlspecialchars($decoration_theme); ?></p>
                                        </div>
                                        <div class="col-md-4">
                                            <h6 class="text-anchor mb-2">Orders</h6>
                                            <div class="checklist">
                                                <?php
                                                $stmt_order = $conn->prepare("SELECT p.*, m.nama_menu, m.kategori FROM pesanan p JOIN menu m ON p.id_menu = m.id_menu WHERE p.id_reservasi = ?");
                                                $stmt_order->bind_param("i", $reservation_id);
                                                $stmt_order->execute();
                                                $order_result = $stmt_order->get_result();
                                                if ($order_result && $order_result->num_rows > 0) {
                                                    while ($order = $order_result->fetch_assoc()) {
                                                        $status_pesanan = $order['status_pesanan'] ?? 'dipesan';
                                                        ?>
                                                        <div class="checklist-item">
                                                            <i class="fas fa-check"></i> 
                                                            <?php echo htmlspecialchars($order['nama_menu']); ?> 
                                                            (Qty: <?php echo htmlspecialchars($order['jumlah']); ?>, <?php echo htmlspecialchars($status_pesanan); ?>)
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
                        </div>
                        <?php
                    }
                } else {
                    ?>
                    <div class="card p-2" data-aos="zoom-in" data-aos-duration="800">
                        <p class="text-center m-0">No active reservations found.</p>
                    </div>
                    <?php
                }
                $stmt->close();
                ?>
            </div>

            <div class="tab-pane fade" id="history" role="tabpanel">
                <?php
                $stmt_history = $conn->prepare("SELECT r.*, u.nama, u.username, p.total_tagihan, p.jumlah_dibayar, p.status_payment, p.tanggal_bayar, a.tersedia AS area_tersedia 
                                              FROM reservasi r 
                                              JOIN user u ON r.id_user = u.id_user 
                                              LEFT JOIN pembayaran p ON r.id_reservasi = p.id_reservasi 
                                              JOIN area a ON r.id_area = a.id_area 
                                              WHERE p.status_payment = 'Fully Paid' AND a.tersedia = 1
                                              ORDER BY r.tanggal DESC");
                $stmt_history->execute();
                $history_result = $stmt_history->get_result();

                if ($history_result && $history_result->num_rows > 0) {
                    while ($reservation = $history_result->fetch_assoc()) {
                        $reservation_id = $reservation['id_reservasi'];
                        $total_tagihan = $reservation['total_tagihan'] ?? 0;
                        $jumlah_dibayar = $reservation['jumlah_dibayar'] ?? 0;
                        $status_payment = $reservation['status_payment'] ?? 'Pending Payment';
                        $tanggal_bayar = $reservation['tanggal_bayar'] ?? 'Not set';
                        $area_status = $reservation['area_tersedia'];
                        $deposit_amount = $total_tagihan * 0.3;
                        $remaining_balance = $total_tagihan - $jumlah_dibayar;

                        $display_status = 'Unknown';
                        if ($status_payment === 'Pending Payment') {
                            $display_status = 'Pending';
                        } elseif ($status_payment === 'Deposit Paid') {
                            $display_status = 'Deposit';
                        } elseif ($status_payment === 'Fully Paid') {
                            $display_status = 'Paid Off';
                        }

                        ?>
                        <div class="card" data-aos="slide-up" data-aos-duration="800">
                            <div class="card-header">
                                <h5 class="text-anchor mb-0">
                                    #<?php echo htmlspecialchars($reservation_id); ?> - <?php echo htmlspecialchars($reservation['nama']); ?>
                                    <span class="badge bg-success ms-2">Completed</span>
                                </h5>
                                <button class="btn btn-link text-light p-0" type="button" data-bs-toggle="collapse" data-bs-target="#history-collapse-<?php echo $reservation_id; ?>">
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                            </div>
                            <div class="collapse" id="history-collapse-<?php echo $reservation_id; ?>">
                                <div class="card-body">
                                    <div class="row g-2">
                                        <div class="col-md-4">
                                            <h6 class="text-anchor mb-2">Payment</h6>
                                            <p><strong>Total:</strong> IDR <?php echo number_format($total_tagihan, 2, ',', '.'); ?></p>
                                            <p><strong>Paid:</strong> IDR <?php echo number_format($jumlah_dibayar, 2, ',', '.'); ?></p>
                                            <p><strong>Date Paid:</strong> <?php echo htmlspecialchars($tanggal_bayar); ?></p>
                                            <p><strong>Deposit (30%):</strong> IDR <?php echo number_format($deposit_amount, 2, ',', '.'); ?></p>
                                        </div>
                                        <div class="col-md-4">
                                            <h6 class="text-anchor mb-2">Reservation</h6>
                                            <p><strong>Area:</strong> <?php 
                                                $stmt_area = $conn->prepare("SELECT nama_area FROM area WHERE id_area = ?");
                                                $stmt_area->bind_param("i", $reservation['id_area']);
                                                $stmt_area->execute();
                                                $area_name = $stmt_area->get_result()->fetch_assoc()['nama_area'] ?? 'Not specified';
                                                $stmt_area->close();
                                                echo htmlspecialchars($area_name); ?> 
                                                <span class="badge bg-success ms-1">Available</span>
                                            </p>
                                            <p><strong>Table:</strong> <?php echo htmlspecialchars($reservation['lokasi_meja'] ?? 'Not specified'); ?></p>
                                            <p><strong>Capacity:</strong> <?php echo htmlspecialchars($reservation['jumlah_orang'] ?? 0); ?> persons</p>
                                            <p><strong>Date:</strong> <?php echo htmlspecialchars($reservation['tanggal'] ?? 'Not specified'); ?></p>
                                            <p><strong>Time:</strong> <?php echo htmlspecialchars($reservation['waktu'] ?? 'Not specified'); ?></p>
                                            <?php
                                            $stmt_interior = $conn->prepare("SELECT decoration_theme FROM interiorrequest WHERE id_request = ?");
                                            $stmt_interior->bind_param("i", $reservation_id);
                                            $stmt_interior->execute();
                                            $interior_result = $stmt_interior->get_result()->fetch_assoc();
                                            $decoration_theme = $interior_result ? $interior_result['decoration_theme'] : 'Not selected';
                                            $stmt_interior->close();
                                            ?>
                                            <p><strong>Theme:</strong> <?php echo htmlspecialchars($decoration_theme); ?></p>
                                        </div>
                                        <div class="col-md-4">
                                            <h6 class="text-anchor mb-2">Orders</h6>
                                            <div class="checklist">
                                                <?php
                                                $stmt_order = $conn->prepare("SELECT p.*, m.nama_menu, m.kategori FROM pesanan p JOIN menu m ON p.id_menu = m.id_menu WHERE p.id_reservasi = ?");
                                                $stmt_order->bind_param("i", $reservation_id);
                                                $stmt_order->execute();
                                                $order_result = $stmt_order->get_result();
                                                if ($order_result && $order_result->num_rows > 0) {
                                                    while ($order = $order_result->fetch_assoc()) {
                                                        $status_pesanan = $order['status_pesanan'] ?? 'dipesan';
                                                        ?>
                                                        <div class="checklist-item">
                                                            <i class="fas fa-check"></i> 
                                                            <?php echo htmlspecialchars($order['nama_menu']); ?> 
                                                            (Qty: <?php echo htmlspecialchars($order['jumlah']); ?>, <?php echo htmlspecialchars($status_pesanan); ?>)
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
                        </div>
                        <?php
                    }
                } else {
                    ?>
                    <div class="card p-2" data-aos="zoom-in" data-aos-duration="800">
                        <p class="text-center m-0">No history found.</p>
                    </div>
                    <?php
                }
                $stmt_history->close();
                ?>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script>
        AOS.init({ duration: 800, once: true });
    </script>
</body>
</html>