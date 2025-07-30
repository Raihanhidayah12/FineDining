<?php
// Memulai sesi untuk mengelola status pengguna di seluruh halaman
session_start();
// Menyertakan file konfigurasi database
include '../includes/config.php';

// Memeriksa apakah pengguna sudah login; jika belum, arahkan ke halaman login
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit();
}

// Mendefinisikan kategori menu
$categories = ['makanan', 'minuman', 'Dessert'];

// Memeriksa koneksi database; hentikan eksekusi jika gagal
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Membuat URL dasar untuk jalur gambar
$base_url = 'http://' . $_SERVER['HTTP_HOST'] . '/';
$fallback_image = '/img/default-menu.jpg';
// Memeriksa apakah gambar default ada; gunakan placeholder jika tidak ada
if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $fallback_image)) {
    $fallback_image = 'https://via.placeholder.com/80x80.png?text=Image+Not+Found';
} else {
    $fallback_image = $base_url . $fallback_image;
}

// Mengambil semua area tempat duduk dari database
$areas = [];
$result = $conn->query("SELECT nama_area, nomor_meja, deskripsi, kapasitas, tersedia, gambar_area FROM area ORDER BY nama_area, nomor_meja");
// Penjelasan Database: Query ini mengambil data dari tabel 'area' yang berisi informasi tentang nama area, nomor meja, deskripsi, kapasitas, status ketersediaan (tersedia), dan jalur gambar area. Data diurutkan berdasarkan nama_area dan nomor_meja untuk tampilan yang konsisten.
if ($result === false) {
    // Jika query gagal, tetapkan data default dengan pesan error
    $areas = ['Error fetching areas' => ['kapasitas' => [0], 'gambar_area' => $fallback_image, 'nomor_meja' => [0], 'tersedia' => [0]]];
} else {
    if ($result->num_rows === 0) {
        // Jika tidak ada data, tetapkan data default dengan pesan 'tidak ada area'
        $areas = ['No areas available' => ['kapasitas' => [0], 'gambar_area' => $fallback_image, 'nomor_meja' => [0], 'tersedia' => [0]]];
    } else {
        // Mengelompokkan data berdasarkan nama_area
        while ($row = $result->fetch_assoc()) {
            $nama_area = $row['nama_area'];
            // Memeriksa apakah gambar area ada di server; gunakan fallback jika tidak
            $gambar_area = !empty($row['gambar_area']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $row['gambar_area']) ? $base_url . $row['gambar_area'] : $fallback_image;
            if (!isset($areas[$nama_area])) {
                $areas[$nama_area] = [
                    'kapasitas' => [],
                    'gambar_area' => $gambar_area,
                    'nomor_meja' => [],
                    'tersedia' => []
                ];
            }
            $areas[$nama_area]['kapasitas'][] = $row['kapasitas'];
            $areas[$nama_area]['nomor_meja'][] = $row['nomor_meja'];
            $areas[$nama_area]['tersedia'][] = $row['tersedia'];
        }
    }
    // Membebaskan hasil query untuk menghemat memori
    $result->free();
}

// Mengambil parameter nama_area dan nomor_meja dari URL
$nama_area = isset($_GET['nama_area']) ? htmlspecialchars(urldecode($_GET['nama_area'])) : '';
$nomor_meja = isset($_GET['nomor_meja']) ? htmlspecialchars($_GET['nomor_meja']) : '';
$tables = [];

// Jika nama_area ada, ambil data meja untuk area tersebut
if ($nama_area) {
    $stmt = $conn->prepare("SELECT nomor_meja, kapasitas, tersedia FROM area WHERE nama_area = ? ORDER BY nomor_meja");
    // Penjelasan Database: Menggunakan prepared statement untuk mencegah SQL injection. Query ini mengambil nomor_meja, kapasitas, dan status ketersediaan dari tabel 'area' untuk nama_area tertentu, diurutkan berdasarkan nomor_meja.
    $stmt->bind_param("s", $nama_area);
    // Mengikat parameter nama_area ke query
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $tables[] = [
            'nomor_meja' => $row['nomor_meja'],
            'kapasitas' => $row['kapasitas'],
            'tersedia' => $row['tersedia']
        ];
    }
    // Membebaskan hasil dan menutup statement
    $result->free();
    $stmt->close();
}

// Mengambil semua menu (tersedia dan tidak tersedia) untuk pelanggan
$menus = [];
foreach ($categories as $category) {
    $stmt = $conn->prepare("SELECT nama_menu, gambar_menu, tersedia, stok FROM menu WHERE kategori = ? LIMIT 5");
    // Penjelasan Database: Prepared statement ini mengambil data menu (nama, gambar, status ketersediaan, dan stok) dari tabel 'menu' berdasarkan kategori, dengan batas 5 item per kategori untuk efisiensi.
    if ($stmt === false) {
        // Jika prepared statement gagal, tetapkan data default dengan pesan error
        $menus[$category] = [['nama_menu' => 'Error fetching ' . $category, 'gambar_menu' => $fallback_image, 'tersedia' => 0, 'stok' => 0]];
    } else {
        $stmt->bind_param("s", $category);
        // Mengikat parameter kategori ke query
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result === false) {
            // Jika query gagal, tetapkan data default dengan pesan error
            $menus[$category] = [['nama_menu' => 'Error fetching ' . $category, 'gambar_menu' => $fallback_image, 'tersedia' => 0, 'stok' => 0]];
        } else {
            $items = [];
            if ($result->num_rows === 0) {
                // Jika tidak ada data, tetapkan pesan 'tidak ada menu tersedia'
                $items[] = ['nama_menu' => 'No ' . $category . ' available', 'gambar_menu' => $fallback_image, 'tersedia' => 0, 'stok' => 0];
            } else {
                // Mengambil data menu dan memeriksa keberadaan gambar
                while ($row = $result->fetch_assoc()) {
                    $gambar_menu = !empty($row['gambar_menu']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $row['gambar_menu']) ? $base_url . $row['gambar_menu'] : $fallback_image;
                    $items[] = [
                        'nama_menu' => $row['nama_menu'],
                        'gambar_menu' => $gambar_menu,
                        'tersedia' => $row['tersedia'],
                        'stok' => $row['stok']
                    ];
                }
            }
            $menus[$category] = $items;
        }
        // Membebaskan hasil dan menutup statement
        $result->free();
        $stmt->close();
    }
}

// Mengatur zona waktu dan mengambil tanggal serta waktu saat ini
date_default_timezone_set('Asia/Jakarta');
$current_date = new DateTime();
$current_time = (int)$current_date->format('H');
$current_minutes = (int)$current_date->format('i');
$today = $current_date->format('Y-m-d');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Metadata untuk encoding dan responsivitas -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Ikon halaman -->
    <link rel="icon" href="/assets/img/logo.png" type="image/gif" sizes="16x16">
    <title>Make a Reservation || FineDining</title>
    <!-- Mengimpor Bootstrap, AOS, Font Awesome, Google Fonts, dan Pikaday untuk styling dan animasi -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,400;1,700;1,900&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pikaday/css/pikaday.css">
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>

    <style>
        /* Variabel CSS untuk warna dan efek */
        :root {
            --primary-gold: #d4af37;
            --accent-gold: #e6c74a;
            --dark-bg: #0f172a;
            --light-bg: #1e293b;
            --text-light: #f1f5f9;
            --text-muted: #d3d4db;
            --shadow: 0 10px 20px rgba(0, 0, 0, 0.5);
            --glass-bg: rgba(255, 255, 255, 0.05);
            --button-bg: #ff8c00;
            --button-bg-hover: #ffa500;
            --sold-out: #ff4d4d;
        }

        /* Menghilangkan scrollbar default */
        html, body {
            margin: 0;
            padding: 0;
            -ms-overflow-style: none;
            scrollbar-width: none;
            overflow-x: hidden;
            height: auto;
        }

        body::-webkit-scrollbar, html::-webkit-scrollbar {
            display: none;
        }

        /* Styling dasar body dengan gradien dan font */
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(to bottom, var(--dark-bg) 0%, var(--light-bg) 70%);
            color: var(--text-light);
            overflow-y: auto;
            min-height: 100vh;
        }

        /* Overlay untuk efek latar belakang */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.1);
            z-index: -1;
        }

        /* Kontainer partikel untuk animasi latar belakang */
        #particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            opacity: 0.5;
        }

        .particle {
            position: absolute;
            background: radial-gradient(circle, rgba(212, 175, 55, 0.6), rgba(212, 175, 55, 0));
            border-radius: 50%;
            opacity: 0;
            animation: floatParticle 30s infinite ease-in-out;
        }

        @keyframes floatParticle {
            0% { opacity: 0; transform: translateY(100vh) scale(0.5); }
            20% { opacity: 0.4; }
            80% { opacity: 0.4; }
            100% { opacity: 0; transform: translateY(-100vh) scale(1.5); }
        }

        /* Styling judul bagian */
        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 900;
            color: var(--primary-gold);
            text-shadow: 0 2px 15px rgba(212, 175, 55, 0.3);
            margin-bottom: 3rem;
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

        /* Styling dropdown */
        .form-select {
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.9), rgba(30, 41, 59, 0.9));
            border: 2px solid transparent;
            border-radius: 12px;
            color: var(--text-light);
            padding: 1.2rem 1.5rem;
            font-size: 1.1rem;
            font-weight: 500;
            font-family: 'Playfair Display', serif;
            box-shadow: 0 4px 15px rgba(212, 175, 55, 0.1);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23d4af37' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1em;
        }

        .form-select:focus {
            border-color: var(--accent-gold);
            box-shadow: 0 0 20px rgba(212, 175, 55, 0.4), inset 0 0 5px rgba(212, 175, 55, 0.2);
            outline: none;
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.95), rgba(30, 41, 59, 0.95));
        }

        .form-select:disabled {
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.7), rgba(30, 41, 59, 0.7));
            color: #a0a0a0;
            opacity: 0.85;
            cursor: not-allowed;
        }

        .form-select option {
            background: var(--dark-bg);
            color: var(--text-light);
            padding: 0.8rem;
            transition: background 0.3s ease;
        }

        .form-select option:disabled {
            color: var(--sold-out);
            font-style: italic;
            background: rgba(15, 23, 42, 0.9);
            opacity: 0.9;
        }

        .form-select option:hover:not(:disabled) {
            background: rgba(212, 175, 55, 0.1);
        }

        /* Styling label formulir */
        .form-label {
            font-family: 'Playfair Display', serif;
            color: var(--primary-gold);
            font-weight: 700;
            font-size: 1.3rem;
            margin-bottom: 0.5rem;
            text-shadow: 0 1px 5px rgba(212, 175, 55, 0.2);
        }

        /* Styling kartu untuk area tempat duduk dan waktu */
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
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(212, 175, 55, 0.2);
            border: 1px solid rgba(212, 175, 55, 0.3);
        }

        /* Warna teks muted */
        .text-muted { color: var(--text-muted) !important; }

        /* Styling tombol */
        .btn-primary, .btn-back {
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

        .btn-primary:hover, .btn-back:hover {
            transform: translateY(-5px);
            background: linear-gradient(to bottom, var(--button-bg-hover), var(--button-bg));
            box-shadow: 0 10px 25px rgba(212, 175, 55, 0.6);
        }

        .btn-primary:active, .btn-back:active {
            transform: translateY(0);
            box-shadow: 0 3px 10px rgba(212, 175, 55, 0.3);
        }

        /* Styling teks anchor */
        .text-anchor {
            color: var(--primary-gold);
            font-family: 'Playfair Display', serif;
            font-weight: 700;
        }

        /* Styling kontainer utama */
        .container {
            padding-bottom: 6rem;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Styling kontainer tombol */
        .button-container {
            margin-top: auto;
            padding-top: 2rem;
            text-align: center;
        }

        /* Styling input tanggal dan waktu */
        .custom-date-time input {
            background: transparent;
            border: 1px solid var(--primary-gold);
            color: var(--text-light);
            border-radius: 10px;
            padding: 1rem;
            font-size: 1.1rem;
            width: 100%;
            box-sizing: border-box;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .custom-date-time input:focus {
            border-color: var(--accent-gold);
            box-shadow: 0 0 15px rgba(212, 175, 55, 0.3);
            background: rgba(15, 23, 42, 0.6);
        }

        /* Styling Pikaday untuk kalender */
        .pika-single {
            background: var(--glass-bg) !important;
            border: 1px solid rgba(212, 175, 55, 0.1) !important;
            border-radius: 15px;
            box-shadow: var(--shadow);
            backdrop-filter: blur(20px) !important;
        }

        .pika-button {
            background: var(--light-bg) !important;
            color: var(--text-light);
            border-radius: 5px;
        }

        .pika-button:hover {
            background: rgba(212, 175, 55, 0.2) !important;
        }

        .pika-select {
            background: var(--light-bg);
            color: var(--text-light);
        }

        /* Styling daftar menu */
        .menu-list {
            display: flex;
            overflow-x: auto;
            gap: 20px;
            padding: 15px 0;
            width: 100%;
            justify-content: flex-start;
            scroll-behavior: smooth;
        }

        .menu-list::-webkit-scrollbar {
            height: 8px;
        }

        .menu-list::-webkit-scrollbar-track {
            background: rgba(15, 23, 42, 0.5);
            border-radius: 10px;
        }

        .menu-list::-webkit-scrollbar-thumb {
            background: var(--primary-gold);
            border-radius: 10px;
        }

        .menu-list::-webkit-scrollbar-thumb:hover {
            background: var(--accent-gold);
        }

        /* Styling item menu */
        .menu-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            min-width: 120px;
            text-align: center;
            flex-shrink: 0;
            background: rgba(15, 23, 42, 0.6);
            padding: 10px;
            border-radius: 12px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: 1px solid rgba(0,0,0,0.5);
        }

        .menu-item.sold-out {
            opacity: 0.9;
            border-color: var(--sold-out);
            background: rgba(255, 77, 77, 0.1);
        }

        .menu-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(212, 175, 55,0.3);
            border: 1px solid var(--primary-gold);
        }

        .menu-item.sold-out {
            transform: none;
            box-shadow: none;
            border: 1px solid var(--sold-out);
        }

        /* Styling gambar menu */
        .menu-item img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 8px;
            border: 2px solid var(--primary-gold);
            transition: transform 0.3s ease;
        }

        .menu-item.sold-out img {
            border-color: var(--sold-out);
            filter: grayscale(50%);
        }

        .menu-item:hover img {
            transform: scale(1.05);
        }

        .menu-item.sold-out:hover img {
            transform: none;
        }

        /* Styling checkbox menu */
        .menu-item input[type="checkbox"] {
            margin-top: 8px;
            accent-color: var(--primary-gold);
            transform: scale(1.3);
            transition: transform 0.2s ease;
        }

        .menu-item input[type="checkbox"]:disabled {
            accent-color: var(--sold-out);
        }

        .menu-item input[type="checkbox"]:checked {
            transform: scale(1.5);
        }

        /* Styling label menu */
        .menu-item label {
            color: var(--text-light);
            font-size: 1rem;
            font-weight: 500;
            word-wrap: break-word;
            padding: 0 5px;
            max-width: 120px;
            transition: color 0.3s ease;
        }

        .menu-item.sold-out label {
            color: var(--sold-out);
            font-style: italic;
            font-weight: 600;
            text-shadow: 0 0 5px rgba(255, 77, 77, 0.5);
        }

        .menu-item:hover label {
            color: var(--primary-gold);
        }

        .menu-item.sold-out:hover label {
            color: var(--sold-out);
        }

        /* Styling badge sold out */
        .sold-out-badge {
            display: inline-block;
            background: var(--sold-out);
            color: #fff;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 10px;
            margin-left: 5px;
            text-transform: uppercase;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }

        /* Styling judul kategori */
        .category-title {
            font-family: 'Playfair Display', serif;
            color: var(--primary-gold);
            font-weight: 700;
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
            text-align: center;
            background: rgba(15, 23, 42, 0.7);
            padding: 12px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(212, 175, 55, 0.2);
        }

        /* Styling kartu menu */
        .card-menu {
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid rgba(212, 175, 55, 0.3);
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        /* Styling alert kustom */
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
            color: var(--text-light);
            font-family: 'Playfair Display', serif;
            font-size: 1.2rem;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.5);
            z-index: 1000;
            display: none;
            max-width: 90%;
        }

        .custom-alert p {
            margin-bottom: 15px;
        }

        .custom-alert button {
            background: var(--button-bg);
            border: none;
            color: #fff;
            padding: 10px 20px;
            border-radius: 10px;
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            margin: 0 5px;
            transition: background 0.3s ease;
        }

        .custom-alert button:hover {
            background: var(--button-bg-hover);
        }

        /* Animasi untuk alert */
        @keyframes fadeIn {
            from { opacity: 0; transform: translate(-50%, -50%) scale(0.9); }
            to { opacity: 1; transform: translate(-50%, -50%) scale(1); }
        }

        @keyframes fadeOut {
            to { opacity: 0; transform: translate(-50%, -50%) scale(0.9); }
        }

        /* Responsivitas untuk layar kecil */
        @media (max-width: 1024px) {
            .section-title { font-size: 2.2rem; }
            .card-img-top { height: 180px; }
            .btn-primary, .btn-back { padding: 12px 30px; font-size: 1.3rem; }
            .menu-item { min-width: 110px; }
            .menu-item img { width: 70px; height: 70px; }
            .menu-item label { font-size: 0.9rem; }
            .category-title { font-size: 1.6rem; }
        }

        @media (max-width: 768px) {
            .row > div { flex: 0 0 100%; max-width: 100%; }
            .section-title { font-size: 2rem; }
            .card-img-top { height: 150px; }
            .btn-primary, .btn-back { padding: 10px 25px; font-size: 1.2rem; }
            .menu-item { min-width: 100px; }
            .menu-item img { width: 60px; height: 60px; }
            .menu-item label { font-size: 0.8rem; }
            .custom-alert { padding: 15px 20px; font-size: 1rem; max-width: 80%; }
            .category-title { font-size: 1.4rem; }
        }

        @media (max-width: 576px) {
            .section-title { font-size: 1.8rem; }
            .card-img-top { height: 120px; }
            .btn-primary, .btn-back { padding: 8px 20px; font-size: 1rem; }
            .menu-item { min-width: 90px; }
            .menu-item img { width: 50px; height: 50px; }
            .menu-item label { font-size: 0.7rem; }
            .custom-alert { padding: 10px 15px; font-size: 0.9rem; max-width: 70%; }
            .card { padding: 15px; }
            .card-menu { padding: 15px; }
            .category-title { font-size: 1.2rem; padding: 8px; }
        }
    </style>
</head>
<body>
    <!-- Overlay dan partikel untuk efek visual -->
    <div class="overlay"></div>
    <div id="particles"></div>

    <!-- Kontainer utama untuk reservasi -->
    <section class="container py-5 position-relative">
        <h1 class="section-title text-center">Make a Reservation</h1>
        <div class="row g-4">
            <!-- Bagian pemilihan area tempat duduk -->
            <div class="col-12 col-md-6" data-aos="fade-right">
                <h4 class="text-anchor mb-4">Your Seating Area</h4>
                <div class="card p-4">
                    <div class="mb-3">
                        <label class="form-label">Seating Area</label>
                        <select class="form-select" id="seatingSelect" aria-label="Select Seating Area" onchange="updateSeatingImageAndCapacity()">
                            <option value="" style="color: #6c757d;">Select Area</option>
                            <?php foreach ($areas as $nama_area_option => $area): ?>
                                <?php
                                // Memeriksa apakah area tersedia
                                $area_tersedia = in_array(1, $area['tersedia']);
                                $disabled = $area_tersedia ? '' : 'disabled';
                                $label = $area_tersedia ? htmlspecialchars($nama_area_option) : htmlspecialchars($nama_area_option) . ' (Sold Out)';
                                ?>
                                <option value="<?php echo htmlspecialchars($nama_area_option); ?>" data-gambar="<?php echo htmlspecialchars($area['gambar_area']); ?>" <?php echo $disabled; ?> <?php echo ($nama_area_option === $nama_area) ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Table Number</label>
                        <select class="form-select" id="tableSelect" aria-label="Select Table Number" onchange="updateCapacityBasedOnTable()" <?php echo $nama_area ? '' : 'disabled'; ?>>
                            <option value="" style="color: #6c757d;">Select Table</option>
                            <?php if (!empty($tables)): ?>
                                <?php foreach ($tables as $table): ?>
                                    <?php
                                    // Memeriksa apakah meja tersedia
                                    $disabled = $table['tersedia'] ? '' : 'disabled';
                                    $label = $table['tersedia'] ? 'Table ' . htmlspecialchars($table['nomor_meja']) : 'Table ' . htmlspecialchars($table['nomor_meja']) . ' (Sold Out)';
                                    ?>
                                    <option value="<?php echo htmlspecialchars($table['nomor_meja']); ?>" data-kapasitas="<?php echo htmlspecialchars($table['kapasitas']); ?>" <?php echo $disabled; ?> <?php echo ($table['nomor_meja'] === $nomor_meja) ? 'selected' : ''; ?>>
                                        <?php echo $label; ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="" style="color: #6c757d;">No tables available</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Capacity</label>
                        <input type="text" id="capacityDisplay" class="form-control" readonly value="<?php echo $nomor_meja ? (isset($tables[0]['kapasitas']) ? $tables[0]['kapasitas'] . ' persons' : 'Please select a table') : 'Please select a table'; ?>">
                    </div>
                    <div id="seatingImage" class="mt-4 text-center" style="display: <?php echo $nama_area ? 'block' : 'none'; ?>;">
                        <img src="<?php echo isset($areas[$nama_area]['gambar_area']) ? $areas[$nama_area]['gambar_area'] : $fallback_image; ?>" class="card-img-top" alt="Selected Seating Area" id="seatingAreaImage">
                    </div>
                </div>
            </div>

            <!-- Bagian pemilihan tanggal dan waktu -->
            <div class="col-12 col-md-6" data-aos="fade-left">
                <h4 class="text-anchor mb-4">Date & Time</h4>
                <div class="card p-4">
                    <div class="mb-4">
                        <label class="form-label">Date</label>
                        <div class="custom-date-time">
                            <input type="text" id="customDate" readonly>
                        </div>
                    </div>
                    <div class="mt-4 mb-4">
                        <label class="form-label">Time</label>
                        <div class="custom-date-time">
                            <select class="form-select" id="customTime" aria-label="Select Time">
                                <option value="" style="color: #6c757d;">Select Time</option>
                                <?php
                                // Menampilkan opsi waktu dari pukul 17:00 hingga 00:00
                                $times = ['05:00 PM', '06:00 PM', '07:00 PM', '08:00 PM', '09:00 PM', '10:00 PM', '11:00 PM', '12:00 AM'];
                                foreach ($times as $time) {
                                    echo "<option value='$time'>$time</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <small class="text-muted">Available between 5:00 PM and 12:00 AM</small>
                    </div>
                </div>
            </div>

            <!-- Bagian menu pra-pesan (diambil dari artifact) -->
            <div class="col-12" data-aos="fade-up">
                <h4 class="text-anchor mb-4">Pre-order Menu (Optional)</h4>
                <div class="row g-4">
                    <?php foreach ($categories as $category): ?>
                        <div class="col-12" data-aos="zoom-in" data-aos-delay="<?php echo (array_search($category, $categories) + 1) * 100; ?>">
                            <div class="card p-4 card-menu">
                                <h5 class="category-title"><?php echo ucfirst(str_replace(['makanan', 'minuman'], ['Food', 'Drinks'], htmlspecialchars($category))); ?></h5>
                                <div class="menu-list">
                                    <?php if (isset($menus[$category]) && !empty($menus[$category])): ?>
                                        <?php foreach ($menus[$category] as $menu): ?>
                                            <?php
                                            // Menentukan status ketersediaan dan stok menu
                                            $disabled = ($menu['tersedia'] && $menu['stok'] > 0) ? '' : 'disabled';
                                            $class = ($menu['tersedia'] && $menu['stok'] > 0) ? '' : 'sold-out';
                                            ?>
                                            <div class="menu-item <?php echo $class; ?>">
                                                <img src="<?php echo htmlspecialchars($menu['gambar_menu']); ?>" alt="<?php echo htmlspecialchars($menu['nama_menu']); ?>" class="menu-item-img">
                                                <input type="checkbox" id="<?php echo htmlspecialchars(strtolower($category) . '-' . str_replace(' ', '-', $menu['nama_menu'])); ?>" name="<?php echo str_replace('Dessert', 'dessert', str_replace(['makanan', 'minuman'], ['food', 'drinks'], strtolower($category))) . '[]'; ?>" value="<?php echo htmlspecialchars($menu['nama_menu']); ?>" <?php echo $disabled; ?>>
                                                <label for="<?php echo htmlspecialchars(strtolower($category) . '-' . str_replace(' ', '-', $menu['nama_menu'])); ?>">
                                                    <?php echo htmlspecialchars($menu['nama_menu']); ?>
                                                    <?php if (!$menu['tersedia'] || $menu['stok'] <= 0): ?>
                                                        <span class="sold-out-badge">Sold Out</span>
                                                    <?php endif; ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-muted text-center">No <?php echo ucfirst(str_replace(['makanan', 'minuman'], ['Food', 'Drinks'], htmlspecialchars($category))); ?> available</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Tombol navigasi -->
            <div class="col-12 button-container" data-aos="fade-up">
                <button class="btn btn-back" onclick="goBack()">
                    <i class="fas fa-arrow-left me-2"></i> Back
                </button>
                <button class="btn btn-primary" onclick="submitReservation()">Next <i class="fas fa-arrow-right ms-2"></i></button>
            </div>
        </div>
    </section>

    <!-- Alert kustom untuk pesan error -->
    <div id="customAlert" class="custom-alert">
        <p id="alertMessage">Please select a seating area, table number, date, time, and at least one available menu item to proceed.</p>
        <button onclick="closeAlert()">OK</button>
    </div>

    <!-- Prompt untuk kustomisasi interior -->
    <div id="interiorPrompt" class="custom-alert">
        <p>Would you like to customize the interior or request special decorations for your reservation?</p>
        <button onclick="redirectToInterior()">Yes</button>
        <button onclick="redirectToConfirmation()">No</button>
    </div>

    <!-- Mengimpor library JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.4/gsap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/pikaday/pikaday.js"></script>
    <script>
        // Menginisialisasi AOS untuk animasi
        AOS.init({ duration: 1000, once: true });

        // Membuat partikel untuk efek latar belakang
        function createParticles() {
            const particleContainer = document.getElementById('particles');
            if (!particleContainer) return;
            const numParticles = 20;
            for (let i = 0; i < numParticles; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                const size = Math.random() * 8 + 3;
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                particle.style.left = `${Math.random() * 100}vw`;
                particle.style.animationDelay = `${Math.random() * 20}s`;
                particle.style.transitionDelay = `${Math.random() * 15 + 15}s`;
                particleContainer.appendChild(particle);
            }
        }
        createParticles();

        // Menginisialisasi Pikaday untuk kalender
        let picker;
        try {
            picker = new Pikaday({
                field: document.getElementById('customDate'),
                format: 'DD/MM/YYYY',
                minDate: new Date('<?php echo $current_date->format('Y-m-d'); ?>'),
                defaultDate: new Date('<?php echo $current_date->format('Y-m-d'); ?>'),
                setDefaultDate: true,
                toString(date, format) {
                    const day = ('0' + date.getDate()).slice(-2);
                    const month = ('0' + (date.getMonth() + 1)).slice(-2);
                    const year = date.getFullYear();
                    return `${day}/${month}/${year}`;
                },
                onSelect: function() {
                    document.getElementById('customDate').value = this.toString(this.getDate(), 'DD/MM/YYYY');
                    updateTimeOptions();
                }
            });
        } catch (e) {
            console.error('Pikaday gagal diinisialisasi:', e);
            const dateInput = document.getElementById('customDate');
            dateInput.removeAttribute('readonly');
            const today = new Date('<?php echo $current_date->format('Y-m-d'); ?>');
            const day = ('0' + today.getDate()).slice(-2);
            const month = ('0' + (today.getMonth() + 1)).slice(-2);
            const year = today.getFullYear();
            dateInput.value = `${day}/${month}/${year}`;
            dateInput.placeholder = 'DD/MM/YYYY';
        }

        // Memperbarui gambar dan kapasitas berdasarkan area yang dipilih
        function updateSeatingImageAndCapacity() {
            const select = document.getElementById('seatingSelect');
            const tableSelect = document.getElementById('tableSelect');
            const capacityDisplay = document.getElementById('capacityDisplay');
            const img = document.getElementById('seatingImage');
            const imgElement = document.getElementById('seatingAreaImage');
            if (!select || !tableSelect || !capacityDisplay || !img || !imgElement) return;

            const selectedOption = select.options[select.selectedIndex];
            if (selectedOption.value) {
                const imageSrc = selectedOption.getAttribute('data-gambar');
                console.log('Mencoba memuat gambar area tempat duduk: ' + imageSrc);
                imgElement.src = imageSrc;
                imgElement.onerror = function() {
                    console.error('Failed to load seating area image: ' + imageSrc);
                    imgElement.src = '<?php echo htmlspecialchars($fallback_image); ?>';
                };
                img.style.display = 'block';
                gsap.fromTo(img, { opacity: 0, scale: 0.95 }, { opacity: 1, scale: 1, duration: 0.7, ease: 'power2.out' });
                tableSelect.disabled = false;
                tableSelect.innerHTML = '<option value="" style="color: #6c757d;">Loading tables...</option>';
                const xhr = new XMLHttpRequest();
                xhr.open('GET', `get_tables.php?nama_area=${encodeURIComponent(selectedOption.value)}`, true);
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        try {
                            const tables = JSON.parse(xhr.responseText);
                            tableSelect.innerHTML = '<option value="" style="color: #6c757d;">Select Table</option>';
                            tables.forEach(table => {
                                const disabled = table.tersedia ? '' : 'disabled';
                                const label = table.tersedia ? `Table ${table.nomor_meja}` : `Table ${table.nomor_meja} (Sold Out)`;
                                tableSelect.innerHTML += `<option value="${table.nomor_meja}" data-kapasitas="${table.kapasitas}" ${disabled}>${label}</option>`;
                            });
                            const storedTable = sessionStorage.getItem('selected_nomor_meja');
                            if (storedTable && sessionStorage.getItem('fromInterior') === 'true') {
                                tableSelect.value = storedTable;
                                updateCapacityBasedOnTable();
                            }
                        } catch (e) {
                            console.error('Gagal mem-parsing respons JSON:', e);
                            showAlert('Failed to load tables. Please try again.');
                            tableSelect.innerHTML = '<option value="" style="color: #6c757d;">No tables available</option>';
                            tableSelect.disabled = true;
                        }
                    } else {
                        console.error('Permintaan AJAX gagal, status:', xhr.status);
                        showAlert('Failed to load tables. Please try again.');
                        tableSelect.innerHTML = '<option value="" style="color: #6c757d;">No tables available</option>';
                        tableSelect.disabled = true;
                    }
                    updateCapacityBasedOnTable();
                };
                xhr.onerror = function() {
                    console.error('Permintaan AJAX gagal');
                    showAlert('Failed to load tables. Please try again.');
                    tableSelect.innerHTML = '<option value="" style="color: #6c757d;">No tables available</option>';
                    tableSelect.disabled = true;
                    updateCapacityBasedOnTable();
                };
                xhr.send();
            } else {
                tableSelect.disabled = true;
                tableSelect.innerHTML = '<option value="" style="color: #6c757d;">Select Table</option>';
                capacityDisplay.value = 'Please select a table';
                img.style.display = 'none';
            }
        }

        // Memperbarui kapasitas berdasarkan meja yang dipilih
        function updateCapacityBasedOnTable() {
            const tableSelect = document.getElementById('tableSelect');
            const capacityDisplay = document.getElementById('capacityDisplay');
            if (!tableSelect || !capacityDisplay) return;

            const selectedTable = tableSelect.options[tableSelect.selectedIndex];
            if (selectedTable.value) {
                const kapasitas = selectedTable.getAttribute('data-kapasitas');
                capacityDisplay.value = kapasitas ? `${kapasitas} persons` : 'No capacity available';
            } else {
                capacityDisplay.value = 'Please select a table';
            }
        }

        // Kembali ke halaman sebelumnya
        function goBack() {
            window.history.back();
            console.log('Tombol kembali clicked');
        }

        // Inisialisasi saat halaman dimuat
        document.addEventListener('DOMContentLoaded', function() {
            const fromInterior = sessionStorage.getItem('fromInterior');
            const urlParams = new URLSearchParams(window.location.search);
            const initialNamaArea = urlParams.get('nama_area');
            const initialNomorMeja = urlParams.get('nomor_meja');

            if (!fromInterior || fromInterior !== 'true') {
                // Menghapus data sesi jika bukan dari halaman interior
                sessionStorage.removeItem('selected_nama_area');
                sessionStorage.removeItem('selected_nomor_meja');
                sessionStorage.removeItem('selected_date');
                sessionStorage.removeItem('selected_time');
                sessionStorage.removeItem('selected_food');
                sessionStorage.removeItem('selected_drinks');
                sessionStorage.removeItem('selected_dessert');
                sessionStorage.removeItem('fromInterior');

                const seatingSelect = document.getElementById('seatingSelect');
                const tableSelect = document.getElementById('tableSelect');
                const capacityDisplay = document.getElementById('capacityDisplay');
                const customDate = document.getElementById('customDate');
                const customTime = document.getElementById('customTime');
                const checkboxes = document.querySelectorAll('input[type="checkbox"]');

                if (seatingSelect) seatingSelect.value = initialNamaArea || '';
                updateSeatingImageAndCapacity();
                if (tableSelect) {
                    tableSelect.disabled = !initialNamaArea;
                    if (initialNamaArea && initialNomorMeja) tableSelect.value = initialNomorMeja;
                }
                updateCapacityBasedOnTable();
                if (customDate) customDate.value = '';
                if (customTime) customTime.value = '';
                checkboxes.forEach(cb => cb.checked = false);
            } else {
                // Memuat data dari sesi jika kembali dari halaman interior
                const seating = sessionStorage.getItem('selected_nama_area');
                const table = sessionStorage.getItem('selected_nomor_meja');
                const date = sessionStorage.getItem('selected_date');
                const time = sessionStorage.getItem('selected_time');
                const food = sessionStorage.getItem('selected_food') ? sessionStorage.getItem('selected_food').split(',') : [];
                const drinks = sessionStorage.getItem('selected_drinks') ? sessionStorage.getItem('selected_drinks').split(',') : [];
                const dessert = sessionStorage.getItem('selected_dessert') ? sessionStorage.getItem('selected_dessert').split(',') : [];

                const seatingSelect = document.getElementById('seatingSelect');
                const tableSelect = document.getElementById('tableSelect');
                const customDate = document.getElementById('customDate');
                const customTime = document.getElementById('customTime');

                if (seatingSelect) seatingSelect.value = seating || initialNamaArea || '';
                updateSeatingImageAndCapacity();
                if (tableSelect && table) tableSelect.value = table || (initialNomorMeja && initialNamaArea ? initialNomorMeja : '');
                updateCapacityBasedOnTable();
                if (customDate) customDate.value = date || '';
                if (customTime) customTime.value = time || '';
                updateTimeOptions();

                const foodCheckboxes = document.querySelectorAll('input[name="food[]"]');
                const drinksCheckboxes = document.querySelectorAll('input[name="drinks[]"]');
                const dessertCheckboxes = document.querySelectorAll('input[name="dessert[]"]');

                foodCheckboxes.forEach(cb => cb.checked = food.includes(cb.value) && !cb.disabled);
                drinksCheckboxes.forEach(cb => cb.checked = drinks.includes(cb.value) && !cb.disabled);
                dessertCheckboxes.forEach(cb => cb.checked = dessert.includes(cb.value) && !cb.disabled);
            }

            // Menangani error saat memuat gambar menu
            const menuImages = document.querySelectorAll('.menu-item-img');
            menuImages.forEach(img => {
                img.onerror = function() {
                    console.error('Gagal memuat gambar menu: ' + img.src);
                    img.src = '<?php echo htmlspecialchars($fallback_image); ?>';
                };
            });

            updateTimeOptions();
        });

        // Menampilkan alert kustom
        function showAlert(message) {
            const alert = document.getElementById('customAlert');
            if (alert) {
                document.getElementById('alertMessage').textContent = message;
                alert.style.display = 'block';
                alert.style.animation = 'fadeIn 0.5s ease-in-out';
                setTimeout(() => {
                    closeAlert();
                }, 3500);
            }
        }

        // Menutup alert kustom
        function closeAlert() {
            const alert = document.getElementById('customAlert');
            if (alert) {
                gsap.to(alert, { opacity: 0, scale: 0.9, duration: 0.5, ease: 'power2.out', onComplete: () => {
                    alert.style.display = 'none';
                    alert.style.opacity = 1;
                }});
            }
        }

        // Variabel untuk menyimpan data reservasi
        let reservationData = {};

        // Menampilkan prompt untuk kustomisasi interior
        function showInteriorPrompt() {
            const prompt = document.getElementById('interiorPrompt');
            if (prompt) {
                prompt.style.display = 'block';
                prompt.style.animation = 'fadeIn 0.5s ease-in-out';
            }
        }

        // Mengarahkan ke halaman kustomisasi interior
        function redirectToInterior() {
            const seatingSelect = document.getElementById('seatingSelect');
            const tableSelect = document.getElementById('tableSelect');
            const capacityDisplay = document.getElementById('capacityDisplay');
            const dateInput = document.getElementById('customDate');
            const timeSelect = document.getElementById('customTime');

            if (!seatingSelect || !tableSelect || !capacityDisplay || !dateInput || !timeSelect) {
                showAlert('An unexpected error has occurred. Please reload the page.');
                return;
            }

            const seating = seatingSelect.value;
            const tableNumber = tableSelect.value;
            const capacityText = capacityDisplay.value;
            const capacity = capacityText.match(/\d+/) ? capacityText.match(/\d+/)[0] : '';
            const date = dateInput.value;
            const time = timeSelect.value;

            if (!seating || !tableNumber || !capacity || capacityText === 'Please select a table' || capacityText.includes('Error') || !date || !time) {
                showAlert('Please select a seating area, table number, date, and time to proceed.');
                return;
            }

            const dateRegex = /^(\d{2})\/(\d{2})\/(\d{4})$/;
            if (!dateRegex.test(date)) {
                showAlert('Please ensure the date is in the correct format (DD/MM/YYYY).');
                return;
            }

            const timeValue = time.match(/(\d{1,2}):(\d{2})\s?(AM|PM)/i);
            if (timeValue) {
                let hours = parseInt(timeValue[1]);
                const minutes = parseInt(timeValue[2]);
                const period = timeValue[3].toUpperCase();
                if (period === 'PM' && hours !== 12) hours += 12;
                else if (period === 'AM' && hours === 12) hours = 0;
                if (hours < 17 && hours !== 0) {
                    showAlert('Please choose a time between 5:00 PM and 12:00 AM.');
                    return;
                }
            } else {
                showAlert('Please enter a valid time format.');
                return;
            }

            reservationData = {
                nama_area: encodeURIComponent(seating),
                nomor_meja: tableNumber,
                kapasitas: capacity,
                date: date,
                time: time,
                food: Array.from(document.querySelectorAll('input[name="food[]"]:checked:not(:disabled)')).map(item => item.value).join(','),
                drinks: Array.from(document.querySelectorAll('input[name="drinks[]"]:checked:not(:disabled)')).map(item => item.value).join(','),
                dessert: Array.from(document.querySelectorAll('input[name="dessert[]"]:checked:not(:disabled)')).map(item => item.value).join(',')
            };

            sessionStorage.setItem('selected_nama_area', seating);
            sessionStorage.setItem('selected_nomor_meja', tableNumber);
            sessionStorage.setItem('selected_date', date);
            sessionStorage.setItem('selected_time', time);
            sessionStorage.setItem('selected_food', reservationData.food);
            sessionStorage.setItem('selected_drinks', reservationData.drinks);
            sessionStorage.setItem('selected_dessert', reservationData.dessert);
            sessionStorage.setItem('fromInterior', 'true');

            const params = new URLSearchParams(reservationData).toString();
            window.location.href = `interior.php?${params}`;
        }

        // Mengarahkan ke halaman konfirmasi
        function redirectToConfirmation() {
            const params = new URLSearchParams(reservationData).toString();
            window.location.href = `./confirmation.php?${params}`;
        }

        // Memperbarui opsi waktu berdasarkan tanggal yang dipilih
        function updateTimeOptions() {
            const timeSelect = document.getElementById('customTime');
            const dateInput = document.getElementById('customDate').value;
            const currentTime = <?php echo $current_time; ?>;
            const currentMinutes = <?php echo $current_minutes; ?>;
            const todayDate = '<?php echo $today; ?>';
            const selectedDate = dateInput ? moment(dateInput, 'DD/MM/YYYY').format('YYYY-MM-DD') : todayDate;

            timeSelect.innerHTML = '<option value="" style="color: #6c757d;">Select Time</option>';
            const availableTimes = ['05:00 PM', '06:00 PM', '07:00 PM', '08:00 PM', '09:00 PM', '10:00 PM', '11:00 PM', '12:00 AM'];

            if (selectedDate < todayDate) {
                availableTimes.forEach(time => {
                    timeSelect.innerHTML += `<option value="${time}" disabled>${time} (Not available)</option>`;
                });
            } else if (selectedDate === todayDate) {
                const now = moment().set({ hour: currentTime, minute: currentMinutes });
                availableTimes.forEach(time => {
                    const timeMoment = moment(time, 'hh:mm A');
                    const timeHour = timeMoment.hour();
                    const timeMinute = timeMoment.minute();
                    const timeInFuture = moment().set({ hour: timeHour, minute: timeMinute });
                    
                    if (timeInFuture.diff(now, 'minutes') <= 60) {
                        timeSelect.innerHTML += `<option value="${time}" disabled>${time} (Not available)</option>`;
                    } else {
                        timeSelect.innerHTML += `<option value="${time}">${time}</option>`;
                    }
                });
            } else {
                availableTimes.forEach(time => {
                    timeSelect.innerHTML += `<option value="${time}">${time}</option>`;
                });
            }

            const storedTime = sessionStorage.getItem('selected_time');
            if (storedTime && timeSelect.querySelector(`option[value="${storedTime}"]:not([disabled])`)) {
                timeSelect.value = storedTime;
            }
        }

        // Mengirim reservasi dengan validasi
        function submitReservation() {
            const seatingSelect = document.getElementById('seatingSelect');
            const tableSelect = document.getElementById('tableSelect');
            const capacityDisplay = document.getElementById('capacityDisplay');
            const dateInput = document.getElementById('customDate');
            const timeSelect = document.getElementById('customTime');
            const menuItems = document.querySelectorAll('input[type="checkbox"]:checked:not(:disabled)');

            if (!seatingSelect || !tableSelect || !capacityDisplay || !dateInput || !timeSelect) {
                showAlert('An unexpected error has occurred. Please reload the page.');
                return;
            }

            const seating = seatingSelect.value;
            const tableNumber = tableSelect.value;
            const capacityText = capacityDisplay.value;
            const capacity = capacityText.match(/\d+/) ? capacityText.match(/\d+/)[0] : '';
            const date = dateInput.value;
            const time = timeSelect.value;

            if (!seating || !tableNumber || !capacity || capacityText === 'Please select a table' || capacityText.includes('Error') || !date || !time || menuItems.length === 0) {
                showAlert('Please select your preferred seating area, table number, date, time, and at least one available menu item to proceed.');
                return;
            }

            const dateRegex = /^(\d{2})\/(\d{2})\/(\d{4})$/;
            if (!dateRegex.test(date)) {
                showAlert('Please ensure the date is in the correct format (DD/MM/YYYY).');
                return;
            }

            const todayDate = '<?php echo $today; ?>';
            const selectedDate = moment(date, 'DD/MM/YYYY').format('YYYY-MM-DD');
            const currentTime = <?php echo $current_time; ?>;
            const currentMinutes = <?php echo $current_minutes; ?>;
            const now = moment().set({ hour: currentTime, minute: currentMinutes });

            const timeValue = time.match(/(\d{1,2}):(\d{2})\s?(AM|PM)/i);
            if (timeValue) {
                let hours = parseInt(timeValue[1]);
                const minutes = parseInt(timeValue[2]);
                const period = timeValue[3].toUpperCase();
                if (period === 'PM' && hours !== 12) hours += 12;
                else if (period === 'AM' && hours === 12) hours = 0;

                const selectedTime = moment().set({ hour: hours, minute: minutes });

                if (selectedDate === todayDate) {
                    if (selectedTime.diff(now, 'minutes') <= 60) {
                        showAlert('Reservations must be made at least 1 hour in advance. Please select a later time.');
                        return;
                    }
                }

                if (selectedDate === todayDate && selectedTime.isSameOrBefore(now)) {
                    showAlert('The selected time has already passed. Please choose a future time.');
                    return;
                }

                if (hours < 17 && hours !== 0) {
                    showAlert('Please choose a time between 5:00 PM and 12:00 AM.');
                    return;
                }
            } else {
                showAlert('Please enter a valid time format.');
                return;
            }

            reservationData = {
                nama_area: encodeURIComponent(seating),
                nomor_meja: tableNumber,
                kapasitas: capacity,
                date: date,
                time: time,
                food: Array.from(document.querySelectorAll('input[name="food[]"]:checked:not(:disabled)')).map(item => item.value).join(','),
                drinks: Array.from(document.querySelectorAll('input[name="drinks[]"]:checked:not(:disabled)')).map(item => item.value).join(','),
                dessert: Array.from(document.querySelectorAll('input[name="dessert[]"]:checked:not(:disabled)')).map(item => item.value).join(',')
            };

            try {
                gsap.fromTo('.btn-primary', { scale: 1, boxShadow: '0 5px 15px rgba(212, 175, 55, 0.4)' }, {
                    scale: 1.05,
                    boxShadow: '0 10px 25px rgba(212, 175, 55, 0.6)',
                    duration: 0.4,
                    ease: 'power2.out',
                    onComplete: () => {
                        showInteriorPrompt();
                    }
                });
            } catch (e) {
                console.error('Animasi GSAP gagal:', e);
                showInteriorPrompt();
            }
        }
    </script>
</body>
</html>