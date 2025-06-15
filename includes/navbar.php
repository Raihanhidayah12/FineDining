<?php
// Get the current page name
$current_page = basename($_SERVER['PHP_SELF']);
?>

<style>
  /* Sticky & fully transparent navbar */
  .navbar-custom {
    background-color: transparent !important;
    position: fixed;
    top: 0;
    width: 100%;
    z-index: 999;
    transition: background-color 0.3s ease;
    padding: 0.5rem 1rem; /* Mengurangi padding dari default Bootstrap */
    min-height: 60px; /* Mengatur tinggi minimum navbar */
  }

  .navbar-custom .navbar-brand,
  .navbar-custom .nav-link {
    color: var(--text-light, #ffffff) !important;
    font-family: 'Playfair Display', serif;
    transition: color 0.3s ease;
  }

  .navbar-custom .navbar-brand {
    font-size: 1.2rem; /* Membesarkan teks FineDining dari 1rem menjadi 1.2rem */
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.3rem; /* Jarak antara logo dan teks */
  }

  .navbar-custom .nav-link {
    font-size: 0.9rem; /* Mengecilkan ukuran teks nav-link */
    padding: 0.5rem 1rem; /* Mengurangi padding nav-link */
  }

  .navbar-custom .nav-link:hover {
    color: var(--primary-gold, #d4af37) !important;
  }

  /* Active nav-link style */
  .navbar-custom .nav-link.active {
    color: var(--primary-gold, #d4af37) !important;
    font-weight: 700;
  }

  /* Logo styling */
  .navbar-custom .navbar-brand img {
    height: 60px; /* Mengecilkan logo dari 40px menjadi 30px */
    width: auto;
    vertical-align: middle;
  }

  /* Toggler button */
  .navbar-custom .navbar-toggler {
    padding: 0.25rem 0.5rem; /* Mengecilkan padding toggler */
    font-size: 1rem; /* Mengecilkan ukuran ikon toggler */
  }

  /* Responsive adjustments */
  @media (max-width: 768px) {
    .navbar-custom .navbar-brand {
      font-size: 1.1rem;
    }
    .navbar-custom .navbar-brand img {
      height: 28px; /* Penyesuaian untuk tablet */
    }
    .navbar-custom .nav-link {
      font-size: 0.85rem;
    }
  }

  @media (max-width: 576px) {
    .navbar-custom .navbar-brand {
      font-size: 1rem;
    }
    .navbar-custom .navbar-brand img {
      height: 25px; /* Penyesuaian untuk ponsel */
    }
    .navbar-custom .nav-link {
      font-size: 0.8rem;
    }
  }
</style>

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Roboto:wght@300;400&display=swap" rel="stylesheet">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php">
      <img src="assets/img/logo.png" alt="FineDining Logo"> <!-- Ganti dengan path logo Anda -->
      FineDining
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <ul class="navbar-nav align-items-center">
        <li class="nav-item">
          <a class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>" href="index.php">Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo ($current_page == 'index.php' && isset($_GET['section']) && $_GET['section'] == 'seating-experiences') ? 'active' : ''; ?>" href="index.php#seating-experiences">Seating</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo ($current_page == 'index.php' && isset($_GET['section']) && $_GET['section'] == 'menu') ? 'active' : ''; ?>" href="index.php#menu">Menus</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo $current_page == 'login.php' ? 'active' : ''; ?>" href="login.php"><i class="bi bi-person fs-5"></i></a>
        </li>
      </ul>
    </div>
  </div>
</nav>