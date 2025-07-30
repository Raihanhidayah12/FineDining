<?php
session_start();
include '../includes/config.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Initialize processed_reservations array if not set
if (!isset($_SESSION['processed_reservations'])) {
    $_SESSION['processed_reservations'] = [];
}

// Check if user is logged in and reservation ID is provided
if (!isset($_SESSION['id_user']) || !isset($_GET['id_reservasi'])) {
    header("Location: login.php");
    exit();
}

$user_id = intval($_SESSION['id_user']);
$reservation_id = intval($_GET['id_reservasi']);

// Fetch reservation details
$stmt = $conn->prepare("SELECT * FROM reservasi WHERE id_reservasi = ? AND id_user = ?");
$stmt->bind_param("ii", $reservation_id, $user_id);
$stmt->execute();
$reservation = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$reservation) {
    die("Reservasi tidak ditemukan atau tidak berhak.");
}

// Fetch area name
$stmt = $conn->prepare("SELECT nama_area FROM area WHERE id_area = ?");
$stmt->bind_param("i", $reservation['id_area']);
$stmt->execute();
$nama_area = $stmt->get_result()->fetch_assoc()['nama_area'] ?? 'Not specified';
$stmt->close();

$nomor_meja = $reservation['lokasi_meja'];
$kapasitas = $reservation['jumlah_orang'];
$date = $reservation['tanggal'];
$time = $reservation['waktu'];

// Fetch order details
$stmt = $conn->prepare("SELECT p.id_menu, p.jumlah, m.nama_menu, m.kategori, m.harga 
                        FROM pesanan p 
                        JOIN menu m ON p.id_menu = m.id_menu 
                        WHERE p.id_reservasi = ?");
$stmt->bind_param("i", $reservation_id);
$stmt->execute();
$orders = $stmt->get_result();
$total_price = 0;
$order_details = [];
if ($orders->num_rows > 0) {
    while ($order = $orders->fetch_assoc()) {
        $total_price += $order['harga'] * $order['jumlah'];
        $order_details[] = [
            'nama_menu' => $order['nama_menu'],
            'kategori' => $order['kategori'],
            'jumlah' => $order['jumlah']
        ];
    }
} else {
    $order_details = [];
}
$stmt->close();

$deposit_percentage = 0.3;
$deposit_amount = $total_price * $deposit_percentage;
$remaining_balance = $total_price - $deposit_amount;

// Fetch existing payment details
$payment_method = 'Tunai'; // Default
$status_payment = 'Pending Payment'; // Default
$stmt = $conn->prepare("SELECT metode_bayar, status_payment FROM pembayaran WHERE id_reservasi = ?");
$stmt->bind_param("i", $reservation_id);
$stmt->execute();
$pembayaran = $stmt->get_result()->fetch_assoc();
if ($pembayaran) {
    $payment_method = $pembayaran['metode_bayar'] ?? 'Tunai';
    $status_payment = $pembayaran['status_payment'] ?? 'Pending Payment';
}
$stmt->close();

// Define payment methods
$payment_methods = [
    'Tunai' => 'Cash (In-Person Only)',
    'Transfer' => 'Bank Transfer (Anywhere)',
    'QRIS' => 'QRIS (Anywhere)'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_SERVER['HTTP_X_UPDATE_STATUS'])) {
    $payment_type = $_POST['payment_type'] ?? 'deposit';
    $payment_method = $_POST['payment_method'] ?? 'Tunai';
    $amount_paid = ($payment_type === 'full') ? $total_price : $deposit_amount;
    $initial_status = ($payment_method === 'Tunai') ? 'Pending Payment' : 
                     ($payment_type === 'full' ? 'Fully Paid' : 'Deposit Paid');

    $conn->begin_transaction();

    try {
        // Check if payment exists
        $stmt_check = $conn->prepare("SELECT id_pembayaran, status_payment FROM pembayaran WHERE id_reservasi = ?");
        $stmt_check->bind_param("i", $reservation_id);
        $stmt_check->execute();
        $existing_payment = $stmt_check->get_result()->fetch_assoc();
        $stmt_check->close();

        if ($existing_payment) {
            // Update existing payment
            $stmt_update = $conn->prepare("UPDATE pembayaran SET total_tagihan = ?, metode_bayar = ?, status_payment = ?, tanggal_bayar = ?, jumlah_dibayar = ? WHERE id_reservasi = ?");
            $tanggal_bayar = date('Y-m-d H:i:s');
            $stmt_update->bind_param("dssssi", $total_price, $payment_method, $initial_status, $tanggal_bayar, $amount_paid, $reservation_id);
            if (!$stmt_update->execute()) {
                throw new Exception("Gagal memperbarui pembayaran: " . $stmt_update->error);
            }
            $stmt_update->close();
        } else {
            // Insert new payment
            $stmt_payment = $conn->prepare("INSERT INTO pembayaran (id_reservasi, id_kasir, total_tagihan, metode_bayar, status_payment, tanggal_bayar, jumlah_dibayar) 
                                           VALUES (?, NULL, ?, ?, ?, ?, ?)");
            $tanggal_bayar = date('Y-m-d H:i:s');
            $stmt_payment->bind_param("idsssd", $reservation_id, $total_price, $payment_method, $initial_status, $tanggal_bayar, $amount_paid);
            if (!$stmt_payment->execute()) {
                throw new Exception("Gagal menyimpan pembayaran: " . $stmt_payment->error);
            }
            $stmt_payment->close();
        }

        // Check if reservation was already processed
        if (isset($_SESSION['processed_reservations'][$reservation_id])) {
            throw new Exception("Reservasi ini sudah diproses sebelumnya.");
        }

        // Update menu stock
        $stmt = $conn->prepare("SELECT p.id_menu, p.jumlah, m.stok 
                               FROM pesanan p 
                               JOIN menu m ON p.id_menu = m.id_menu 
                               WHERE p.id_reservasi = ?");
        $stmt->bind_param("i", $reservation_id);
        $stmt->execute();
        $orders = $stmt->get_result();
        if ($orders->num_rows > 0) {
            while ($order = $orders->fetch_assoc()) {
                $new_stock = $order['stok'] - $order['jumlah'];
                if ($new_stock < 0) throw new Exception("Stok tidak mencukupi untuk menu ID " . $order['id_menu']);
                $stmt_stock = $conn->prepare("UPDATE menu SET stok = ? WHERE id_menu = ?");
                $stmt_stock->bind_param("ii", $new_stock, $order['id_menu']);
                $stmt_stock->execute();
                $stmt_stock->close();
            }
        }
        $stmt->close();

        // Mark area and table as unavailable
        $stmt_area = $conn->prepare("UPDATE area SET tersedia = 0 WHERE id_area = ?");
        $stmt_area->bind_param("i", $reservation['id_area']);
        $stmt_area->execute();
        $stmt_area->close();

        // Update payment status in reservasi table
        $stmt_reservasi = $conn->prepare("UPDATE reservasi SET payment_status = ?, tanggal_bayar = ? WHERE id_reservasi = ?");
        $tanggal_bayar = date('Y-m-d H:i:s');
        $stmt_reservasi->bind_param("ssi", $initial_status, $tanggal_bayar, $reservation_id);
        $stmt_reservasi->execute();
        $stmt_reservasi->close();

        // Mark reservation as processed
        $_SESSION['processed_reservations'][$reservation_id] = true;

        $conn->commit();

        // Redirect based on payment method
        if ($payment_method === 'Tunai') {
            echo "<script>window.location.href = 'myorder.php?rid=$reservation_id&method=Tunai';</script>";
        } else {
            echo "<script>alert('Pembayaran berhasil!'); window.location.href = 'myorder.php?success=1&rid=$reservation_id&status=" . urlencode($initial_status) . "';</script>";
        }
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        echo "Error: " . htmlspecialchars($e->getMessage());
        exit();
    }
} elseif (isset($_SERVER['HTTP_X_UPDATE_STATUS']) && $_SERVER['HTTP_X_UPDATE_STATUS'] === 'true') {
    header('Content-Type: application/json');
    $payment_type = $_POST['payment_type'] ?? 'deposit';
    $payment_method = $_POST['payment_method'] ?? 'Tunai';
    $final_status = ($payment_type === 'full') ? 'Fully Paid' : 'Deposit Paid';

    $conn->begin_transaction();
    try {
        $stmt_update = $conn->prepare("UPDATE pembayaran SET status_payment = ? WHERE id_reservasi = ?");
        $stmt_update->bind_param("si", $final_status, $reservation_id);
        $stmt_update->execute();
        $stmt_update->close();

        $stmt_reservasi_update = $conn->prepare("UPDATE reservasi SET payment_status = ? WHERE id_reservasi = ?");
        $stmt_reservasi_update->bind_param("si", $final_status, $reservation_id);
        $stmt_reservasi_update->execute();
        $stmt_reservasi_update->close();

        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/assets/img/logo.png" type="image/gif" sizes="16x16">
    <title>Payment || FineDining</title>
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
        --text-light: #ffffff; /* Ensure this is white */
        --text-muted: #ffffff; /* Change to white instead of light gray */
        --text-highlight: #ffcc00;
        --shadow: 0 10px 20px rgba(0, 0, 0, 0.5);
        --glass-bg: rgba(255, 255, 255, 0.1);
        --button-bg: #ff8c00;
        --button-bg-hover: #ffa500;
        --error-bg: #dc3545;
        --success-bg: #28a745;
    }

        html, body { margin: 0; padding: 0; overflow-x: hidden !important; }
        * { box-sizing: border-box; }
        body::-webkit-scrollbar { display: none; }

body {
        font-family: 'Roboto', sans-serif;
        background: linear-gradient(to bottom, var(--dark-bg) 0%, var(--light-bg) 70%);
        color: var(--text-light); /* Explicitly set to white */
        overflow-y: auto;
        min-height: 100vh;
    }

    p {
        color: var(--text-light); /* Explicitly set to white */
        font-size: 1.1rem;
        line-height: 1.6;
        transition: color 0.3s ease;
        margin: 0.5rem 0;
        padding: 0 0.5rem;
    }

    .checklist-item {
        color: var(--text-light); /* Explicitly set to white */
        display: flex;
        align-items: center;
        margin-bottom: 10px;
        padding: 0 0.5rem;
    }

    /* Ensure labels and other text elements are white */
    label {
        color: var(--text-light); /* Explicitly set to white */
    }

    /* Ensure text-anchor class uses white or adjusts as needed */
    .text-anchor {
        color: var(--text-highlight); /* Keep highlight color but ensure readability */
        font-weight: 700;
    }

    /* Additional adjustments for other text elements */
    h4, h5, h6 {
        color: var(--text-light); /* Ensure headings are white */
    }

    /* Override any specific styles that might set different colors */
    .payment-option label {
        color: var(--text-light); /* Ensure payment option labels are white */
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
            padding: 2rem;
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
            padding: 12px 30px;
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 1.3rem;
            transition: all 0.4s ease;
            box-shadow: 0 5px 15px rgba(212, 175, 55, 0.6);
            margin: 0 10px;
            position: relative;
            overflow: hidden;
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

        .payment-options {
            background: var(--glass-bg);
            border: 1px solid rgba(212, 175, 55, 0.1);
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 1rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .payment-option {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 5px;
            transition: background 0.3s ease;
        }

        .payment-option:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .payment-option input[type="radio"] {
            margin-right: 1rem;
        }

        .container {
            padding-bottom: 6rem;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 0 1rem;
        }

        .button-container { margin-top: auto; padding-top: 2rem; text-align: center; position: relative; }

        .loader {
            display: none;
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--primary-gold);
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            margin-left: 10px;
        }

        @keyframes spin {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }

        .success-check {
            display: none;
            color: var(--success-bg);
            font-size: 2rem;
            margin-top: 1rem;
            text-align: center;
        }

        @media (max-width: 768px) {
            .section-title { font-size: 2rem; }
            .btn-primary { padding: 10px 25px; font-size: 1.2rem; }
            .payment-options { padding: 1rem; }
        }

        @media (max-width: 576px) {
            .section-title { font-size: 1.5rem; }
            .btn-primary { padding: 8px 15px; font-size: 1rem; }
            .payment-option { flex-direction: column; align-items: flex-start; gap: 0.5rem; }
        }
    </style>
</head>
<body>
    <div class="overlay"></div>

    <section class="container py-5 position-relative">
        <h1 class="section-title" data-aos="zoom-in" data-aos-duration="1000">Payment</h1>
        <div class="row g-4">
            <div class="col-12" data-aos="fade-up">
                <div class="card p-4">
                    <h4 class="text-anchor mb-4">Reservation Details</h4>
                    <div class="checklist">
                        <div class="checklist-item"><i class="fas fa-check"></i> Seating Area: <?php echo htmlspecialchars($nama_area); ?></div>
                        <div class="checklist-item"><i class="fas fa-check"></i> Table Number: <?php echo htmlspecialchars($nomor_meja); ?></div>
                        <div class="checklist-item"><i class="fas fa-check"></i> Capacity: <?php echo htmlspecialchars($kapasitas); ?> persons</div>
                        <div class="checklist-item"><i class="fas fa-check"></i> Date: <?php echo htmlspecialchars($date); ?></div>
                        <div class="checklist-item"><i class="fas fa-check"></i> Time: <?php echo htmlspecialchars($time); ?></div>
                    </div>
                    <h5 class="text-anchor mt-4">Order Details</h5>
                    <div class="checklist">
                        <?php if (!empty($order_details)): ?>
                            <?php
                            $categories = ['makanan' => 'Food', 'minuman' => 'Drink', 'dessert' => 'Dessert'];
                            $uncategorized = [];
                            foreach ($order_details as $item) {
                                if (!in_array($item['kategori'], array_keys($categories))) {
                                    $uncategorized[] = $item;
                                }
                            }
                            foreach ($categories as $cat_key => $cat_name) {
                                $category_items = array_filter($order_details, function($item) use ($cat_key) {
                                    return strtolower($item['kategori']) === strtolower($cat_key);
                                });
                                if (!empty($category_items)) {
                                    echo "<div class='checklist-item'><i class='fas fa-check'></i> <strong>$cat_name:</strong></div>";
                                    foreach ($category_items as $item) {
                                        echo "<div class='checklist-item'>" . htmlspecialchars($item['nama_menu']) . " - Qty: " . htmlspecialchars($item['jumlah']) . "</div>";
                                    }
                                }
                            }
                            if (!empty($uncategorized)) {
                                echo "<div class='checklist-item'><i class='fas fa-check'></i> <strong>Other:</strong></div>";
                                foreach ($uncategorized as $item) {
                                    echo "<div class='checklist-item'>" . htmlspecialchars($item['nama_menu']) . " - Qty: " . htmlspecialchars($item['jumlah']) . " (Category: " . htmlspecialchars($item['kategori']) . ")</div>";
                                }
                            }
                            ?>
                        <?php else: ?>
                            <div class="checklist-item">No items ordered</div>
                        <?php endif; ?>
                    </div>
                    <h5 class="text-anchor mt-4">Payment Summary</h5>
                    <p><strong>Total Price:</strong> IDR <?php echo number_format($total_price, 0); ?></p>
                    <p><strong>Deposit (30%):</strong> IDR <?php echo number_format($deposit_amount, 0); ?></p>
                    <p><strong>Remaining Balance:</strong> IDR <?php echo number_format($remaining_balance, 0); ?></p>
                    <form method="POST" action="" id="paymentForm">
                        <div class="payment-options">
                            <h6 class="text-anchor mb-3">Payment Type</h6>
                            <div class="payment-option">
                                <input type="radio" name="payment_type" value="deposit" id="deposit" checked>
                                <label for="deposit">Deposit (30%) - IDR <?php echo number_format($deposit_amount, 0); ?></label>
                            </div>
                            <div class="payment-option">
                                <input type="radio" name="payment_type" value="full" id="full">
                                <label for="full">Full Payment - IDR <?php echo number_format($total_price, 0); ?></label>
                            </div>
                            <h6 class="text-anchor mt-3 mb-3">Payment Method</h6>
                            <?php foreach ($payment_methods as $method => $description): ?>
                                <div class="payment-option">
                                    <input type="radio" name="payment_method" value="<?php echo htmlspecialchars($method); ?>" id="<?php echo strtolower(str_replace(' ', '_', $method)); ?>" <?php echo $method === $payment_method ? 'checked' : ''; ?>>
                                    <label for="<?php echo strtolower(str_replace(' ', '_', $method)); ?>"><?php echo htmlspecialchars($method); ?> (<?php echo $description; ?>)</label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="button-container mt-4" data-aos="fade-up">
                            <button type="submit" class="btn btn-primary" id="submitButton">
                                Confirm Payment <i class="fas fa-check ms-2"></i>
                                <span class="loader" id="loader"></span>
                            </button>
                            <div class="success-check" id="successCheck">✔ Payment Successful</div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script>
        AOS.init({ duration: 1000, once: true });

        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const submitButton = document.getElementById('submitButton');
            const loader = document.getElementById('loader');
            const successCheck = document.getElementById('successCheck');
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;

            loader.style.display = 'inline-block';
            submitButton.disabled = true;

            fetch(window.location.href, {
                method: 'POST',
                body: new FormData(this)
            }).then(response => response.text())
              .then(text => {
                  loader.style.display = 'none';
                  if (text.includes('window.location.href')) {
                      // Extract the URL from the script tag
                      const match = text.match(/window\.location\.href = '([^']+)'/);
                      if (match && match[1]) {
                          window.location.href = match[1];
                      }
                  } else if (text.includes('Pembayaran berhasil!')) {
                      successCheck.style.display = 'block';
                      window.location.href = `myorder.php?success=1&rid=<?php echo $reservation_id; ?>&status=<?php echo urlencode($initial_status ?? $reservation['payment_status']); ?>`;
                  } else if (text.startsWith('Error:')) {
                      alert(text.replace('Error: ', ''));
                      submitButton.disabled = false;
                      successCheck.style.display = 'none';
                  } else {
                      alert('Unknown response from server');
                      submitButton.disabled = false;
                      successCheck.style.display = 'none';
                  }
              }).catch(error => {
                  alert('Error: ' + error.message);
                  submitButton.disabled = false;
                  successCheck.style.display = 'none';
              });
        });
    </script>
</body>
</html>