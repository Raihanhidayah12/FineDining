<?php
session_start();
include '../includes/config.php';

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit();
}

$user_id = intval($_SESSION['id_user']);
$reservation_id = isset($_GET['rid']) ? intval($_GET['rid']) : 0;
$payment_method = isset($_GET['method']) ? $_GET['method'] : null;

// Fetch reservation details
$stmt = $conn->prepare("SELECT r.*, a.tersedia FROM reservasi r JOIN area a ON r.id_area = a.id_area WHERE r.id_reservasi = ? AND r.id_user = ?");
$stmt->bind_param("ii", $reservation_id, $user_id);
$stmt->execute();
$reservation = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch payment details
$stmt = $conn->prepare("SELECT * FROM pembayaran WHERE id_reservasi = ?");
$stmt->bind_param("i", $reservation_id);
$stmt->execute();
$pembayaran = $stmt->get_result()->fetch_assoc();
$stmt->close();

$status_payment = $pembayaran['status_payment'] ?? 'Pending Payment';
$total_tagihan = $pembayaran['total_tagihan'] ?? 0;
$jumlah_dibayar = $pembayaran['jumlah_dibayar'] ?? 0;
$deposit_amount = $total_tagihan * 0.3;
$tersedia = $reservation['tersedia'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/assets/img/logo.png" type="image/gif" sizes="16x16">
    <title>My Orders || FineDining</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,400;1,700;1,900&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gold: #d4af37;
            --accent-gold: #e6c74a;
            --dark-bg: #0f172a;
            --light-bg: #1e293b;
            --text-light: #ffffff;
            --text-muted: #ffffff; /* All non-title text white */
            --text-highlight: #ffcc00; /* Titles remain yellow */
            --shadow-soft: 0 8px 20px rgba(0, 0, 0, 0.3);
            --glass-bg: rgba(255, 255, 255, 0.08);
            --button-bg: #d4af37;
            --button-bg-hover: #e6c74a;
            --error-bg: #dc3545;
            --success-bg: #28a745;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            overflow-x: hidden;
            scrollbar-width: none; /* Hide scrollbar for Firefox */
            -ms-overflow-style: none; /* Hide scrollbar for IE/Edge */
        }

        body::-webkit-scrollbar {
            display: none; /* Hide scrollbar for Chrome/Safari */
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(145deg, var(--dark-bg), var(--light-bg));
            color: var(--text-light);
            min-height: 100vh;
        }

        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(212, 175, 55, 0.05) 0%, transparent 70%);
            z-index: -1;
        }

        .container {
            max-width: 1300px;
            padding: 5rem 2rem 3rem;
            margin: 0 auto;
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 3.2rem;
            font-weight: 900;
            color: var(--text-highlight); /* Yellow for main title */
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
            letter-spacing: 1px;
            text-shadow: 0 2px 10px rgba(212, 175, 55, 0.3);
        }

        .section-title::after {
            content: '';
            display: block;
            width: 120px;
            height: 4px;
            background: linear-gradient(to right, var(--primary-gold), var(--accent-gold));
            margin: 1rem auto;
            border-radius: 2px;
        }

        .nav-tabs {
            border-bottom: none;
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 3rem;
        }

        .nav-tabs .nav-link {
            font-family: 'Playfair Display', serif;
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--text-light); /* White for nav tabs */
            background: var(--glass-bg);
            border: 1px solid rgba(212, 175, 55, 0.2);
            border-radius: 10px;
            padding: 0.8rem 2rem;
            transition: all 0.3s ease-in-out;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .nav-tabs .nav-link:hover {
            color: var(--text-light);
            background: rgba(212, 175, 55, 0.1);
            transform: translateY(-2px);
        }

        .nav-tabs .nav-link.active {
            color: var(--text-light);
            background: var(--primary-gold);
            border-color: var(--primary-gold);
            box-shadow: 0 4px 12px rgba(212, 175, 55, 0.3);
        }

        .card {
            background: var(--glass-bg);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(212, 175, 55, 0.15);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            transition: all 0.4s ease;
            box-shadow: var(--shadow-soft);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.4);
        }

        .card-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-highlight); /* Yellow for card titles */
            margin-bottom: 1.5rem;
            letter-spacing: 0.5px;
        }

        .checklist {
            margin-top: 1.5rem;
        }

        .checklist-item {
            display: flex;
            align-items: center;
            font-size: 1.1rem;
            margin-bottom: 0.8rem;
            color: var(--text-light); /* White for checklist items */
        }

        .checklist-item i {
            color: var(--success-bg);
            margin-right: 0.8rem;
            font-size: 1.2rem;
        }

        .btn-primary {
            background: linear-gradient(45deg, var(--button-bg), var(--button-bg-hover));
            border: none;
            color: var(--text-light); /* White for button text */
            border-radius: 30px;
            padding: 0.9rem 2.5rem;
            font-family: 'Playfair Display', serif;
            font-size: 1.1rem;
            font-weight: 700;
            text-transform: uppercase;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(212, 175, 55, 0.3);
        }

        .btn-primary:hover {
            background: linear-gradient(45deg, var(--button-bg-hover), var(--button-bg));
            transform: translateY(-3px);
            box-shadow: 0 6px 18px rgba(212, 175, 55, 0.5);
        }

        .btn-primary:disabled {
            background: #4a4a4a;
            cursor: not-allowed;
            box-shadow: none;
        }

        .btn-success {
            background: var(--success-bg);
            border: none;
            color: var(--text-light); /* White for button text */
            border-radius: 30px;
            padding: 0.9rem 2.5rem;
            font-family: 'Playfair Display', serif;
            font-size: 1.1rem;
            font-weight: 700;
            text-transform: uppercase;
            transition: all 0.3s ease;
        }

        .btn-success:hover {
            background: #1b5e20;
            transform: translateY(-3px);
        }

        .btn-success:disabled {
            background: #4a4a4a;
            cursor: not-allowed;
        }

        .timer {
            font-size: 1.2rem;
            color: var(--text-light); /* White for timer */
            text-align: center;
            margin-top: 1.5rem;
            font-weight: 500;
        }

        .cancel-message {
            background: var(--error-bg);
            color: var(--text-light); /* White for cancel message */
            padding: 0.8rem;
            border-radius: 10px;
            margin-top: 1.5rem;
            text-align: center;
            font-weight: 500;
        }

        .payment-summary, .customization {
            background: rgba(255, 255, 255, 0.05);
            padding: 1.5rem;
            border-radius: 12px;
            margin-top: 1.5rem;
        }

        .payment-summary p, .customization p {
            margin: 0.5rem 0;
            font-size: 1.1rem;
            color: var(--text-light); /* White for summary text */
        }

        .text-muted {
            color: var(--text-light) !important; /* White for muted text */
        }

        @media (max-width: 768px) {
            .section-title {
                font-size: 2.5rem;
            }

            .nav-tabs .nav-link {
                font-size: 1rem;
                padding: 0.6rem 1.5rem;
            }

            .card {
                padding: 1.5rem;
            }

            .card-title {
                font-size: 1.5rem;
            }

            .btn-primary, .btn-success {
                padding: 0.7rem 2rem;
                font-size: 1rem;
            }
        }

        @media (max-width: 576px) {
            .section-title {
                font-size: 2rem;
            }

            .nav-tabs {
                flex-direction: column;
                gap: 0.5rem;
            }

            .nav-tabs .nav-link {
                font-size: 0.9rem;
                padding: 0.5rem 1.2rem;
            }

            .card {
                padding: 1.2rem;
            }

            .card-title {
                font-size: 1.3rem;
            }

            .btn-primary, .btn-success {
                padding: 0.6rem 1.5rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="overlay"></div>

    <section class="container py-5 position-relative">
        <h1 class="section-title" data-aos="fade-down" data-aos-duration="1000">My Orders</h1>

        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" id="active-tab" data-bs-toggle="tab" data-bs-target="#active-orders" type="button" role="tab" aria-controls="active-orders" aria-selected="true">Active Orders</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#order-history" type="button" role="tab" aria-controls="order-history" aria-selected="false">Order History</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="cancelled-tab" data-bs-toggle="tab" data-bs-target="#cancelled-orders" type="button" role="tab" aria-controls="cancelled-orders" aria-selected="false">Cancelled Orders</button>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="active-orders" role="tabpanel" aria-labelledby="active-tab">
                <?php if ($reservation_id && $payment_method === 'Tunai' && $status_payment === 'Pending Payment'): ?>
                    <div class="card" data-aos="fade-up" data-aos-duration="800">
                        <h4 class="card-title">Reservation #<?php echo htmlspecialchars($reservation_id); ?> (Pending Cash)</h4>
                        <div class="checklist">
                            <div class="checklist-item"><i class="fas fa-check-circle"></i> Area: <?php echo htmlspecialchars($conn->query("SELECT nama_area FROM area WHERE id_area = " . $reservation['id_area'])->fetch_assoc()['nama_area'] ?? 'N/A'); ?></div>
                            <div class="checklist-item"><i class="fas fa-check-circle"></i> Table: <?php echo htmlspecialchars($reservation['lokasi_meja'] ?? 'N/A'); ?></div>
                            <div class="checklist-item"><i class="fas fa-check-circle"></i> Capacity: <?php echo htmlspecialchars($reservation['jumlah_orang'] ?? 0); ?> persons</div>
                            <div class="checklist-item"><i class="fas fa-check-circle"></i> Date: <?php echo htmlspecialchars($reservation['tanggal'] ?? 'N/A'); ?></div>
                            <div class="checklist-item"><i class="fas fa-check-circle"></i> Time: <?php echo htmlspecialchars($reservation['waktu'] ?? 'N/A'); ?></div>
                        </div>
                        <h5 class="card-title mt-4">Order Details</h5>
                        <div class="checklist">
                            <?php
                            $order_result = $conn->query("SELECT p.*, m.nama_menu, m.kategori FROM pesanan p JOIN menu m ON p.id_menu = m.id_menu WHERE p.id_reservasi = $reservation_id");
                            if ($order_result && $order_result->num_rows > 0) {
                                while ($order = $order_result->fetch_assoc()) {
                                    echo '<div class="checklist-item"><i class="fas fa-check-circle"></i> ' . htmlspecialchars($order['nama_menu']) . ' (' . htmlspecialchars($order['kategori']) . ') - Qty: ' . htmlspecialchars($order['jumlah']) . '</div>';
                                }
                            } else {
                                echo '<div class="checklist-item"><i class="fas fa-check-circle"></i> No items ordered</div>';
                            }
                            ?>
                        </div>
                        <div class="payment-summary">
                            <h5 class="card-title">Payment Summary</h5>
                            <p><strong>Total:</strong> IDR <?php echo number_format($total_tagihan, 2, ',', '.'); ?></p>
                            <p><strong>Deposit (30%):</strong> IDR <?php echo number_format($deposit_amount, 2, ',', '.'); ?></p>
                            <p><strong>Paid:</strong> IDR <?php echo number_format($jumlah_dibayar, 2, ',', '.'); ?></p>
                        </div>
                        <div id="timer" class="timer">Time left: <span id="countdown">60</span> sec</div>
                        <div id="cancelMessage" class="cancel-message">Order cancelled due to timeout.</div>
                    </div>
                <?php endif; ?>

                <?php
                $active_result = $conn->query("
                    SELECT r.*, p.total_tagihan, p.status_payment, p.tanggal_bayar, p.jumlah_dibayar 
                    FROM reservasi r
                    LEFT JOIN pembayaran p ON r.id_reservasi = p.id_reservasi
                    WHERE r.id_user = $user_id 
                    AND (p.status_payment = 'Pending Payment' OR p.status_payment = 'Deposit Paid' OR p.status_payment = 'Fully Paid')
                    AND r.id_reservasi != $reservation_id
                    ORDER BY r.tanggal DESC
                ");

                if ($active_result && $active_result->num_rows > 0) {
                    while ($reservation = $active_result->fetch_assoc()) {
                        $reservation_id = $reservation['id_reservasi'];
                        $total_tagihan = $reservation['total_tagihan'] ?? 0;
                        $status_payment = $reservation['status_payment'] ?? 'Pending Payment';
                        $tanggal_bayar = $reservation['tanggal_bayar'] ?? 'N/A';
                        $jumlah_dibayar = $reservation['jumlah_dibayar'] ?? 0;
                        $deposit_amount = $total_tagihan * 0.3;
                        $remaining_balance = $total_tagihan - $jumlah_dibayar;
                        $display_status = ($status_payment === 'Pending Payment') ? 'Pending' : ($status_payment === 'Deposit Paid' ? 'Deposit Paid' : 'Fully Paid');
                        ?>
                        <div class="card" data-aos="fade-up" data-aos-duration="800" data-aos-delay="100">
                            <h4 class="card-title">Reservation #<?php echo htmlspecialchars($reservation_id); ?> (<?php echo htmlspecialchars($display_status); ?>)</h4>
                            <div class="checklist">
                                <div class="checklist-item"><i class="fas fa-check-circle"></i> Area: <?php echo htmlspecialchars($conn->query("SELECT nama_area FROM area WHERE id_area = " . $reservation['id_area'])->fetch_assoc()['nama_area'] ?? 'N/A'); ?></div>
                                <div class="checklist-item"><i class="fas fa-check-circle"></i> Table: <?php echo htmlspecialchars($reservation['lokasi_meja'] ?? 'N/A'); ?></div>
                                <div class="checklist-item"><i class="fas fa-check-circle"></i> Capacity: <?php echo htmlspecialchars($reservation['jumlah_orang'] ?? 0); ?> persons</div>
                                <div class="checklist-item"><i class="fas fa-check-circle"></i> Date: <?php echo htmlspecialchars($reservation['tanggal'] ?? 'N/A'); ?></div>
                                <div class="checklist-item"><i class="fas fa-check-circle"></i> Time: <?php echo htmlspecialchars($reservation['waktu'] ?? 'N/A'); ?></div>
                            </div>
                            <h5 class="card-title mt-4">Order Details</h5>
                            <div class="checklist">
                                <?php
                                $order_result = $conn->query("SELECT p.*, m.nama_menu, m.kategori FROM pesanan p JOIN menu m ON p.id_menu = m.id_menu WHERE p.id_reservasi = $reservation_id");
                                if ($order_result && $order_result->num_rows > 0) {
                                    while ($order = $order_result->fetch_assoc()) {
                                        echo '<div class="checklist-item"><i class="fas fa-check-circle"></i> ' . htmlspecialchars($order['nama_menu']) . ' (' . htmlspecialchars($order['kategori']) . ') - Qty: ' . htmlspecialchars($order['jumlah']) . '</div>';
                                    }
                                } else {
                                    echo '<div class="checklist-item"><i class="fas fa-check-circle"></i> No items</div>';
                                }
                                ?>
                            </div>
                            <div class="payment-summary">
                                <h5 class="card-title">Payment Summary</h5>
                                <p><strong>Total:</strong> IDR <?php echo number_format($total_tagihan, 2, ',', '.'); ?></p>
                                <p><strong>Paid:</strong> IDR <?php echo number_format($jumlah_dibayar, 2, ',', '.'); ?></p>
                                <p><strong>Deposit:</strong> IDR <?php echo number_format($deposit_amount, 2, ',', '.'); ?></p>
                                <p><strong>Remaining:</strong> IDR <?php echo number_format($remaining_balance, 2, ',', '.'); ?></p>
                            </div>
                            <div class="customization">
                                <h5 class="card-title">Customization</h5>
                                <p><strong>Theme:</strong> <?php echo htmlspecialchars($conn->query("SELECT decoration_theme FROM interiorrequest WHERE id_reservasi = $reservation_id")->fetch_assoc()['decoration_theme'] ?? 'N/A'); ?></p>
                                <p><strong>Requests:</strong> <?php echo htmlspecialchars($reservation['catatan_tambahan'] ?? 'None'); ?></p>
                            </div>
                            <?php if ($status_payment === 'Pending Payment' && $jumlah_dibayar == 0): ?>
                                <div class="text-center mt-4">
                                    <a href="pembayaran.php?id_reservasi=<?php echo $reservation_id; ?>" class="btn btn-primary">Pay Now</a>
                                </div>
                            <?php elseif ($status_payment === 'Deposit Paid' && $remaining_balance > 0): ?>
                                <div class="text-center mt-4">
                                    <a href="pembayaran_pelunasan.php?id_reservasi=<?php echo $reservation_id; ?>" class="btn btn-primary">Settle Payment</a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php
                    }
                } else {
                    echo '<div class="card" data-aos="fade-up" data-aos-duration="800"><p class="text-center">No active orders.</p></div>';
                }
                ?>
            </div>

            <div class="tab-pane fade" id="order-history" role="tabpanel" aria-labelledby="history-tab">
                <?php
                $history_result = $conn->query("
                    SELECT r.*, p.total_tagihan, p.status_payment, p.tanggal_bayar, p.jumlah_dibayar 
                    FROM reservasi r
                    JOIN pembayaran p ON r.id_reservasi = p.id_reservasi
                    JOIN area a ON r.id_area = a.id_area
                    WHERE r.id_user = $user_id 
                    AND p.status_payment = 'Fully Paid'
                    AND a.tersedia = 1
                    ORDER BY r.tanggal DESC
                ");

                if ($history_result && $history_result->num_rows > 0) {
                    while ($reservation = $history_result->fetch_assoc()) {
                        $reservation_id = $reservation['id_reservasi'];
                        $total_tagihan = $reservation['total_tagihan'] ?? 0;
                        $jumlah_dibayar = $reservation['jumlah_dibayar'] ?? 0;
                        $tanggal_bayar = $reservation['tanggal_bayar'] ?? 'N/A';
                        ?>
                        <div class="card" data-aos="fade-up" data-aos-duration="800" data-aos-delay="100">
                            <h4 class="card-title">Reservation #<?php echo htmlspecialchars($reservation_id); ?> (Completed)</h4>
                            <div class="checklist">
                                <div class="checklist-item"><i class="fas fa-check-circle"></i> Area: <?php echo htmlspecialchars($conn->query("SELECT nama_area FROM area WHERE id_area = " . $reservation['id_area'])->fetch_assoc()['nama_area'] ?? 'N/A'); ?></div>
                                <div class="checklist-item"><i class="fas fa-check-circle"></i> Table: <?php echo htmlspecialchars($reservation['lokasi_meja'] ?? 'N/A'); ?></div>
                                <div class="checklist-item"><i class="fas fa-check-circle"></i> Capacity: <?php echo htmlspecialchars($reservation['jumlah_orang'] ?? 0); ?> persons</div>
                                <div class="checklist-item"><i class="fas fa-check-circle"></i> Date: <?php echo htmlspecialchars($reservation['tanggal'] ?? 'N/A'); ?></div>
                                <div class="checklist-item"><i class="fas fa-check-circle"></i> Time: <?php echo htmlspecialchars($reservation['waktu'] ?? 'N/A'); ?></div>
                            </div>
                            <h5 class="card-title mt-4">Order Details</h5>
                            <div class="checklist">
                                <?php
                                $order_result = $conn->query("SELECT p.*, m.nama_menu, m.kategori FROM pesanan p JOIN menu m ON p.id_menu = m.id_menu WHERE p.id_reservasi = $reservation_id");
                                if ($order_result && $order_result->num_rows > 0) {
                                    while ($order = $order_result->fetch_assoc()) {
                                        echo '<div class="checklist-item"><i class="fas fa-check-circle"></i> ' . htmlspecialchars($order['nama_menu']) . ' (' . htmlspecialchars($order['kategori']) . ') - Qty: ' . htmlspecialchars($order['jumlah']) . '</div>';
                                    }
                                } else {
                                    echo '<div class="checklist-item"><i class="fas fa-check-circle"></i> No items</div>';
                                }
                                ?>
                            </div>
                            <div class="payment-summary">
                                <h5 class="card-title">Payment Summary</h5>
                                <p><strong>Total:</strong> IDR <?php echo number_format($total_tagihan, 2, ',', '.'); ?></p>
                                <p><strong>Paid:</strong> IDR <?php echo number_format($jumlah_dibayar, 2, ',', '.'); ?></p>
                                <p><strong>Status:</strong> Lunas</p>
                                <p><strong>Date Paid:</strong> <?php echo htmlspecialchars($tanggal_bayar); ?></p>
                            </div>
                            <div class="customization">
                                <h5 class="card-title">Customization</h5>
                                <p><strong>Theme:</strong> <?php echo htmlspecialchars($conn->query("SELECT decoration_theme FROM interiorrequest WHERE id_reservasi = $reservation_id")->fetch_assoc()['decoration_theme'] ?? 'N/A'); ?></p>
                                <p><strong>Requests:</strong> <?php echo htmlspecialchars($reservation['catatan_tambahan'] ?? 'None'); ?></p>
                            </div>
                            <div class="text-center mt-4">
                                <button class="btn btn-success" disabled>Completed</button>
                            </div>
                            <p class="text-center text-muted mt-2">Thank You and See You!</p>
                        </div>
                        <?php
                    }
                } else {
                    echo '<div class="card" data-aos="fade-up" data-aos-duration="800"><p class="text-center">No history found.</p></div>';
                }
                ?>
            </div>

            <div class="tab-pane fade" id="cancelled-orders" role="tabpanel" aria-labelledby="cancelled-tab">
                <?php
                $cancelled_result = $conn->query("
                    SELECT r.*, p.total_tagihan, p.status_payment, p.tanggal_bayar, p.jumlah_dibayar 
                    FROM reservasi r
                    LEFT JOIN pembayaran p ON r.id_reservasi = p.id_reservasi
                    WHERE r.id_user = $user_id 
                    AND p.status_payment = 'Cancelled'
                    ORDER BY r.tanggal DESC
                ");

                if ($cancelled_result && $cancelled_result->num_rows > 0) {
                    while ($reservation = $cancelled_result->fetch_assoc()) {
                        $reservation_id = $reservation['id_reservasi'];
                        $total_tagihan = $reservation['total_tagihan'] ?? 0;
                        $jumlah_dibayar = $reservation['jumlah_dibayar'] ?? 0;
                        $tanggal_bayar = $reservation['tanggal_bayar'] ?? 'N/A';
                        ?>
                        <div class="card" data-aos="fade-up" data-aos-duration="800" data-aos-delay="100">
                            <h4 class="card-title">Reservation #<?php echo htmlspecialchars($reservation_id); ?> (Cancelled)</h4>
                            <div class="checklist">
                                <div class="checklist-item"><i class="fas fa-check-circle"></i> Area: <?php echo htmlspecialchars($conn->query("SELECT nama_area FROM area WHERE id_area = " . $reservation['id_area'])->fetch_assoc()['nama_area'] ?? 'N/A'); ?></div>
                                <div class="checklist-item"><i class="fas fa-check-circle"></i> Table: <?php echo htmlspecialchars($reservation['lokasi_meja'] ?? 'N/A'); ?></div>
                                <div class="checklist-item"><i class="fas fa-check-circle"></i> Capacity: <?php echo htmlspecialchars($reservation['jumlah_orang'] ?? 0); ?> persons</div>
                                <div class="checklist-item"><i class="fas fa-check-circle"></i> Date: <?php echo htmlspecialchars($reservation['tanggal'] ?? 'N/A'); ?></div>
                                <div class="checklist-item"><i class="fas fa-check-circle"></i> Time: <?php echo htmlspecialchars($reservation['waktu'] ?? 'N/A'); ?></div>
                            </div>
                            <h5 class="card-title mt-4">Order Details</h5>
                            <div class="checklist">
                                <?php
                                $order_result = $conn->query("SELECT p.*, m.nama_menu, m.kategori FROM pesanan p JOIN menu m ON p.id_menu = m.id_menu WHERE p.id_reservasi = $reservation_id");
                                if ($order_result && $order_result->num_rows > 0) {
                                    while ($order = $order_result->fetch_assoc()) {
                                        echo '<div class="checklist-item"><i class="fas fa-check-circle"></i> ' . htmlspecialchars($order['nama_menu']) . ' (' . htmlspecialchars($order['kategori']) . ') - Qty: ' . htmlspecialchars($order['jumlah']) . '</div>';
                                    }
                                } else {
                                    echo '<div class="checklist-item"><i class="fas fa-check-circle"></i> No items</div>';
                                }
                                ?>
                            </div>
                            <div class="payment-summary">
                                <h5 class="card-title">Payment Summary</h5>
                                <p><strong>Total:</strong> IDR <?php echo number_format($total_tagihan, 2, ',', '.'); ?></p>
                                <p><strong>Paid:</strong> IDR <?php echo number_format($jumlah_dibayar, 2, ',', '.'); ?></p>
                                <p><strong>Status:</strong> Cancelled</p>
                                <p><strong>Date Cancelled:</strong> <?php echo htmlspecialchars($tanggal_bayar); ?></p>
                            </div>
                            <div class="text-center mt-4">
                                <button class="btn btn-success" disabled>Cancelled</button>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    echo '<div class="card" data-aos="fade-up" data-aos-duration="800"><p class="text-center">No cancelled orders.</p></div>';
                }
                ?>
            </div>
        </div>

        <div class="text-center mt-5">
            <a href="../customer.php" class="btn btn-primary">Back to Dashboard</a>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        AOS.init({ duration: 1000, once: true });

        const timer = document.getElementById('timer');
        const countdown = document.getElementById('countdown');
        const cancelMessage = document.getElementById('cancelMessage');
        if (timer && <?php echo $payment_method === 'Tunai' && $status_payment === 'Pending Payment' ? 'true' : 'false'; ?>) {
            timer.style.display = 'block';
            let timeLeft = 60;
            countdown.textContent = timeLeft;

            const countdownInterval = setInterval(() => {
                timeLeft--;
                countdown.textContent = timeLeft;
                if (timeLeft <= 0) {
                    clearInterval(countdownInterval);
                    timer.style.display = 'none';
                    cancelMessage.style.display = 'block';
                    $.ajax({
                        url: 'cancel_reservation.php',
                        method: 'POST',
                        data: { reservation_id: <?php echo $reservation_id; ?> },
                        success: function(response) {
                            setTimeout(() => window.location.reload(), 2000);
                        },
                        error: function() {
                            alert('Error cancelling reservation.');
                        }
                    });
                }
            }, 1000);
        }
    </script>
</body>
</html>