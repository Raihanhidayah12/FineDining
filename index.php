<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

<!-- Bootstrap 5 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
<!-- AOS Animation Library -->
<link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha384-3B6N4N7H5F7D1F2D6G8H9I0J9K5L2F3G4H5J6K7L8M9N0O1P2Q3R4S5T6U7V8W" crossorigin="anonymous" referrerpolicy="no-referrer" />
<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Roboto:wght@300;400&display=swap" rel="stylesheet">
<link rel="icon" href="./assets/img/logo.png" type="image/gif" sizes="16x16">

<style>
/* Root Variables */
/* Root Variables */
:root {
  --primary-gold: #d4af37;
  --accent-gold: #e6c74a;
  --dark-bg: #0f0f1a;
  --light-bg: #1e1e2f;
  --text-light: #ffffff;
  --text-muted: #d1d1d1;
  --navbar-bg: rgba(18, 18, 18, 0.95);
  --shadow-sm: 0 2px 5px rgba(0, 0, 0, 0.2);
  --shadow-lg: 0 15px 25px rgba(0, 0, 0, 0.5);
}

/* Base Styles */
body {
  font-family: 'Roboto', sans-serif;
  background-color: var(--dark-bg);
  color: var(--text-light);
  margin: 0;
  overflow-x: hidden;
}

/* Hide Scrollbar */
html,
body {
  scrollbar-width: none;
  -ms-overflow-style: none;
}

html::-webkit-scrollbar {
  display: none;
}

/* Typography */
h1, h2, h3, h4, h5, h6 {
  font-family: 'Playfair Display', serif;
  color: var(--primary-gold);
  font-weight: 900;
  letter-spacing: 0.5px;
}

.section-title {
  font-size: 3rem;
  margin-bottom: 2.5rem;
  text-align: center;
  text-shadow: var(--shadow-sm);
}

.section-title::after {
  content: '';
  display: block;
  width: 100px;
  height: 4px;
  background: var(--primary-gold);
  margin: 0.75rem auto;
}

/* Navbar */
.navbar {
  background: var(--navbar-bg);
  backdrop-filter: blur(10px);
  position: sticky;
  top: 0;
  z-index: 1000;
  transition: box-shadow 0.3s ease;
  box-shadow: var(--shadow-sm);
}

.navbar-brand,
.nav-link {
  font-family: 'Playfair Display', serif;
  color: var(--text-light) !important;
  transition: color 0.3s ease;
}

.navbar-brand {
  font-size: 1.75rem;
  font-weight: 700;
}

.nav-link:hover {
  color: var(--primary-gold) !important;
}

/* Hero Section */
.hero-bg-unified {
  background: linear-gradient(rgba(0, 0, 0, 0.75), rgba(0, 0, 0, 0.75)), url('assets/img/Group 402.png') center/cover no-repeat;
  height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  position: relative;
}

.hero-bg-unified::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 150px;
  background: linear-gradient(to top, var(--dark-bg), transparent);
}

/* Buttons */
.btn-primary,
.btn-gold {
  background-color: var(--primary-gold);
  border-color: var(--primary-gold);
  color: #0f0f1a;
  border-radius: 50px;
  padding: 0.75rem 2rem;
  font-family: 'Playfair Display', serif;
  font-weight: 700;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}

.btn-primary:hover,
.btn-gold:hover {
  background-color: var(--accent-gold);
  border-color: var(--accent-gold);
  transform: translateY(-3px);
  box-shadow: 0 5px 15px rgba(212, 175, 55, 0.4);
}

.btn-primary::after,
.btn-gold::after {
  content: '';
  position: absolute;
  width: 0;
  height: 100%;
  top: 0;
  left: 0;
  background: rgba(255, 255, 255, 0.3);
  transition: width 0.4s ease;
}

.btn-primary:hover::after,
.btn-gold:hover::after {
  width: 100%;
}

.btn-outline-light,
.btn-outline-gold {
  border-color: var(--primary-gold);
  color: var(--primary-gold);
  border-radius: 50px;
  padding: 0.75rem 2rem;
  font-family: 'Playfair Display', serif;
  font-weight: 700;
  transition: all 0.3s ease;
}

.btn-outline-light:hover,
.btn-outline-gold:hover:not(:disabled),
.btn-outline-gold.selected {
  background-color: var(--primary-gold);
  color: #0f0f1a;
  transform: translateY(-3px);
  box-shadow: 0 5px 15px rgba(212, 175, 55, 0.4);
}

.btn-outline-gold {
  background: transparent;
  border-radius: 25px;
  padding: 0.5rem 1.2rem;
  font-size: 0.9rem;
}

.btn-outline-gold::after {
  content: '';
  position: absolute;
  width: 0;
  height: 100%;
  top: 0;
  left: 0;
  background: rgba(255, 255, 255, 0.2);
  transition: width 0.3s ease;
}

.btn-outline-gold:hover::after {
  width: 100%;
}

.btn-booked {
  background-color: rgba(255, 0, 0, 0.8);
  border-color: rgba(255, 0, 0, 0.8);
  color: var(--text-light);
  border-radius: 25px;
  padding: 0.5rem 1.2rem;
  font-size: 0.9rem;
  cursor: not-allowed;
  opacity: 0.9;
}

.btn-booked:hover {
  opacity: 1;
}

.btn-secondary {
  background: rgba(51, 51, 51, 0.9);
  border: 1px solid var(--primary-gold);
  color: var(--primary-gold);
  font-family: 'Playfair Display', serif;
  font-weight: 700;
  border-radius: 50px;
  padding: 0.7rem 2rem;
  cursor: not-allowed;
}

.btn-secondary:hover {
  animation: shake 0.5s ease;
}

.carousel-btn {
  background: rgba(30, 30, 47, 0.9);
  backdrop-filter: blur(5px);
  color: var(--primary-gold);
  border: 2px solid var(--primary-gold);
  width: 50px;
  height: 50px;
  border-radius: 50%;
  transition: all 0.3s ease;
}

.carousel-btn:hover {
  background: var(--primary-gold);
  color: #0f0f1a;
  transform: scale(1.2);
}

.carousel-btn:focus {
  outline: 2px solid var(--primary-gold);
  outline-offset: 2px;
}

.carousel-btn i {
  font-size: 1.4rem;
}

/* Card Styles */
.card {
  border: none;
  border-radius: 20px;
  background: var(--light-bg);
  transition: transform 0.4s ease, box-shadow 0.4s ease;
}

.card:hover {
  transform: translateY(-12px);
  box-shadow: var(--shadow-lg);
}

.card-img-container {
  position: relative;
  overflow: hidden;
  height: 220px;
}

.card-img-top {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.8s ease;
  filter: brightness(0.9) contrast(1.2);
}

.card-body {
  color: var(--text-light);
  background: rgba(30, 30, 47, 0.95);
  backdrop-filter: blur(10px);
  border-bottom-left-radius: 20px;
  border-bottom-right-radius: 20px;
}

.card-title,
.card-text,
.card-text.text-muted {
  color: var(--text-light) !important;
}

.card-title.text-gold {
  color: var(--primary-gold) !important;
  text-shadow: 0 2px 6px rgba(212, 175, 55, 0.3);
  font-size: 1.8rem;
}

/* Menu Section */
.bg-dark {
  background: linear-gradient(to right, var(--dark-bg), var(--light-bg)) !important;
  position: relative;
  overflow: hidden;
}

#menu-carousel {
  scroll-snap-type: x mandatory;
  scroll-behavior: smooth;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
  -ms-overflow-style: none;
}

#menu-carousel::-webkit-scrollbar {
  display: none;
}

.menu-card {
  scroll-snap-align: start;
  margin-right: 1.5rem;
  width: 250px;
}

.menu-item {
  background: rgba(30, 30, 47, 0.95);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(212, 175, 55, 0.3);
  border-radius: 20px;
  box-shadow: var(--shadow-sm);
  transition: all 0.4s ease;
  display: flex;
  flex-direction: column;
  height: 100%;
}

.menu-item:hover {
  box-shadow: 0 20px 40px rgba(212, 175, 55, 0.4);
  transform: translateY(-10px) scale(1.02);
}

.menu-item:hover .card-img-top {
  transform: scale(1.2);
}

.menu-item .card-img-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(to bottom, rgba(212, 175, 55, 0.4), rgba(0, 0, 0, 0.8));
  opacity: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: opacity 0.5s ease;
}

.menu-item:hover .card-img-overlay {
  opacity: 1;
}

.menu-item .card-img-overlay h5 {
  font-family: 'Playfair Display', serif;
  font-size: 1.4rem;
  color: var(--text-light);
  text-shadow: 0 2px 6px rgba(0, 0, 0, 0.7);
  transform: translateY(30px);
  transition: transform 0.5s ease;
}

.menu-item:hover .card-img-overlay h5 {
  transform: translateY(0);
}

.menu-item .card-body {
  padding: 1.75rem;
  flex-grow: 1;
  display: flex;
  flex-direction: column;
}

.menu-item .card-title {
  font-family: 'Playfair Display', serif;
  font-size: 1.9rem;
  font-weight: 900;
  margin-bottom: 1rem;
  text-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
}

.menu-item .card-text {
  font-size: 0.95rem;
  color: var(--text-muted);
  margin-bottom: 1.25rem;
  line-height: 1.7;
  flex-grow: 1;
}

.menu-item .price {
  font-family: 'Roboto', sans-serif;
  font-size: 1.2rem;
  font-weight: bold;
  color: var(--primary-gold);
  margin-bottom: 1.25rem;
}

.menu-item .price::before {
  content: '';
  display: block;
  width: 80px;
  height: 3px;
  background: linear-gradient(to right, var(--primary-gold), transparent);
  margin-bottom: 0.75rem;
}

.menu-item .btn-container {
  margin-top: auto;
  text-align: center;
}

/* Seating Card */
.seating-card {
  background: linear-gradient(145deg, var(--light-bg), rgba(30, 30, 47, 0.9));
  border-radius: 20px;
  overflow: hidden;
  transition: transform 0.4s ease, box-shadow 0.4s ease;
}

.seating-card:hover {
  transform: translateY(-10px) scale(1.02);
  box-shadow: 0 20px 40px rgba(212, 175, 55, 0.4);
}

.seating-card:hover .card-img-top {
  transform: scale(1.1);
}

.card-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(to bottom, rgba(212, 175, 55, 0.3), rgba(0, 0, 0, 0.7));
  opacity: 0;
  transition: opacity 0.5s ease;
}

.seating-card:hover .card-overlay {
  opacity: 1;
}

.table-numbers {
  margin-top: 1.5rem;
  margin-left: -0.5rem;
}

.table-numbers .btn {
  margin: 0.25rem 0.5rem;
}

/* Particles Background */
#particles-bg {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 0;
}

/* Modal */
#loginModal {
  backdrop-filter: blur(5px);
}

#loginModal .modal-content {
  background: var(--light-bg);
  border: 1px solid var(--primary-gold);
  border-radius: 15px;
}

#loginModal .modal-header {
  border-bottom: none;
}

#loginModal .modal-title {
  font-family: 'Playfair Display', serif;
  color: var(--primary-gold);
  font-weight: 900;
}

#loginModal .modal-body p {
  color: var(--text-light);
  font-family: 'Roboto', sans-serif;
}

#loginModal .btn-primary {
  background: var(--primary-gold);
  border: none;
}

#loginModal .btn-secondary {
  background: transparent;
  border: 1px solid var(--primary-gold);
  color: var(--primary-gold);
}

/* Animations */
@keyframes ripple {
  to { transform: scale(4); opacity: 0; }
}

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  20%, 60% { transform: translateX(-4px); }
  40%, 80% { transform: translateX(4px); }
}

@keyframes glowPulse {
  0% { transform: translate(-50%, -50%) scale(0.8); opacity: 0.5; }
  100% { transform: translate(-50%, -50%) scale(1.2); opacity: 0.2; }
}

.glow-effect {
  position: absolute;
  top: 50%;
  left: 50%;
  width: 200px;
  height: 200px;
  background: radial-gradient(circle, rgba(212, 175, 55, 0.2) 0%, transparent 70%);
  transform: translate(-50%, -50%);
  z-index: -1;
  animation: glowPulse 2s infinite alternate;
  opacity: 0;
}

.btn-gold:hover .glow-effect {
  opacity: 1;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
  .hero-bg-unified { height: 80vh; }
  .section-title { font-size: 2.5rem; }
  .card-img-container,
  .menu-item .card-img-container,
  .seating-card .card-img-container { height: 180px; }
  .menu-card { width: 230px; }
  .menu-item .card-title { font-size: 1.6rem; }
  .menu-item .card-text { font-size: 0.9rem; }
  .menu-item .price { font-size: 1.1rem; }
  .menu-item .card-img-overlay h5 { font-size: 1.2rem; }
  .btn-primary, .btn-secondary, .btn-gold { font-size: 0.95rem; padding: 0.6rem 1.75rem; }
  .card-title.text-gold { font-size: 1.5rem; }
  .table-numbers .btn { font-size: 0.85rem; padding: 0.4rem 1rem; }
}

@media (max-width: 576px) {
  .hero-bg-unified h1 { font-size: 2.25rem; }
  .hero-bg-unified p { font-size: 1rem; }
  .section-title { font-size: 2rem; }
  .menu-card { width: 200px; }
  .menu-item .card-img-container,
  .seating-card .card-img-container { height: 160px; }
  .menu-item .card-body { padding: 1.25rem; }
  .menu-item .card-title { font-size: 1.3rem; }
  .menu-item .card-text { font-size: 0.85rem; }
  .menu-item .price { font-size: 1rem; }
  .menu-item .card-img-overlay h5 { font-size: 1.1rem; }
  .btn-primary, .btn-secondary, .btn-gold { font-size: 0.9rem; padding: 0.5rem 1.5rem; }
  .carousel-btn { width: 40px; height: 40px; }
  .carousel-btn i { font-size: 1.2rem; }
  .card-title.text-gold { font-size: 1.3rem; }
  .table-numbers .btn { font-size: 0.8rem; padding: 0.3rem 0.8rem; }
}
</style>

<?php
include 'includes/config.php';

// Fetch active menus for carousel (limit to 5)
$menus = [];
$result = $conn->query("SELECT nama_menu, deskripsi, harga, stok, gambar_menu FROM menu WHERE tersedia = 1 AND nama_menu IS NOT NULL AND harga IS NOT NULL AND deskripsi IS NOT NULL LIMIT 5");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $menus[] = $row;
    }
    $result->free();
}
?>

<!-- Hero Section -->
<header class="hero-bg-unified text-light position-relative" data-aos="fade-down">
  <div class="container">
    <h1 class="fw-bold display-4 mb-4">Experience the Best Culinary Journey</h1>
    <p class="lead mb-5">Discover special menus and a cozy ambiance for your memorable moments.</p>
    <div class="d-flex justify-content-center gap-3 flex-wrap">
      <a href="#" class="btn btn-primary shadow reserve-btn" onclick="reserveTable(event)">Reserve Your Table</a>
      <a href="#menu" class="btn btn-outline-light shadow">Explore Our Menu</a>
    </div>
  </div>
</header>

<!-- Seating Areas -->
<section class="container py-5" data-aos="fade-up" id="seating-experiences">
  <h2 class="text-center section-title">Our Seating Experiences</h2>
  <div class="row g-4">
    <?php
    $areas = [];
    $result = $conn->query("SELECT nama_area, nomor_meja, deskripsi, kapasitas, tersedia, gambar_area 
                            FROM area 
                            ORDER BY nama_area, nomor_meja");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $nama_area = $row['nama_area'];
            if (!isset($areas[$nama_area])) {
                $areas[$nama_area] = [
                    'deskripsi' => $row['deskripsi'],
                    'kapasitas' => [],
                    'gambar_area' => $row['gambar_area'],
                    'nomor_meja' => [],
                    'tersedia' => [],
                ];
            }
            $areas[$nama_area]['nomor_meja'][] = $row['nomor_meja'];
            $areas[$nama_area]['tersedia'][$row['nomor_meja']] = $row['tersedia'];
            $areas[$nama_area]['kapasitas'][] = (int)$row['kapasitas'];
        }
        $result->free();
    }

    $index = 0;
    foreach ($areas as $nama_area => $area) {
        $delay = $index * 150;
        $gambar_area = !empty($area['gambar_area']) && file_exists($area['gambar_area']) 
            ? htmlspecialchars($area['gambar_area']) 
            : 'img/default-area.jpg';
        $alt_text = htmlspecialchars($nama_area) . ' - ' . substr(htmlspecialchars($area['deskripsi']), 0, 50) . '...';

        $kapasitas = array_unique($area['kapasitas']);
        sort($kapasitas);
        $kapasitas_text = count($kapasitas) > 1 
            ? min($kapasitas) . '-' . max($kapasitas) . ' persons' 
            : $kapasitas[0] . ' person' . ($kapasitas[0] > 1 ? 's' : '');

        echo '<div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="' . $delay . '">
          <div class="card h-100 seating-card shadow-lg position-relative overflow-hidden">
            <div class="card-img-container">
              <img src="' . $gambar_area . '" class="card-img-top lazy" alt="' . $alt_text . '" loading="lazy">
              <div class="card-overlay"></div>
            </div>
            <div class="card-body p-4 text-start">
              <h5 class="card-title fw-bold text-gold mb-2">' . htmlspecialchars($nama_area) . '</h5>
              <p class="card-text text-muted mb-3">' . htmlspecialchars($area['deskripsi'] ?: 'No description available') . '</p>
              <div class="d-flex align-items-center mb-3">
                <span class="badge bg-gold me-2">' . $kapasitas_text . '</span>
              </div>
              <div class="table-numbers d-flex gap-3 flex-wrap justify-content-start" data-table="' . htmlspecialchars($nama_area) . '">';

        foreach ($area['nomor_meja'] as $nomor_meja) {
            $is_available = $area['tersedia'][$nomor_meja];
            $class = $is_available ? 'btn btn-outline-gold rounded-pill px-3' : 'btn btn-booked rounded-pill px-3';
            $disabled = $is_available ? '' : 'disabled';
            $text = $is_available ? "Table $nomor_meja" : "Table $nomor_meja (Booked)";
            $action = $is_available ? 'onclick="selectTableForReservation(this, ' . $nomor_meja . ', \'' . htmlspecialchars($nama_area) . '\')"' : '';
            
            echo '<button type="button" class="' . $class . '" ' . $disabled . ' ' . $action . ' data-nomor-meja="' . $nomor_meja . '">' . $text . '</button>';
        }
        echo '</div>';

        echo '<input type="hidden" name="selected_nomor_meja_' . htmlspecialchars($nama_area) . '" id="selected_nomor_meja_' . htmlspecialchars($nama_area) . '" value="">';

        $has_available_tables = array_filter($area['tersedia'], fn($status) => $status == 1);
        if ($has_available_tables) {
            echo '<div class="mt-4 position-relative">
                    <a href="#" class="btn btn-gold rounded-pill px-4 reserve-btn" 
                       onclick="reserveTable(event, \'' . htmlspecialchars($nama_area) . '\')">Reserve Now</a>
                    <span class="glow-effect"></span>
                  </div>';
        } else {
            echo '<div class="mt-4">
                    <button class="btn btn-booked rounded-pill px-4" disabled>Fully Booked</button>
                  </div>';
        }

        echo '</div>
          </div>
        </div>';
        $index++;
    }

    if (empty($areas)) {
        echo '<div class="col-12 text-center text-muted" role="alert">
                <p>No seating areas available at the moment.</p>
              </div>';  
    }
    ?>
  </div>
</section>

<!-- Popular Menu -->
<section class="py-5 bg-dark" id="menu" data-aos="fade-up">
  <div id="particles-bg"></div>
  <div class="container position-relative">
    <h2 class="text-center section-title" id="menu-title">Our Signature Menus</h2>
    <div class="position-relative">
      <button onclick="scrollMenu(-1)" class="carousel-btn position-absolute top-50 start-0 translate-middle-y z-3" aria-label="Previous menu item" tabindex="0"><i class="fas fa-chevron-left"></i></button>
      <button onclick="scrollMenu(1)" class="carousel-btn position-absolute top-50 end-0 translate-middle-y z-3" aria-label="Next menu item" tabindex="0"><i class="fas fa-chevron-right"></i></button>
      <div class="d-flex overflow-x-auto px-0" id="menu-carousel" role="region" aria-label="Menu carousel">
        <?php
        function buildCard($m) {
          $gambar = !empty($m['gambar_menu']) && file_exists($m['gambar_menu']) ? htmlspecialchars($m['gambar_menu']) : 'img/default-menu.jpg';
          $button_text = $m['stok'] > 0 ? '<a href="#" class="btn btn-primary rounded-pill px-4 order-btn" data-bs-toggle="modal" data-bs-target="#loginModal">Order Now</a>' : 
                                         '<button class="btn btn-secondary rounded-pill px-4" disabled>Sold Out</button>';
          $alt_text = htmlspecialchars($m['nama_menu']) . ' - ' . substr(htmlspecialchars($m['deskripsi']), 0, 50) . '...';
          return '<div class="card h-100 shadow menu-item" data-tilt data-tilt-max="10" data-tilt-speed="400" data-tilt-perspective="1000">
            <div class="card-img-container">
              <img src="' . $gambar . '" class="card-img-top lazy" alt="' . $alt_text . '" loading="lazy">
              <div class="card-img-overlay">
                <h5>' . htmlspecialchars($m['nama_menu']) . '</h5>
              </div>
            </div>
            <div class="card-body p-4">
              <h5 class="card-title fw-bold">' . htmlspecialchars($m['nama_menu']) . '</h5>
              <p class="card-text text-muted">' . htmlspecialchars($m['deskripsi']) . '</p>
              <p class="price">Rp ' . number_format($m['harga'], 0, ',', '.') . '</p>
              <div class="btn-container">
                ' . $button_text . '
              </div>
            </div>
          </div>';
        }

        if (!empty($menus)) {
          foreach ($menus as $index => $menu) {
            $menu['nama_menu'] = str_replace(
              ['Hors d’oeuvres', 'Beef Wellington', 'Grilled Salmon Steak', 'Chicken Cordon Bleu', 'Molten Lava Cake'],
              ['Appetizers', 'Beef Wellington', 'Grilled Salmon Steak', 'Chicken Cordon Bleu', 'Molten Lava Cake'],
              $menu['nama_menu']
            );
            $menu['deskripsi'] = str_replace(
              [
                'Canapes,Bruschetta,Stuffed Mushrooms,Shrimp Cocktail,Mini Quiche,Caprese Skewers and Spring Rolls Mini',
                'Daging sapi tenderloin dibalut puff pastry & mushroom duxelles.',
                'Fillet salmon panggang dengan saus lemon butter.',
                'Dada ayam berisi ham & keju, disajikan dengan saus krim jamur.',
                'Kue cokelat hangat dengan isi cokelat leleh & es krim vanilla.'
              ],
              [
                'Canapes, Bruschetta, Stuffed Mushrooms, Shrimp Cocktail, Mini Quiche, Caprese Skewers, and Spring Rolls Mini',
                'Tenderloin beef wrapped in puff pastry with mushroom duxelles.',
                'Grilled salmon fillet with lemon butter sauce.',
                'Chicken breast stuffed with ham and cheese, served with creamy mushroom sauce.',
                'Warm chocolate cake with a molten chocolate center, served with vanilla ice cream.'
              ],
              $menu['deskripsi']
            );
            echo '<div class="flex-shrink-0 menu-card" style="width: 250px;" data-aos="zoom-in" data-aos-delay="' . ($index * 150) . '">';
            echo buildCard($menu);
            echo '</div>';
          }
        } else {
          echo '<p class="text-center text-muted" role="alert">No menus available at the moment.</p>';
        }
        ?>
      </div>
    </div>
    <div class="text-center mt-5" data-aos="fade-up" data-aos-delay="200">
      <a href="#" class="btn btn-outline-light rounded-pill px-5" onclick="viewFullMenu(event)">View Full Menu</a>
    </div>
  </div>
</section>

<!-- Login Notification Modal -->
<div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="loginModalLabel">Login Required</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Please log in to place your order and enjoy our exclusive dining experience.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Close</button>
        <a href="login.php" class="btn btn-primary rounded-pill">Log In</a>
      </div>
    </div>
  </div>
</div>

<!-- Make a Reservation -->
<section class="py-5 text-center text-light"  data-aos="zoom-in">
  <div class="container">
    <h2 class="fw-bold display-5">Reserve Your Experience</h2>
    <p class="lead mb-5">Secure your table for an unforgettable dining moment</p>
    <a href="#" class="btn btn-primary rounded-pill px-5">Book Now</a>
  </div>
</section>

<!-- How to Book Your Spot -->
<section class="container py-5" data-aos="fade-up">
  <h2 class="text-center section-title">How to Reserve</h2>
  <div class="row g-4">
    <?php
    $steps = [
      ["Choose Your Spot", "Select from our exclusive seating areas"],
      ["Set Date & Time", "Pick the perfect moment for your visit"],
      ["Confirm Reservation", "Finalize with your contact details"]
    ];
    foreach ($steps as $i => $step) {
      $delay = $i * 150;
      echo '<div class="col-md-4" data-aos="fade-up" data-aos-delay="' . $delay . '">
        <div class="p-4 border rounded-4 h-100 shadow-sm bg-dark">
          <div class="display-5 text-makers fw-bold">' . ($i + 1) . '</div>
          <h5 class="mt-3">' . $step[0] . '</h5>
          <p class="subtext">' . $step[1] . '</p>
        </div>
      </div>';
    }
    ?>
  </div>
</section>

<?php include 'includes/footer.php'; ?>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<!-- AOS JS -->
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<!-- Particles.js -->
<script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
<!-- GSAP -->
<script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js"></script>
<!-- Vanilla Tilt -->
<script src="https://cdn.jsdelivr.net/npm/vanilla-tilt@1.8.1/dist/vanilla-tilt.min.js"></script>
<script>
  AOS.init({
    duration: 1000,
    once: true,
    easing: 'ease-out'
  });

  // GSAP animation for section title
  gsap.from("#menu-title", {
    duration: 1.5,
    y: 50,
    opacity: 0,
    rotationX: 10,
    ease: "power3.out",
    scrollTrigger: {
      trigger: "#menu",
      start: "top 80%"
    }
  });

  function scrollMenu(direction) {
    const carousel = document.querySelector('#menu-carousel');
    const scrollAmount = carousel.offsetWidth * 0.8 * direction;
    carousel.scrollBy({ left: scrollAmount, behavior: 'smooth' });
  }

  // Touch support for carousel
  let touchStartX = 0;
  let touchEndX = 0;

  const carousel = document.getElementById('menu-carousel');
  carousel.addEventListener('touchstart', (e) => {
    touchStartX = e.changedTouches[0].screenX;
  });

  carousel.addEventListener('touchend', (e) => {
    touchEndX = e.changedTouches[0].screenX;
    if (touchStartX - touchEndX > 50) scrollMenu(1);
    if (touchEndX - touchStartX > 50) scrollMenu(-1);
  });

  // Keyboard navigation for carousel
  document.querySelectorAll('.carousel-btn').forEach(btn => {
    btn.addEventListener('keypress', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        const direction = btn.getAttribute('aria-label').includes('Next') ? 1 : -1;
        scrollMenu(direction);
      }
    });
  });

  // Particles.js for menu section
  if (window.innerWidth > 576) {
    particlesJS('particles-bg', {
      particles: {
        number: { value: 60, density: { enable: true, value_area: 800 } },
        color: { value: ['#d4af37', '#e6c74a'] },
        shape: { type: ['circle', 'triangle'], stroke: { width: 0 } },
        opacity: { value: 0.5, random: true },
        size: { value: 3, random: true },
        line_linked: {
          enable: true,
          distance: 120,
          color: '#d4af37',
          opacity: 0.2,
          width: 1.5
        },
        move: {
          enable: true,
          speed: 1.5,
          direction: 'none',
          random: true,
          straight: false,
          out_mode: 'out',
          bounce: false
        }
      },
      interactivity: {
        detect_on: 'canvas',
        events: {
          onhover: { enable: true, mode: 'grab' },
          onclick: { enable: true, mode: 'push' },
          resize: true
        }
      },
      retina_detect: true
    });
  }

  // Ripple effect on button click
  document.querySelectorAll('.btn-primary').forEach(btn => {
    btn.addEventListener('click', function(e) {
      const rect = this.getBoundingClientRect();
      const ripple = document.createElement('span');
      ripple.classList.add('ripple');
      const diameter = Math.max(this.clientWidth, this.clientHeight);
      const radius = diameter / 2;
      ripple.style.width = ripple.style.height = `${diameter}px`;
      ripple.style.left = `${e.clientX - rect.left - radius}px`;
      ripple.style.top = `${e.clientY - rect.top - radius}px`;
      this.appendChild(ripple);
      setTimeout(() => ripple.remove(), 600);
    });
  });

  // Lazy load images
  document.querySelectorAll('.lazy').forEach(img => {
    if ('IntersectionObserver' in window) {
      const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const img = entry.target;
            img.src = img.src;
            observer.unobserve(img);
          }
        });
      });
      observer.observe(img);
    }
  });

  // Navbar shadow on scroll
  window.addEventListener('scroll', () => {
    const navbar = document.querySelector('.navbar');
    if (window.scrollY > 50) {
      navbar.style.boxShadow = '0 4px 10px rgba(0, 0, 0, 0.3)';
    } else {
      navbar.style.boxShadow = '0 2px 5px rgba(0, 0, 0, 0.2)';
    }
  });

  function selectTableForReservation(button, nomorMeja, namaArea) {
    const buttons = button.parentElement.querySelectorAll('.btn-outline-gold');
    const selectedInput = document.getElementById('selected_nomor_meja_' + namaArea);

    buttons.forEach(btn => {
      btn.classList.remove('selected');
      gsap.to(btn, { scale: 1, duration: 0.3, ease: "power2.out" });
    });

    button.classList.add('selected');
    gsap.fromTo(button, { scale: 1 }, { scale: 1.1, duration: 0.3, ease: "power2.out", yoyo: true, repeat: 1 });

    selectedInput.value = nomorMeja;
  }

function reserveTable(event) {
  event.preventDefault();
  const isLoggedIn = <?php echo isset($_SESSION['id_user']) ? 'true' : 'false'; ?>;
  if (!isLoggedIn) {
    const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
    loginModal.show();
    return;
  }
  // If logged in, redirect to reservation page or perform reservation action
  gsap.fromTo(event.target, { scale: 1, rotation: 0 }, {
    scale: 1.2,
    rotation: 5,
    duration: 0.5,
    ease: "elastic.out(1, 0.3)",
    onComplete: () => {
      window.location.href = 'reserve.php'; // Replace with your reservation page URL
    }
  });


    gsap.fromTo(event.target, { scale: 1, rotation: 0 }, {
      scale: 1.2,
      rotation: 5,
      duration: 0.5,
      ease: "elastic.out(1, 0.3)",
      onComplete: () => {
        window.location.href = `reserve.php?nama_area=${encodeURIComponent(namaArea)}&nomor_meja=${selectedNomorMeja}`;
      }
    });
  }

  function viewFullMenu(event) {
    event.preventDefault();
    const isLoggedIn = <?php echo isset($_SESSION['id_user']) ? 'true' : 'false'; ?>;
    if (!isLoggedIn) {
      const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
      loginModal.show();
      return;
    }
    gsap.fromTo(event.target, { scale: 1, rotation: 0 }, {
      scale: 1.2,
      rotation: 5,
      duration: 0.5,
      ease: "elastic.out(1, 0.3)",
      onComplete: () => {
        window.location.href = 'pages/menu.php';
      }
    });
  }
</script>