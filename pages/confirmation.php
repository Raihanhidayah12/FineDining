<?php
session_start();
include '../includes/config.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit();
}

// Server-side validation for GET parameters
$required_params = ['nama_area', 'nomor_meja', 'kapasitas', 'date', 'time'];
$missing_params = [];
foreach ($required_params as $param) {
    if (!isset($_GET[$param]) || empty(trim($_GET[$param]))) {
        $missing_params[] = $param;
    }
}
if (!empty($missing_params)) {
    die("Error: Missing or invalid parameters: " . implode(', ', $missing_params));
}

$base_url = 'http://' . $_SERVER['HTTP_HOST'] . '/';
$fallback_image = '/img/default-menu.jpg';
if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $fallback_image)) {
    $fallback_image = 'https://via.placeholder.com/80x80.png?text=Image+Not+Found';
} else {
    $fallback_image = $base_url . $fallback_image;
}

// Initialize variables with default empty string to avoid null
$nama_area = isset($_GET['nama_area']) ? trim($_GET['nama_area']) : '';
$nomor_meja = isset($_GET['nomor_meja']) ? trim($_GET['nomor_meja']) : '';
$kapasitas = isset($_GET['kapasitas']) ? trim($_GET['kapasitas']) : '';
$date = isset($_GET['date']) ? trim($_GET['date']) : '';
$time = isset($_GET['time']) ? trim($_GET['time']) : '';
$food = isset($_GET['food']) && !empty(trim($_GET['food'])) ? explode(',', trim($_GET['food'])) : [];
$food_qty = isset($_GET['food_qty']) && !empty(trim($_GET['food_qty'])) ? explode(',', trim($_GET['food_qty'])) : array_fill(0, count($food), 1);
$drinks = isset($_GET['drinks']) && !empty(trim($_GET['drinks'])) ? explode(',', trim($_GET['drinks'])) : [];
$drinks_qty = isset($_GET['drinks_qty']) && !empty(trim($_GET['drinks_qty'])) ? explode(',', trim($_GET['drinks_qty'])) : array_fill(0, count($drinks), 1);
$dessert = isset($_GET['dessert']) && !empty(trim($_GET['dessert'])) ? explode(',', trim($_GET['dessert'])) : [];
$dessert_qty = isset($_GET['dessert_qty']) && !empty(trim($_GET['dessert_qty'])) ? explode(',', trim($_GET['dessert_qty'])) : array_fill(0, count($dessert), 1);
$decorationTheme = isset($_GET['decorationTheme']) ? trim($_GET['decorationTheme']) : '';
$specialRequests = isset($_GET['specialRequests']) ? trim($_GET['specialRequests']) : '';

// Apply htmlspecialchars only after ensuring non-null values
$nama_area = htmlspecialchars(urldecode($nama_area));
$nomor_meja = htmlspecialchars($nomor_meja);
$kapasitas = htmlspecialchars($kapasitas);
$date = htmlspecialchars($date);
$time = htmlspecialchars($time);
$decorationTheme = htmlspecialchars($decorationTheme);
$specialRequests = htmlspecialchars($specialRequests);

// Debugging: Show received GET parameters
echo "<!-- Debug: Received GET food: " . htmlspecialchars(implode(',', $food)) . " -->\n";

$areas = [];
if ($conn) {
    $nama_area_escaped = $conn->real_escape_string($nama_area);
    $result = $conn->query("SELECT nama_area, gambar_area FROM area WHERE tersedia = 1 AND nama_area = '$nama_area_escaped'");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $gambar_area = !empty($row['gambar_area']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $row['gambar_area']) ? $base_url . $row['gambar_area'] : $fallback_image;
        $areas[$nama_area] = $gambar_area;
    } else {
        die("Error: Invalid or unavailable seating area.");
    }
} else {
    die("Database connection failed.");
}

// Initialize arrays to store menu details and errors
$food_details = [];
$drinks_details = [];
$dessert_details = [];
$total_price = 0;
$errors = [];

// Aggregate quantities for duplicate items
$food_quantities = [];
$drinks_quantities = [];
$dessert_quantities = [];

if ($conn) {
    $table_check = $conn->query("SHOW TABLES LIKE 'menu'");
    if ($table_check->num_rows == 0) {
        $errors[] = "Menu table does not exist in the database.";
    } else {
        $columns_check = $conn->query("SHOW COLUMNS FROM menu LIKE 'nama_menu'");
        if ($columns_check->num_rows == 0) $errors[] = "Column 'nama_menu' not found in menu table.";
        $columns_check = $conn->query("SHOW COLUMNS FROM menu LIKE 'harga'");
        if ($columns_check->num_rows == 0) $errors[] = "Column 'harga' not found in menu table.";
        $columns_check = $conn->query("SHOW COLUMNS FROM menu LIKE 'kategori'");
        if ($columns_check->num_rows == 0) $errors[] = "Column 'kategori' not found in menu table.";
        $columns_check = $conn->query("SHOW COLUMNS FROM menu LIKE 'tersedia'");
        if ($columns_check->num_rows == 0) $errors[] = "Column 'tersedia' not found in menu table.";
        $columns_check = $conn->query("SHOW COLUMNS FROM menu LIKE 'stok'");
        if ($columns_check->num_rows == 0) $errors[] = "Column 'stok' not found in menu table.";

        if (empty($errors)) {
            // Aggregate food quantities
            for ($i = 0; $i < count($food); $i++) {
                $item = trim($food[$i]);
                if (!empty($item)) {
                    $qty = isset($food_qty[$i]) ? max(1, intval($food_qty[$i])) : 1;
                    $food_quantities[$item] = ($food_quantities[$item] ?? 0) + $qty;
                }
            }

            // Fetch and validate food items
            if (!empty($food_quantities)) {
                $food_list = "'" . implode("','", array_map([$conn, 'real_escape_string'], array_keys($food_quantities))) . "'";
                $result = $conn->query("SELECT nama_menu, harga, stok FROM menu WHERE kategori = 'makanan' AND nama_menu IN ($food_list) AND tersedia = 1");
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $food_details[$row['nama_menu']] = ['harga' => $row['harga'], 'stok' => $row['stok']];
                    }
                    foreach ($food_quantities as $item => $qty) {
                        if (isset($food_details[$item])) {
                            if ($qty <= $food_details[$item]['stok']) {
                                $total_price += $food_details[$item]['harga'] * $qty;
                            } else {
                                $errors[] = "Food item '$item' has insufficient stock. Requested: $qty, Available: " . $food_details[$item]['stok'];
                                $food_quantities[$item] = min($qty, $food_details[$item]['stok']); // Adjust to max available
                            }
                        } else {
                            $errors[] = "Food item '$item' not found or unavailable in menu.";
                        }
                    }
                } else {
                    $errors[] = "Error fetching food prices: " . $conn->error;
                }
            }

            // Aggregate drinks quantities
            for ($i = 0; $i < count($drinks); $i++) {
                $item = trim($drinks[$i]);
                if (!empty($item)) {
                    $qty = isset($drinks_qty[$i]) ? max(1, intval($drinks_qty[$i])) : 1;
                    $drinks_quantities[$item] = ($drinks_quantities[$item] ?? 0) + $qty;
                }
            }

            // Fetch and validate drinks items
            if (!empty($drinks_quantities)) {
                $drinks_list = "'" . implode("','", array_map([$conn, 'real_escape_string'], array_keys($drinks_quantities))) . "'";
                $result = $conn->query("SELECT nama_menu, harga, stok FROM menu WHERE kategori = 'minuman' AND nama_menu IN ($drinks_list) AND tersedia = 1");
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $drinks_details[$row['nama_menu']] = ['harga' => $row['harga'], 'stok' => $row['stok']];
                    }
                    foreach ($drinks_quantities as $item => $qty) {
                        if (isset($drinks_details[$item])) {
                            if ($qty <= $drinks_details[$item]['stok']) {
                                $total_price += $drinks_details[$item]['harga'] * $qty;
                            } else {
                                $errors[] = "Drink item '$item' has insufficient stock. Requested: $qty, Available: " . $drinks_details[$item]['stok'];
                                $drinks_quantities[$item] = min($qty, $drinks_details[$item]['stok']); // Adjust to max available
                            }
                        } else {
                            $errors[] = "Drink item '$item' not found or unavailable in menu.";
                        }
                    }
                } else {
                    $errors[] = "Error fetching drinks prices: " . $conn->error;
                }
            }

            // Aggregate dessert quantities
            for ($i = 0; $i < count($dessert); $i++) {
                $item = trim($dessert[$i]);
                if (!empty($item)) {
                    $qty = isset($dessert_qty[$i]) ? max(1, intval($dessert_qty[$i])) : 1;
                    $dessert_quantities[$item] = ($dessert_quantities[$item] ?? 0) + $qty;
                }
            }

            // Fetch and validate dessert items
            if (!empty($dessert_quantities)) {
                $dessert_list = "'" . implode("','", array_map([$conn, 'real_escape_string'], array_keys($dessert_quantities))) . "'";
                $result = $conn->query("SELECT nama_menu, harga, stok FROM menu WHERE kategori = 'dessert' AND nama_menu IN ($dessert_list) AND tersedia = 1");
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $dessert_details[$row['nama_menu']] = ['harga' => $row['harga'], 'stok' => $row['stok']];
                    }
                    foreach ($dessert_quantities as $item => $qty) {
                        if (isset($dessert_details[$item])) {
                            if ($qty <= $dessert_details[$item]['stok']) {
                                $total_price += $dessert_details[$item]['harga'] * $qty;
                            } else {
                                $errors[] = "Dessert item '$item' has insufficient stock. Requested: $qty, Available: " . $dessert_details[$item]['stok'];
                                $dessert_quantities[$item] = min($qty, $dessert_details[$item]['stok']); // Adjust to max available
                            }
                        } else {
                            $errors[] = "Dessert item '$item' not found or unavailable in menu.";
                        }
                    }
                } else {
                    $errors[] = "Error fetching dessert prices: " . $conn->error;
                }
            }
        }
    }
}

$deposit_percentage = 0.3;
$deposit_amount = $total_price * $deposit_percentage;
$remaining_balance = $total_price - $deposit_amount;

date_default_timezone_set('Asia/Jakarta');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/assets/img/logo.png" type="image/gif" sizes="16x16">
    <title>Reservation Confirmation || FineDining</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,400;1,700;1,900&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        :root {
            --primary-gold: #d4af37;
            --accent-gold: #e6c74a;
            --dark-bg: #0f172a;
            --light-bg: #1e293b;
            --text-light: #ffffff; /* Ensured white text */
            --text-muted: #d3d4db;
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
            color: var(--text-light); /* White text */
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
        }

        .section-title::after {
            content: '';
            display: block;
            width: 120px;
            height: 4px;
            background: linear-gradient(to right, var(--primary-gold), var(--accent-gold));
            margin: 1.5rem auto;
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
        .checklist-item { display: flex; align-items: center; margin-bottom: 10px; padding: 0 0.5rem; color: var(--text-light); } /* White text */
        .checklist-item i { color: var(--success-bg); margin-right: 10px; }

        p strong { color: var(--text-highlight); font-weight: 600; text-shadow: 0 1px 5px rgba(255, 204, 0, 0.2); }
        p { color: var(--text-light); /* White text */ font-size: 1.1rem; line-height: 1.6; transition: color 0.3s ease; margin: 0.5rem 0; padding: 0 0.5rem; }
        p:hover { color: var(--accent-gold); }

        .btn-primary, .btn-back, .btn-download {
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

        .btn-primary:hover, .btn-back:hover, .btn-download:hover {
            transform: translateY(-5px);
            background: linear-gradient(to bottom, var(--button-bg-hover), var(--button-bg));
            box-shadow: 0 10px 25px rgba(212, 175, 55, 0.6);
        }

        .btn-primary:active, .btn-back:active, .btn-download:active {
            transform: translateY(0);
            box-shadow: 0 3px 10px rgba(212, 175, 55, 0.3);
        }

        .btn-primary:disabled { background: #999; cursor: not-allowed; box-shadow: none; }

        .container {
            padding-bottom: 6rem;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 0 1rem;
        }

        .button-container { margin-top: auto; padding-top: 2rem; text-align: center; }

        .custom-alert {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid var(--primary-gold);
            border-radius: 15px;
            padding: 20px 30px;
            text-align: center;
            color: var(--text-light); /* White text */
            font-family: 'Playfair Display', serif;
            font-size: 1.2rem;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.5);
            z-index: 1000;
            display: none;
            max-width: 90%;
        }

        .loading-spinner {
            display: none;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid var(--primary-gold);
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        .terms-checkbox { margin: 1.5rem 0; font-size: 1rem; color: var(--text-light); /* White text */ padding: 0 0.5rem; }
        .terms-checkbox input { margin-right: 10px; }
        .terms-checkbox a { color: var(--text-highlight); text-decoration: none; }
        .terms-checkbox a:hover { text-decoration: underline; }

        .countdown-timer { font-size: 1rem; color: var(--text-highlight); margin-bottom: 1rem; text-align: center; padding: 0 0.5rem; }

        .quantity-controls { display: flex; align-items: center; gap: 10px; }
        .quantity-btn { 
            background: var(--button-bg); 
            color: white; 
            border: none; 
            padding: 5px 10px; 
            border-radius: 5px; 
            cursor: pointer; 
            transition: background 0.3s ease;
        }
        .quantity-btn:hover { background: var(--button-bg-hover); }
        .quantity-btn:disabled { 
            background: #999; 
            cursor: not-allowed; 
        }
        .quantity-input { 
            width: 60px; 
            text-align: center; 
            background: var(--glass-bg); 
            color: var(--text-light); /* White text */
            border: 1px solid rgba(212, 175, 55, 0.3); 
            border-radius: 5px; 
            padding: 5px;
        }

        @media (max-width: 768px) {
            .section-title { font-size: 2rem; }
            .btn-primary, .btn-back, .btn-download { padding: 10px 25px; font-size: 1.2rem; }
            .card-img-top { max-width: 75% !important; }
            .quantity-input { width: 50px; }
        }

        @media (max-width: 576px) {
            .section-title { font-size: 1.5rem; }
            .btn-primary, .btn-back, .btn-download { padding: 8px 15px; font-size: 1rem; }
            .custom-alert { padding: 10px 15px; font-size: 0.9rem; max-width: 70%; }
            .card-img-top { max-width: 100% !important; }
            .quantity-controls { gap: 5px; }
            .quantity-btn { padding: 3px 8px; }
            .quantity-input { width: 40px; padding: 3px; }
        }
    </style>
</head>
<body>
    <div class="overlay"></div>

    <section class="container py-5 position-relative">
        <h1 class="section-title text-center">Reservation Confirmation</h1>
        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <div class="countdown-timer" id="countdownTimer">Please confirm your reservation within <span id="timer">5:00</span> minutes.</div>
        <div class="row g-4">
            <div class="col-12" data-aos="fade-up">
                <div class="card p-4" id="pdfContent">
                    <h4 class="text-anchor mb-4">Reservation Details</h4>
                    <div class="checklist">
                        <div class="checklist-item"><i class="fas fa-check"></i> Seating Area: <?php echo $nama_area ?: 'Not selected'; ?></div>
                        <div class="checklist-item"><i class="fas fa-check"></i> Table Number: <?php echo $nomor_meja ?: 'Not selected'; ?></div>
                        <div class="checklist-item"><i class="fas fa-check"></i> Capacity: <?php echo $kapasitas ?: 'Not selected'; ?> persons</div>
                        <div class="checklist-item"><i class="fas fa-check"></i> Date: <?php echo $date ?: 'Not selected'; ?></div>
                        <div class="checklist-item"><i class="fas fa-check"></i> Time: <?php echo $time ?: 'Not selected'; ?></div>
                    </div>
                    <h5 class="text-anchor mt-4">Pre-order Menu</h5>
                    <div class="checklist">
                        <div class="checklist-item"><i class="fas fa-check"></i> <strong>Food:</strong></div>
                        <?php if (!empty($food_quantities)): ?>
                            <?php foreach ($food_quantities as $item => $qty): ?>
                                <div class="checklist-item">
                                    <span style="flex: 1;"><?php echo htmlspecialchars($item); ?> (IDR <span class="item-price" data-category="food" data-item="<?php echo htmlspecialchars($item); ?>"><?php echo isset($food_details[$item]) ? number_format($food_details[$item]['harga'] * $qty, 0) : 'N/A'; ?></span>)</span>
                                    <div class="quantity-controls">
                                        <button class="quantity-btn" onclick="updateQuantity('food', '<?php echo htmlspecialchars($item); ?>', -1)" data-stock="<?php echo isset($food_details[$item]) ? $food_details[$item]['stok'] : 0; ?>">-</button>
                                        <input type="number" class="quantity-input" id="food-<?php echo htmlspecialchars($item); ?>" value="<?php echo $qty; ?>" min="1" data-stock="<?php echo isset($food_details[$item]) ? $food_details[$item]['stok'] : 0; ?>" onchange="updateQuantity('food', '<?php echo htmlspecialchars($item); ?>', this.value)">
                                        <button class="quantity-btn" onclick="updateQuantity('food', '<?php echo htmlspecialchars($item); ?>', 1)" data-stock="<?php echo isset($food_details[$item]) ? $food_details[$item]['stok'] : 0; ?>">+</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="checklist-item">None selected</div>
                        <?php endif; ?>
                        <div class="checklist-item"><i class="fas fa-check"></i> <strong>Drink:</strong></div>
                        <?php if (!empty($drinks_quantities)): ?>
                            <?php foreach ($drinks_quantities as $item => $qty): ?>
                                <div class="checklist-item">
                                    <span style="flex: 1;"><?php echo htmlspecialchars($item); ?> (IDR <span class="item-price" data-category="drinks" data-item="<?php echo htmlspecialchars($item); ?>"><?php echo isset($drinks_details[$item]) ? number_format($drinks_details[$item]['harga'] * $qty, 0) : 'N/A'; ?></span>)</span>
                                    <div class="quantity-controls">
                                        <button class="quantity-btn" onclick="updateQuantity('drinks', '<?php echo htmlspecialchars($item); ?>', -1)" data-stock="<?php echo isset($drinks_details[$item]) ? $drinks_details[$item]['stok'] : 0; ?>">-</button>
                                        <input type="number" class="quantity-input" id="drinks-<?php echo htmlspecialchars($item); ?>" value="<?php echo $qty; ?>" min="1" data-stock="<?php echo isset($drinks_details[$item]) ? $drinks_details[$item]['stok'] : 0; ?>" onchange="updateQuantity('drinks', '<?php echo htmlspecialchars($item); ?>', this.value)">
                                        <button class="quantity-btn" onclick="updateQuantity('drinks', '<?php echo htmlspecialchars($item); ?>', 1)" data-stock="<?php echo isset($drinks_details[$item]) ? $drinks_details[$item]['stok'] : 0; ?>">+</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="checklist-item">None selected</div>
                        <?php endif; ?>
                        <div class="checklist-item"><i class="fas fa-check"></i> <strong>Dessert:</strong></div>
                        <?php if (!empty($dessert_quantities)): ?>
                            <?php foreach ($dessert_quantities as $item => $qty): ?>
                                <div class="checklist-item">
                                    <span style="flex: 1;"><?php echo htmlspecialchars($item); ?> (IDR <span class="item-price" data-category="dessert" data-item="<?php echo htmlspecialchars($item); ?>"><?php echo isset($dessert_details[$item]) ? number_format($dessert_details[$item]['harga'] * $qty, 0) : 'N/A'; ?></span>)</span>
                                    <div class="quantity-controls">
                                        <button class="quantity-btn" onclick="updateQuantity('dessert', '<?php echo htmlspecialchars($item); ?>', -1)" data-stock="<?php echo isset($dessert_details[$item]) ? $dessert_details[$item]['stok'] : 0; ?>">-</button>
                                        <input type="number" class="quantity-input" id="dessert-<?php echo htmlspecialchars($item); ?>" value="<?php echo $qty; ?>" min="1" data-stock="<?php echo isset($dessert_details[$item]) ? $dessert_details[$item]['stok'] : 0; ?>" onchange="updateQuantity('dessert', '<?php echo htmlspecialchars($item); ?>', this.value)">
                                        <button class="quantity-btn" onclick="updateQuantity('dessert', '<?php echo htmlspecialchars($item); ?>', 1)" data-stock="<?php echo isset($dessert_details[$item]) ? $dessert_details[$item]['stok'] : 0; ?>">+</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="checklist-item">None selected</div>
                        <?php endif; ?>
                    </div>
                    <h5 class="text-anchor mt-4">Payment Summary</h5>
                    <p><strong>Total Price:</strong> IDR <span id="totalPrice"><?php echo number_format($total_price, 0); ?></span></p>
                    <p><strong>Deposit (30%):</strong> IDR <span id="depositAmount"><?php echo number_format($deposit_amount, 0); ?></span></p>
                    <p><strong>Remaining Balance:</strong> IDR <span id="remainingBalance"><?php echo number_format($remaining_balance, 0); ?></span></p>
                    <h5 class="text-anchor mt-4">Interior Customization</h5>
                    <p><strong>Decoration Theme:</strong> <?php echo $decorationTheme ?: 'Not selected'; ?></p>
                    <p><strong>Special Requests:</strong> <?php echo $specialRequests ?: 'None'; ?></p>
                    <h5 class="text-anchor mt-4">Cancellation Policy</h5>
                    <p>Cancellations made at least 48 hours in advance will receive a full deposit refund. Cancellations within 48 hours will forfeit the deposit.</p>
                    <div class="mt-4 text-center">
                        <img src="<?php echo isset($areas[$nama_area]) ? htmlspecialchars($areas[$nama_area]) : $fallback_image; ?>" class="card-img-top" alt="Interior Preview" style="max-width: 50%; border-radius: 5px;" aria-label="Interior preview of selected seating area">
                    </div>
                    <div class="terms-checkbox">
                        <input type="checkbox" id="terms" required aria-label="Agree to terms and conditions">
                        <label for="terms">I agree to the <a href="/terms" target="_blank">terms and conditions</a> and cancellation policy.</label>
                    </div>
                    <p class="mt-3"><a href="mailto:support@finedining.com" style="color: var(--text-highlight);">Contact Support</a> if you have any issues.</p>
                </div>
            </div>

            <div class="col-12 button-container" data-aos="fade-up">
                <button class="btn btn-back" onclick="goBack()" aria-label="Go back to previous page">
                    <i class="fas fa-arrow-left me-2"></i> Back
                </button>
                <button class="btn btn-download" onclick="downloadSummary()" aria-label="Download reservation summary">
                    <i class="fas fa-download me-2"></i> Download
                </button>
                <button class="btn btn-primary" id="confirmBtn" onclick="confirmReservation()" disabled aria-label="Confirm reservation">
                    Confirm <i class="fas fa-check ms-2"></i>
                </button>
                <div class="loading-spinner" id="loadingSpinner"></div>
            </div>
        </div>
    </section>

    <div id="customAlert" class="custom-alert" role="alert">
        <p id="alertMessage">Your reservation has been confirmed! We look forward to serving you.</p>
        <button onclick="closeAlert()" aria-label="Close alert">OK</button>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.4/gsap.min.js"></script>
   <script>
    AOS.init({ duration: 1000, once: true });

    function goBack() { window.history.back(); }

    function downloadSummary() {
        const element = document.getElementById('pdfContent');
        const opt = {
            margin: 0.5,
            filename: 'reservation_summary.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2 },
            jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
        };
        html2pdf().set(opt).from(element).save();
    }

    function showAlert(message) {
        const alert = document.getElementById('customAlert');
        if (alert) {
            document.getElementById('alertMessage').textContent = message;
            alert.style.display = 'block';
            alert.style.animation = 'fadeIn 0.5s ease-in-out';
            setTimeout(() => { closeAlert(); }, 3500);
        }
    }

    function closeAlert() {
        const alert = document.getElementById('customAlert');
        if (alert) {
            gsap.to(alert, { opacity: 0, scale: 0.9, duration: 0.5, ease: 'power2.out', onComplete: () => {
                alert.style.display = 'none';
                alert.style.opacity = 1;
            }});
        }
    }

    function toggleConfirmButton() {
        const termsCheckbox = document.getElementById('terms');
        const confirmBtn = document.getElementById('confirmBtn');
        confirmBtn.disabled = !termsCheckbox.checked;
    }

    document.getElementById('terms').addEventListener('change', function() { toggleConfirmButton(); });

    let timeLeft = 5 * 60;
    function startCountdown() {
        const timerDisplay = document.getElementById('timer');
        const interval = setInterval(() => {
            if (timeLeft <= 0) {
                clearInterval(interval);
                showAlert('Reservation confirmation time expired. Please start over.');
                setTimeout(() => { window.location.href = './reservasi.php'; }, 2000);
                return;
            }
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            timerDisplay.textContent = `${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
            timeLeft--;
        }, 1000);
    }

    startCountdown();

    // Initialize quantities and stock from PHP
    let quantities = {
        food: <?php echo json_encode($food_quantities); ?>,
        drinks: <?php echo json_encode($drinks_quantities); ?>,
        dessert: <?php echo json_encode($dessert_quantities); ?>
    };
    let prices = {
        food: <?php echo json_encode($food_details); ?>,
        drinks: <?php echo json_encode($drinks_details); ?>,
        dessert: <?php echo json_encode($dessert_details); ?>
    };

    function updateQuantity(category, item, change) {
        const input = document.getElementById(`${category}-${item}`);
        const stock = parseInt(input.getAttribute('data-stock')) || 0;
        let newQty = parseInt(input.value) || 1;
        
        if (typeof change === 'number') {
            newQty = newQty + change;
        } else {
            newQty = parseInt(change) || 1;
        }
        
        if (newQty < 1) {
            newQty = 1;
            showAlert(`Quantity for ${item} cannot be less than 1.`);
        } else if (newQty > stock) {
            newQty = stock;
            showAlert(`Quantity for ${item} cannot exceed available stock: ${stock}`);
        }
        
        input.value = newQty;
        quantities[category][item] = newQty;

        // Update price display
        const price = prices[category][item] ? prices[category][item].harga * newQty : 'N/A';
        const parent = input.closest('.checklist-item');
        const priceSpan = parent.querySelector('.item-price');
        priceSpan.textContent = price === 'N/A' ? 'N/A' : Number(price).toLocaleString();

        // Recalculate totals
        let totalPrice = 0;
        for (let cat in quantities) {
            for (let itm in quantities[cat]) {
                const qty = quantities[cat][itm];
                const price = prices[cat][itm] ? prices[cat][itm].harga : 0;
                totalPrice += price * qty;
            }
        }
        const depositPercentage = 0.3;
        const depositAmount = totalPrice * depositPercentage;
        const remainingBalance = totalPrice - depositAmount;

        document.getElementById('totalPrice').textContent = totalPrice.toLocaleString();
        document.getElementById('depositAmount').textContent = depositAmount.toLocaleString();
        document.getElementById('remainingBalance').textContent = remainingBalance.toLocaleString();

        // Disable + button if at stock limit
        const plusBtn = parent.querySelector('.quantity-btn[data-stock]:last-of-type');
        plusBtn.disabled = newQty >= stock;
    }

function confirmReservation() {
    const confirmBtn = document.getElementById('confirmBtn');
    const loadingSpinner = document.getElementById('loadingSpinner');
    confirmBtn.disabled = true;
    loadingSpinner.style.display = 'block';

    // Inisialisasi reservationData di sini
    let reservationData = {
        nama_area: encodeURIComponent('<?php echo $nama_area; ?>'), // Ganti dengan id_area jika ada
        nomor_meja: '<?php echo $nomor_meja; ?>',
        kapasitas: '<?php echo $kapasitas; ?>',
        date: '<?php echo $date; ?>', // Pastikan $date sudah didefinisikan di PHP
        time: '<?php echo $time; ?>', // Pastikan $time sudah didefinisikan di PHP
        food: Object.keys(quantities.food).join(','),
        food_qty: Object.values(quantities.food).join(','),
        drinks: Object.keys(quantities.drinks).join(','),
        drinks_qty: Object.values(quantities.drinks).join(','),
        dessert: Object.keys(quantities.dessert).join(','),
        dessert_qty: Object.values(quantities.dessert).join(','),
        decorationTheme: '<?php echo $decorationTheme; ?>',
        specialRequests: '<?php echo $specialRequests; ?>',
        total_price: document.getElementById('totalPrice').textContent.replace(/,/g, ''),
        deposit_amount: document.getElementById('depositAmount').textContent.replace(/,/g, ''),
        user_id: '<?php echo $_SESSION['id_user']; ?>',
        created_at: '<?php echo date('Y-m-d H:i:s'); ?>'
    };

    // Konversi tanggal dari DD/MM/YYYY ke YYYY-MM-DD
    const dateParts = reservationData.date.split('/');
    const formattedDate = `${dateParts[2]}-${dateParts[1]}-${dateParts[0]}`;
    const timeParts = reservationData.time.match(/(\d{2}):(\d{2})\s?(AM|PM)/i);
    let hours = parseInt(timeParts[1]);
    if (timeParts[3].toUpperCase() === 'PM' && hours !== 12) hours += 12;
    else if (timeParts[3].toUpperCase() === 'AM' && hours === 12) hours = 0;
    const formattedTime = `${hours.toString().padStart(2, '0')}:${timeParts[2]}:00`;

    // Perbarui reservationData dengan format yang sesuai
    reservationData.date = formattedDate;
    reservationData.time = formattedTime;

    console.log('Sending data to save_reservation.php: ', reservationData);
    fetch('./save_reservation.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(reservationData).toString(),
        signal: AbortSignal.timeout(10000) // Timeout setelah 10 detik
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status} - ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        loadingSpinner.style.display = 'none';
        if (data.success) {
            showAlert('Your reservation has been confirmed! Redirecting to My Orders...');
            setTimeout(() => { window.location.href = './myorder.php'; }, 2000);
        } else {
            showAlert('Error: ' + (data.message || 'Unknown error from server'));
            confirmBtn.disabled = false;
        }
    })
    .catch(error => {
        loadingSpinner.style.display = 'none';
        showAlert('An error occurred while confirming your reservation. Check console for details.');
        console.error('Fetch error details: ', error);
        confirmBtn.disabled = false;
    });
}
    
</script>
</body>
</html>