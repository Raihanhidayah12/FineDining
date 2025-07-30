<?php
session_start();
include '../includes/config.php';

// Set time zone to WIB (Asia/Jakarta)
date_default_timezone_set('Asia/Jakarta');

// Ensure the user is a chef
if (!isset($_SESSION['id_user']) || $_SESSION['role'] !== 'chef') {
    header("Location: login.php");
    exit();
}

$user_id = intval($_SESSION['id_user']);

// Handle status updates
if (isset($_POST['update_status']) && isset($_POST['id_pesanan']) && isset($_POST['status'])) {
    $id_pesanan = intval($_POST['id_pesanan']);
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE pesanan SET status_pesanan = ?, tanggal_selesai = NOW() WHERE id_pesanan = ?");
    $stmt->bind_param("si", $status, $id_pesanan);
    if ($stmt->execute()) {
        error_log("Updated status for id_pesanan: $id_pesanan to $status");
    } else {
        error_log("Failed to update status for id_pesanan: $id_pesanan - " . $stmt->error);
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/assets/img/logo.png" type="image/gif" sizes="16x16">
    <title>Kitchen Dashboard || FineDining</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,400;1,700;1,900&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gold: #d4af37;
            --accent-gold: #e6c74a;
            --chef-red: #4a2c2c;
            --chef-white: #f0e6d2;
            --dark-bg: #1a1a2e;
            --light-bg: #16213e;
            --text-light: #ffffff;
            --text-muted: #b0b0b0;
            --text-highlight: #d4af37;
            --shadow: 0 10px 20px rgba(0, 0, 0, 0.5);
            --glass-bg: rgba(255, 255, 255, 0.05);
            --button-bg: #4a2c2c;
            --button-bg-hover: #6b4e4e;
            --error-bg: #8b0000;
            --success-bg: #2e7d32;
            --gold-line: 2px solid #d4af37;
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
            position: relative;
        }

        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"%3E%3Cpath fill="%234a2c2c" fill-opacity="0.1" d="M0,64L48,80C96,96,192,128,288,128C384,128,480,96,576,85.3C672,75,768,85,864,106.7C960,128,1056,160,1152,176C1248,192,1344,192,1392,192L1440,192L1440,320L0,320Z"%3E%3C/path%3E%3C/svg%3E');
            z-index: -1;
            animation: wave 7s infinite ease-in-out;
        }

        @keyframes wave {
            0% { transform: translateY(0); }
            50% { transform: translateY(10px); }
            100% { transform: translateY(0); }
        }

        .chef-hat {
            font-size: 4rem;
            color: var(--chef-white);
            animation: bounce 2s infinite;
            text-align: center;
            margin-bottom: 1rem;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-20px); }
            60% { transform: translateY(-10px); }
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 900;
            color: var(--text-highlight);
            text-shadow: 0 2px 15px rgba(212, 175, 55, 0.3);
            margin-bottom: 1rem;
            padding: 0 1rem;
            text-align: center;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .section-title::after {
            content: '';
            display: block;
            width: 120px;
            height: var(--gold-line);
            background: var(--text-highlight);
            margin: 0.5rem auto 2rem;
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
            animation: slideIn 0.8s ease-out;
        }

        @keyframes slideIn {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(212, 175, 55, 0.2);
            border: 1px solid rgba(212, 175, 55, 0.3);
        }

        .text-anchor { color: var(--text-highlight); font-weight: 700; }

        .checklist {
            background: var(--glass-bg);
            border: 1px solid rgba(212, 175, 55, 0.1);
            border-radius: 10px;
            padding: 15px;
            margin-top: 1rem;
        }
        .checklist-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            padding: 0 0.5rem;
            color: var(--text-light);
            transition: transform 0.3s ease;
        }
        .checklist-item:hover { transform: scale(1.02); }
        .checklist-item i { color: var(--success-bg); margin-right: 10px; }

        .btn-primary {
            background: var(--button-bg);
            border: none;
            color: var(--chef-white);
            border-radius: 10px;
            padding: 5px 15px;
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 1rem;
            transition: all 0.4s ease;
            box-shadow: 0 5px 15px rgba(74, 44, 44, 0.6);
            margin: 5px;
        }

        .btn-primary:hover {
            transform: translateY(-5px);
            background: var(--button-bg-hover);
            box-shadow: 0 10px 25px rgba(107, 78, 78, 0.6);
        }

        .btn-primary:active {
            transform: translateY(0);
            box-shadow: 0 3px 10px rgba(107, 78, 78, 0.3);
        }

        .btn-primary:disabled { background: #666; cursor: not-allowed; box-shadow: none; }

        .btn-success {
            background-color: var(--success-bg);
            border: none;
            color: var(--chef-white);
            border-radius: 10px;
            padding: 5px 15px;
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 1rem;
            transition: all 0.4s ease;
            box-shadow: 0 5px 15px rgba(46, 125, 50, 0.6);
        }

        .btn-success:hover {
            transform: translateY(-5px);
            background-color: #388e3c;
            box-shadow: 0 10px 25px rgba(46, 125, 50, 0.6);
        }

        .btn-success:active {
            transform: translateY(0);
            box-shadow: 0 3px 10px rgba(46, 125, 50, 0.3);
        }

        .btn-success:disabled { background: #666; cursor: not-allowed; box-shadow: none; }

        .container {
            padding-bottom: 6rem;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 0 1rem;
        }

        @media (max-width: 768px) {
            .section-title { font-size: 2rem; }
            .btn-primary, .btn-success { padding: 5px 10px; font-size: 0.9rem; }
            .chef-hat { font-size: 3rem; }
        }

        @media (max-width: 576px) {
            .section-title { font-size: 1.5rem; }
            .btn-primary, .btn-success { padding: 4px 8px; font-size: 0.8rem; }
            .chef-hat { font-size: 2.5rem; }
        }
    </style>
</head>
<body>
    <div class="overlay"></div>
    <div class="chef-hat"><i class="fas fa-hat-cowboy"></i></div>

    <section class="container py-5 position-relative">
        <h1 class="section-title" data-aos="zoom-in" data-aos-duration="1000">Kitchen Dashboard</h1>

        <!-- Sticky Filter Bar -->
        <div class="sticky-top bg-transparent py-2" style="z-index: 1000;">
            <div class="d-flex justify-content-center gap-2 flex-wrap">
                <button class="btn btn-primary filter-btn" data-filter="all">All</button>
                <button class="btn btn-primary filter-btn" data-filter="dipesan">Ordered</button>
                <button class="btn btn-primary filter-btn" data-filter="dimasak">Under Cooking</button>
                <button class="btn btn-primary filter-btn" data-filter="selesai">Done</button>
            </div>
        </div>

        <!-- Ordered Section -->
        <div class="order-section" data-status="dipesan">
            <h2 class="section-title" style="font-size: 2rem; margin-top: 2rem;">Ordered</h2>
            <?php
            $stmt = $conn->prepare("
                SELECT p.*, r.id_reservasi, r.tanggal, r.waktu, m.nama_menu, m.kategori, a.nomor_meja
                FROM pesanan p 
                JOIN reservasi r ON p.id_reservasi = r.id_reservasi 
                JOIN menu m ON p.id_menu = m.id_menu 
                JOIN area a ON p.id_area = a.id_area 
                WHERE p.status_pesanan = 'dipesan'
                ORDER BY r.tanggal ASC, r.waktu ASC
            ");
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                $current_reservation_id = null;
                while ($order = $result->fetch_assoc()) {
                    if ($current_reservation_id != $order['id_reservasi']) {
                        if ($current_reservation_id !== null) {
                            echo '</div></div>';
                        }
                        $current_reservation_id = $order['id_reservasi'];
                        ?>
                        <div class="card p-4" data-aos="slide-up" data-aos-duration="800" data-aos-delay="100">
                            <h4 class="text-anchor mb-4">Reservation #<?php echo htmlspecialchars($order['id_reservasi']); ?></h4>
                            <div class="checklist">
                                <div class="checklist-item"><i class="fas fa-check"></i> Date: <?php echo htmlspecialchars($order['tanggal']); ?></div>
                                <div class="checklist-item"><i class="fas fa-check"></i> Time: <?php echo htmlspecialchars($order['waktu']); ?></div>
                                <div class="checklist-item"><i class="fas fa-check"></i> Table: <?php echo htmlspecialchars($order['nomor_meja']); ?></div>
                            </div>
                            <h5 class="text-anchor mt-4">Order Details</h5>
                            <div class="checklist">
                        <?php
                    }
                    ?>
                    <div class="checklist-item">
                        <i class="fas fa-check"></i> 
                        <?php echo htmlspecialchars($order['nama_menu']); ?> (<?php echo htmlspecialchars($order['kategori']); ?>) 
                        - Qty: <?php echo htmlspecialchars($order['jumlah']); ?>
                        - Status: 
                        <form method="post" style="display:inline; margin-left: 1rem;">
                            <input type="hidden" name="id_pesanan" value="<?php echo $order['id_pesanan']; ?>">
                            <select name="status" onchange="this.form.submit()" class="btn btn-primary">
                                <option value="dipesan" selected>Ordered</option>
                                <option value="dimasak">Under Cooking</option>
                                <option value="selesai">Done</option>
                            </select>
                            <input type="hidden" name="update_status" value="1">
                        </form>
                    </div>
                    <?php
                }
                if ($current_reservation_id !== null) {
                    echo '</div></div>';
                }
            } else {
                ?>
                <div class="card p-4" data-aos="zoom-in" data-aos-duration="800">
                    <p class="text-center">No orders in this status.</p>
                </div>
                <?php
            }
            $stmt->close();
            ?>
        </div>

        <!-- Under Cooking Section -->
        <div class="order-section" data-status="dimasak">
            <h2 class="section-title" style="font-size: 2rem; margin-top: 2rem;">Under Cooking</h2>
            <?php
            $stmt = $conn->prepare("
                SELECT p.*, r.id_reservasi, r.tanggal, r.waktu, m.nama_menu, m.kategori, a.nomor_meja
                FROM pesanan p 
                JOIN reservasi r ON p.id_reservasi = r.id_reservasi 
                JOIN menu m ON p.id_menu = m.id_menu 
                JOIN area a ON p.id_area = a.id_area 
                WHERE p.status_pesanan = 'dimasak'
                ORDER BY r.tanggal ASC, r.waktu ASC
            ");
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                $current_reservation_id = null;
                while ($order = $result->fetch_assoc()) {
                    if ($current_reservation_id != $order['id_reservasi']) {
                        if ($current_reservation_id !== null) {
                            echo '</div></div>';
                        }
                        $current_reservation_id = $order['id_reservasi'];
                        ?>
                        <div class="card p-4" data-aos="slide-up" data-aos-duration="800" data-aos-delay="100">
                            <h4 class="text-anchor mb-4">Reservation #<?php echo htmlspecialchars($order['id_reservasi']); ?></h4>
                            <div class="checklist">
                                <div class="checklist-item"><i class="fas fa-check"></i> Date: <?php echo htmlspecialchars($order['tanggal']); ?></div>
                                <div class="checklist-item"><i class="fas fa-check"></i> Time: <?php echo htmlspecialchars($order['waktu']); ?></div>
                                <div class="checklist-item"><i class="fas fa-check"></i> Table: <?php echo htmlspecialchars($order['nomor_meja']); ?></div>
                            </div>
                            <h5 class="text-anchor mt-4">Order Details</h5>
                            <div class="checklist">
                        <?php
                    }
                    ?>
                    <div class="checklist-item">
                        <i class="fas fa-check"></i> 
                        <?php echo htmlspecialchars($order['nama_menu']); ?> (<?php echo htmlspecialchars($order['kategori']); ?>) 
                        - Qty: <?php echo htmlspecialchars($order['jumlah']); ?>
                        - Status: 
                        <form method="post" style="display:inline; margin-left: 1rem;">
                            <input type="hidden" name="id_pesanan" value="<?php echo $order['id_pesanan']; ?>">
                            <select name="status" onchange="this.form.submit()" class="btn btn-primary">
                                <option value="dipesan">Ordered</option>
                                <option value="dimasak" selected>Under Cooking</option>
                                <option value="selesai">Done</option>
                            </select>
                            <input type="hidden" name="update_status" value="1">
                        </form>
                    </div>
                    <?php
                }
                if ($current_reservation_id !== null) {
                    echo '</div></div>';
                }
            } else {
                ?>
                <div class="card p-4" data-aos="zoom-in" data-aos-duration="800">
                    <p class="text-center">No orders in this status.</p>
                </div>
                <?php
            }
            $stmt->close();
            ?>
        </div>

        <!-- History Section (Collapsible) -->
        <div class="order-section" data-status="selesai">
            <h2 class="section-title" style="font-size: 2rem; margin-top: 2rem;">
                <button class="btn btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#historyCollapse" aria-expanded="false" aria-controls="historyCollapse">
                    Done Orders
                </button>
            </h2>
            <div class="collapse" id="historyCollapse">
                <?php
                $stmt = $conn->prepare("
                    SELECT p.*, r.id_reservasi, r.tanggal, r.waktu, m.nama_menu, m.kategori, a.nomor_meja
                    FROM pesanan p 
                    JOIN reservasi r ON p.id_reservasi = r.id_reservasi 
                    JOIN menu m ON p.id_menu = m.id_menu 
                    JOIN area a ON p.id_area = a.id_area 
                    WHERE p.status_pesanan = 'selesai'
                    ORDER BY r.tanggal DESC, r.waktu DESC
                ");
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result && $result->num_rows > 0) {
                    $current_reservation_id = null;
                    while ($order = $result->fetch_assoc()) {
                        if ($current_reservation_id != $order['id_reservasi']) {
                            if ($current_reservation_id !== null) {
                                echo '</div></div>';
                            }
                            $current_reservation_id = $order['id_reservasi'];
                            ?>
                            <div class="card p-4" data-aos="slide-up" data-aos-duration="800" data-aos-delay="100">
                                <h4 class="text-anchor mb-4">Reservation #<?php echo htmlspecialchars($order['id_reservasi']); ?></h4>
                                <div class="checklist">
                                    <div class="checklist-item"><i class="fas fa-check"></i> Date: <?php echo htmlspecialchars($order['tanggal']); ?></div>
                                    <div class="checklist-item"><i class="fas fa-check"></i> Time: <?php echo htmlspecialchars($order['waktu']); ?></div>
                                    <div class="checklist-item"><i class="fas fa-check"></i> Table: <?php echo htmlspecialchars($order['nomor_meja']); ?></div>
                                </div>
                                <h5 class="text-anchor mt-4">Order Details</h5>
                                <div class="checklist">
                            <?php
                        }
                        ?>
                        <div class="checklist-item">
                            <i class="fas fa-check"></i> 
                            <?php echo htmlspecialchars($order['nama_menu']); ?> (<?php echo htmlspecialchars($order['kategori']); ?>) 
                            - Qty: <?php echo htmlspecialchars($order['jumlah']); ?>
                            - Status: <span class="btn btn-success">Done</span>
                        </div>
                        <?php
                    }
                    if ($current_reservation_id !== null) {
                        echo '</div></div>';
                    }
                } else {
                    ?>
                    <div class="card p-4" data-aos="zoom-in" data-aos-duration="800">
                        <p class="text-center">No completed orders in history.</p>
                    </div>
                    <?php
                }
                $stmt->close();
                ?>
            </div>
        </div>

        <div class="text-center mt-4">
            <a href="../logout.php" class="btn btn-primary">Logout</a>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script>
        AOS.init({ duration: 1000, once: true });

        // Filter functionality
        const filterButtons = document.querySelectorAll('.filter-btn');
        const orderSections = document.querySelectorAll('.order-section');

        filterButtons.forEach(button => {
            button.addEventListener('click', () => {
                const filter = button.getAttribute('data-filter');

                orderSections.forEach(section => {
                    if (filter === 'all' || section.getAttribute('data-status') === filter) {
                        section.style.display = 'block';
                        if (section.getAttribute('data-status') === 'selesai') {
                            const collapse = section.querySelector('.collapse');
                            if (collapse) collapse.classList.add('show');
                        }
                    } else {
                        section.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>
</html>