<?php
session_start();
include '../includes/config.php';

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit();
}

$reservation_id = isset($_GET['id_reservasi']) ? intval($_GET['id_reservasi']) : 0;
$user_id = intval($_SESSION['id_user']);

// Fetch reservation and payment details
$payment_result = $conn->query("SELECT p.total_tagihan, p.jumlah_dibayar, p.status_payment, r.id_user 
                               FROM pembayaran p 
                               JOIN reservasi r ON p.id_reservasi = r.id_reservasi 
                               WHERE p.id_reservasi = $reservation_id LIMIT 1")->fetch_assoc();

if (!$payment_result || $payment_result['id_user'] != $user_id) {
    die("Invalid reservation or unauthorized access.");
}

$total_tagihan = $payment_result['total_tagihan'];
$jumlah_dibayar = $payment_result['jumlah_dibayar'];
$status_payment = $payment_result['status_payment'];
$deposit_amount = $total_tagihan * 0.3;
$remaining_balance = $total_tagihan - $jumlah_dibayar;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn->begin_transaction();
    try {
        // Validasi: Hanya izinkan pelunasan jika status adalah 'Deposit Paid' dan remaining balance > 0
        if ($status_payment !== 'Deposit Paid' || $remaining_balance <= 0) {
            throw new Exception("Invalid payment status for final payment or no balance remaining.");
        }

        // Update pembayaran dengan total tagihan penuh
        $new_jumlah_dibayar = $total_tagihan;
        $conn->query("UPDATE pembayaran SET status_payment = 'Fully Paid', jumlah_dibayar = $new_jumlah_dibayar, tanggal_bayar = NOW() 
                      WHERE id_reservasi = $reservation_id");
        $conn->query("UPDATE reservasi SET payment_status = 'Fully Paid' WHERE id_reservasi = $reservation_id");

        $conn->commit();
        header("Location: myorder.php?success=1&rid=$reservation_id");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/assets/img/logo.png" type="image/gif" sizes="16x16">
    <title>Final Payment || FineDining</title>
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
            --shadow: 0 10px 20px rgba(0, 0, 0, 0.5);
            --glass-bg: rgba(255, 255, 255, 0.1);
            --button-bg: #ff8c00;
            --button-bg-hover: #ffa500;
            --error-bg: #dc3545;
            --success-bg: #28a745;
            --gold-line: 1px solid rgba(212, 175, 55, 0.5);
        }

        html, body { margin: 0; padding: 0; overflow-x: hidden !important; }
        * { box-sizing: border-box; }
        body::-webkit-scrollbar { display: none; }

        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(to bottom, var(--dark-bg) 0%, var(--light-bg) 70%);
            color: var(--text-light);
            overflow-y: auto;
            min-height: 100vh;
        }

        .overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.1); z-index: -1; }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 900;
            color: var(--text-highlight);
            text-shadow: 0 2px 15px rgba(255, 204, 0, 0.3);
            margin-bottom: 3rem;
            padding: 0 1rem;
            text-align: center;
            position: relative;
        }

        .section-title::after {
            content: '';
            display: block;
            width: 120px;
            height: 4px;
            background: linear-gradient(to right, var(--primary-gold), var(--accent-gold));
            margin: 1.5rem auto 0;
            box-shadow: 0 0 15px rgba(212, 175, 55, 0.4);
        }

        .card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(212, 175, 55, 0.1);
            border-radius: 15px;
            overflow: hidden;
            transition: transform 0.5s ease, box-shadow 0.5s ease;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
            padding: 1rem;
            max-width: 100%;
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(212, 175, 55, 0.2);
            border: 1px solid rgba(212, 175, 55, 0.3);
        }

        .text-anchor { color: var(--text-highlight); font-weight: 700; }

        .error-message {
            background: var(--error-bg);
            color: #fff;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 1rem;
            margin-left: 1rem;
        }

        .checklist { background: var(--glass-bg); border: 1px solid rgba(212, 175, 55, 0.1); border-radius: 10px; padding: 15px; margin-top: 1rem; }
        .checklist-item { display: flex; align-items: center; margin-bottom: 10px; padding: 0 0.5rem; color: var(--text-light); }
        .checklist-item i { color: var(--success-bg); margin-right: 10px; }

        p strong { color: var(--text-highlight); font-weight: 600; text-shadow: 0 1px 5px rgba(255, 204, 0, 0.2); }
        p { color: var(--text-light); font-size: 1.1rem; line-height: 1.6; transition: color 0.3s ease; margin: 0.5rem 0; padding: 0 0.5rem; }
        p:hover { color: var(--accent-gold); }

        .btn-primary {
            background: linear-gradient(to bottom, var(--button-bg), var(--button-bg-hover));
            border: none;
            color: #fff;
            border-radius: 10px;
            padding: 15px 40px;
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 1.4rem;
            transition: all 0.4s ease;
            box-shadow: 0 5px 15px rgba(212, 175, 55, 0.6);
            margin: 0 10px;
        }

        .btn-primary:hover {
            transform: translateY(-5px);
            background: linear-gradient(to bottom, var(--button-bg-hover), var(--button-bg));
            box-shadow: 0 10px 25px rgba(212, 175, 55, 0.6);
        }

        .btn-primary:active {
            transform: translateY(0);
            box-shadow: 0 3px 10px rgba(212, 175, 55, 0.3);
        }

        .btn-primary:disabled { background: #999; cursor: not-allowed; box-shadow: none; }

        .btn-success {
            background-color: var(--success-bg);
            border: none;
            color: #fff;
            border-radius: 10px;
            padding: 15px 40px;
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 1.4rem;
            transition: all 0.4s ease;
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.6);
        }

        .btn-success:hover {
            transform: translateY(-5px);
            background-color: #218838;
            box-shadow: 0 10px 25px rgba(40, 167, 69, 0.6);
        }

        .btn-success:active {
            transform: translateY(0);
            box-shadow: 0 3px 10px rgba(40, 167, 69, 0.3);
        }

        .btn-success:disabled { background: #999; cursor: not-allowed; box-shadow: none; }

        .container {
            padding-bottom: 6rem;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 0 1rem;
        }

        .button-container { margin-top: auto; padding-top: 2rem; text-align: center; }

        .success-check {
            color: var(--success-bg);
            font-size: 1.5rem;
            margin: 1rem 0;
            text-align: center;
        }

        @media (max-width: 768px) {
            .section-title { font-size: 2rem; }
            .btn-primary, .btn-success { padding: 10px 25px; font-size: 1.2rem; }
        }

        @media (max-width: 576px) {
            .section-title { font-size: 1.5rem; }
            .btn-primary, .btn-success { padding: 8px 15px; font-size: 1rem; }
        }
    </style>
</head>
<body>
    <div class="overlay"></div>

    <section class="container py-5 position-relative">
        <h1 class="section-title" data-aos="zoom-in" data-aos-duration="1000">Final Payment</h1>
        <div class="row g-4">
            <div class="col-12" data-aos="fade-up">
                <div class="card p-4">
                    <h4 class="text-anchor mb-4">Reservation #<?php echo htmlspecialchars($reservation_id); ?></h4>
                    <div class="checklist">
                        <div class="checklist-item"><i class="fas fa-check"></i> <strong>Total Bill:</strong> IDR <?php echo number_format($total_tagihan, 2, ',', '.'); ?></div>
                        <div class="checklist-item"><i class="fas fa-check"></i> <strong>Deposit Paid (30%):</strong> IDR <?php echo number_format($deposit_amount, 2, ',', '.'); ?></div>
                        <div class="checklist-item"><i class="fas fa-check"></i> <strong>Remaining Balance (70%):</strong> IDR <?php echo number_format($remaining_balance, 2, ',', '.'); ?></div>
                    </div>
                    <?php if ($remaining_balance > 0): ?>
                        <form method="POST" class="mt-4">
                            <input type="hidden" name="id_reservasi" value="<?php echo $reservation_id; ?>">
                            <div class="button-container">
                                <button type="submit" class="btn btn-primary">Pay Now <i class="fas fa-arrow-right ms-2"></i></button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="text-center mt-4">
                            <button class="btn btn-success" disabled>Payment Successful</button>
                            <div class="success-check">✔ Payment Completed</div>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($error_message)): ?>
                        <div class="error-message">Error: <?php echo htmlspecialchars($error_message); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script>
        AOS.init({ duration: 1000, once: true });
    </script>
</body>
</html>