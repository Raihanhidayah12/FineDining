<?php include '../includes/header.php'; ?>
<!-- // Memasukkan file header.php dari direktori includes yang berada satu level di atas folder saat ini.
// File ini biasanya berisi elemen HTML awal seperti tag <html>, <head>, meta tags, dan mungkin navigasi. -->

<!-- Bootstrap 5 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
<!-- // Memuat file CSS Bootstrap 5 dari CDN untuk memberikan styling responsif dan komponen UI seperti grid, tombol, dan modal. -->

<!-- AOS Animation Library -->
<link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
<!-- // Memuat CSS untuk library AOS (Animate On Scroll) yang digunakan untuk animasi elemen saat pengguna menggulir halaman. -->

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha384-3B6N4N7H5F7D1F2D6G8H9I0J9K5L2F3G4H5J6K7L8M9N0O1P2Q3R4S5T6U7V8W" crossorigin="anonymous" referrerpolicy="no-referrer" />
<!-- // Memuat ikon Font Awesome versi 6 untuk elemen seperti ikon panah pada tombol "Back to Home". -->

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Roboto:wght@300;400&display=swap" rel="stylesheet">
<!-- // Memuat font Playfair Display (untuk judul elegan) dan Roboto (untuk teks biasa) dari Google Fonts dengan variasi berat font. -->
<link rel="icon" href="/assets/img/logo.png" type="image/gif" sizes="16x16">
<style>
:root {
  --primary-gold: #d4af37;
  --accent-gold: #e6c74a;
  --dark-bg: #0f0f1a;
  --light-bg: #1e1e2f;
  --text-light: #ffffff;
  --text-muted: #d1d1d1;
  --navbar-bg: rgba(18, 18, 18, 0.95);
}
/* // Mendefinisikan variabel CSS untuk warna yang konsisten di seluruh halaman, seperti emas, latar gelap, dan teks putih. */

body {
  font-family: 'Roboto', sans-serif;
  background-color: var(--dark-bg);
  color: var(--text-light);
  overflow-x: hidden;
  margin: 0;
}
/* // Mengatur gaya dasar body: font Roboto, latar belakang gelap, teks putih, mencegah overflow horizontal, dan menghapus margin default. */

html, body {
  scrollbar-width: none;
  -ms-overflow-style: none;
}
html::-webkit-scrollbar {
  display: none;
}
/* // Menyembunyikan scrollbar di semua browser untuk tampilan lebih bersih. */

.navbar {
  background: var(--navbar-bg);
  backdrop-filter: blur(10px);
  position: sticky;
  top: 0;
  z-index: 1000;
  transition: all 0.3s ease;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
  padding: 1rem 2rem;
}
/* // Gaya untuk navbar (meskipun dikomentari tidak digunakan): latar semi-transparan dengan blur, posisi sticky, dan bayangan halus. */

.navbar-brand, .nav-link {
  font-family: 'Playfair Display', serif;
  color: var(--text-light) !important;
  transition: color 0.3s ease, transform 0.3s ease;
}
.navbar-brand {
  font-size: 2rem;
  font-weight: 900;
}
.nav-link {
  font-size: 1.2rem;
  font-weight: 700;
  padding: 0.5rem 1rem;
}
.nav-link:hover, .nav-link.active {
  color: var(--primary-gold) !important;
  transform: scale(1.05);
}
/* // Gaya untuk logo navbar dan tautan navigasi: font Playfair Display, warna putih, efek hover berubah ke emas dan membesar. */

.navbar-toggler {
  border: 2px solid var(--primary-gold);
  color: var(--primary-gold);
}
.navbar-toggler-icon {
  background-image: url("data:image/svg+xml;charset=utf8,%3Csvg viewBox='0 0 30 30' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath stroke='rgba(212, 175, 55, 1)' stroke-width='2' stroke-linecap='round' stroke-miterlimit='10' d='M4 7h22M4 15h22M4 23h22'/%3E%3C/svg%3E");
}
/* // Gaya untuk tombol toggler navbar (mobile): border emas dan ikon hamburger kustom berwarna emas. */

.nav-item i {
  margin-left: 0.5rem;
  color: var(--primary-gold);
}
/* // Ikon dalam item navigasi diberi jarak dan warna emas. */

h1, h2, h3, h4, h5, h6 {
  font-family: 'Playfair Display', serif;
  color: var(--primary-gold);
  font-weight: 900;
  letter-spacing: 0.5px;
}
/* // Semua heading menggunakan font Playfair Display, warna emas, tebal, dan jarak antar huruf sedikit lebih lebar. */

.section-title {
  font-size: 3.5rem;
  font-weight: 900;
  margin-bottom: 3rem;
  position: relative;
  text-shadow: 0 2px 8px rgba(0, 0, 0, 0.5);
  background: linear-gradient(90deg, #d4af37, #e6c74a);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}
.section-title::after {
  content: '';
  display: block;
  width: 120px;
  height: 5px;
  background: linear-gradient(90deg, #d4af37, #e6c74a);
  margin: 1rem auto;
  box-shadow: 0 0 10px rgba(212, 175, 55, 0.5);
}
/* // Gaya untuk judul section: besar, gradien emas, bayangan teks, dan garis dekoratif emas di bawahnya. */

.btn-primary {
  background: linear-gradient(90deg, #d4af37, #e6c74a);
  border: none;
  color: #0f0f1a;
  border-radius: 50px;
  padding: 0.75rem 2rem;
  font-family: 'Playfair Display', serif;
  font-weight: 700;
  transition: all 0.3s ease;
  position: relative;
  overflow: hidden;
}
.btn-primary:hover {
  background: linear-gradient(90deg, #e6c74a, #d4af37);
  transform: translateY(-3px);
  box-shadow: 0 5px 15px rgba(212, 175, 55, 0.4);
}
/* // Tombol utama dengan gradien emas, sudut membulat, dan efek hover (mengangkat tombol dan bayangan). */

.btn-outline-light {
  border-color: var(--primary-gold);
  color: var(--primary-gold);
  border-radius: 50px;
  padding: 0.75rem 2rem;
  font-family: 'Playfair Display', serif;
  font-weight: 700;
  transition: all 0.3s ease;
}
.btn-outline-light:hover {
  background: linear-gradient(90deg, #d4af37, #e6c74a);
  color: #0f0f1a;
  transform: translateY(-3px);
  box-shadow: 0 5px 15px rgba(212, 175, 55, 0.4);
}
/* // Tombol outline dengan border emas, teks emas, dan efek hover serupa dengan tombol utama. */

.menu-item {
  background: rgba(30, 30, 47, 0.9);
  backdrop-filter: blur(15px);
  border: 1px solid rgba(212, 175, 55, 0.2);
  border-radius: 25px;
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.5);
  transition: all 0.5s ease;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  height: 100%;
}
.menu-item:hover {
  border: 1px solid #d4af37;
  box-shadow: 0 15px 30px rgba(212, 175, 55, 0.5), 0 0 20px rgba(212, 175, 55, 0.3);
  transform: translateY(-10px) scale(1.03);
}
/* // Kartu menu dengan latar semi-transparan, efek blur, dan border emas tipis. Saat hover, kartu terangkat dan membesar dengan bayangan emas. */

.menu-item .card-img-container {
  position: relative;
  overflow: hidden;
  height: 240px;
}
.menu-item .card-img-top {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 1s ease, filter 0.5s ease;
  filter: brightness(0.85) contrast(1.1);
}
.menu-item:hover .card-img-top {
  transform: scale(1.15);
  filter: brightness(1) contrast(1.2);
}
/* // Kontainer gambar kartu menu dengan tinggi tetap. Gambar menyesuaikan ukuran, dan saat hover, gambar membesar serta lebih cerah dan kontras. */

.menu-item .card-img-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(to bottom, rgba(212, 175, 55, 0.5), rgba(0, 0, 0, 0.9));
  opacity: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: opacity 0.6s ease;
}
.menu-item:hover .card-img-overlay {
  opacity: 1;
}
/* // Overlay pada gambar kartu menu, muncul saat hover dengan gradien emas ke hitam, menampilkan nama menu di tengah. */

.menu-item .card-img-overlay h5 {
  font-family: 'Playfair Display', serif;
  font-size: 1.6rem;
  color: var(--text-light);
  text-shadow: 0 2px 8px rgba(0, 0, 0, 0.8);
  transform: translateY(40px);
  transition: transform 0.6s ease;
}
.menu-item:hover .card-img-overlay h5 {
  transform: translateY(0);
}
/* // Teks pada overlay gambar dengan animasi muncul ke atas saat hover. */

.menu-item .card-body {
  padding: 2rem;
  flex-grow: 1;
  display: flex;
  flex-direction: column;
}
.menu-item .card-title {
  font-family: 'Playfair Display', serif;
  font-size: 2rem;
  font-weight: 900;
  color: var(--text-light);
  margin-bottom: 1rem;
  text-shadow: 0 1px 4px rgba(0, 0, 0, 0.5);
}
.menu-item .card-text {
  font-family: 'Roboto', sans-serif;
  font-size: 1rem;
  color: var(--text-light);
  margin-bottom: 1.5rem;
  line-height: 1.8;
  flex-grow: 1;
}
/* // Isi kartu menu: padding, judul dengan font Playfair Display, dan deskripsi dengan font Roboto. */

.menu-item .price {
  font-family: 'Roboto', sans-serif;
  font-size: 1.3rem;
  font-weight: bold;
  color: var(--primary-gold);
  margin-bottom: 1.5rem;
  position: relative;
}
.menu-item .price::before {
  content: '';
  display: block;
  width: 90px;
  height: 3px;
  background: linear-gradient(to right, #d4af37, transparent);
  margin-bottom: 0.75rem;
}
/* // Harga pada kartu menu dengan garis dekoratif emas di atasnya. */

.menu-item .btn-container {
  margin-top: auto;
  text-align: center;
}
.btn-secondary {
  background: rgba(51, 51, 51, 0.9);
  border: 1px solid var(--primary-gold);
  color: var(--primary-gold);
  font-family: 'Playfair Display', serif;
  font-weight: 700;
  font-size: 1rem;
  padding: 0.7rem 2rem;
  cursor: not-allowed;
}
.btn-secondary:hover {
  animation: shake 0.5s ease;
}
@keyframes shake {
  0%, 100% { transform: translateX(0); }
  20%, 60% { transform: translateX(-4px); }
  40%, 80% { transform: translateX(4px); }
}
/* // Tombol sekunder untuk item habis stok dengan efek goyang saat hover dan kursor non-aktif. */

#particles-bg {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 0;
}
/* // Elemen untuk latar belakang animasi partikel di section menu. */

.bg-dark {
  background: linear-gradient(135deg, #0f0f1a 0%, #1e1e2f 100%) !important;
  position: relative;
  overflow: hidden;
}
.bg-dark::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: radial-gradient(circle, rgba(212, 175, 55, 0.1) 0%, transparent 70%);
  z-index: 0;
}
/* // Latar belakang section menu dengan gradien gelap dan efek radial emas. */

.filter-btn {
  background: transparent;
  border: 2px solid var(--primary-gold);
  color: var(--primary-gold);
  border-radius: 50px;
  padding: 0.6rem 1.8rem;
  font-family: 'Playfair Display', serif;
  font-weight: 700;
  margin: 0.5rem;
  transition: all 0.3s ease;
}
.filter-btn:hover, .filter-btn.active {
  background: linear-gradient(90deg, #d4af37, #e6c74a);
  color: #0f0f1a;
  transform: scale(1.05);
  box-shadow: 0 0 10px rgba(212, 175, 55, 0.3);
}
/* // Tombol filter kategori dengan border emas dan efek hover gradien emas. */

.pagination .page-link {
  background: var(--light-bg);
  color: var(--primary-gold);
  border: 1px solid var(--primary-gold);
  margin: 0 0.3rem;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
}
.pagination .page-link:hover, .pagination .page-item.active .page-link {
  background: linear-gradient(90deg, #d4af37, #e6c74a);
  color: #0f0f1a;
  transform: scale(1.1);
  box-shadow: 0 0 8px rgba(212, 175, 55, 0.4);
}
/* // Tombol paginasi berbentuk lingkaran dengan efek hover dan aktif serupa tombol filter. */

#loginModal {
  backdrop-filter: blur(8px);
}
#loginModal .modal-content {
  background: rgba(15, 15, 26, 0.95);
  backdrop-filter: blur(15px);
  border: 1px solid #d4af37;
  border-radius: 20px;
  box-shadow: 0 0 20px rgba(212, 175, 55, 0.3);
}
/* // Modal reservasi dengan latar belakang semi-transparan, efek blur, dan border emas. */

#loginModal .modal-header {
  border-bottom: none;
}
#loginModal .modal-title {
  color: #d4af37;
  font-size: 1.8rem;
  text-shadow: 0 1px 4px rgba(0, 0, 0, 0.5);
}
#loginModal .modal-body p {
  color: #ffffff;
  font-size: 1.1rem;
}
#loginModal .modal-footer {
  border-top: none;
}
/* // Mengatur header, judul, dan isi modal, menghapus garis pemisah default. */

#loginModal .btn-secondary {
  background-color: #333333;
  border: 1px solid #d4af37;
  color: #d4af37;
  transition: all 0.3s ease;
}
#loginModal .btn-secondary:hover {
  background-color: #444444;
  color: #e6c74a;
}
#loginModal .btn-primary {
  background: linear-gradient(90deg, #d4af37, #e6c74a);
  border: none;
  color: #0f0f1a;
  transition: all 0.3s ease;
}
#loginModal .btn-primary:hover {
  background: linear-gradient(90deg, #e6c74a, #d4af37);
  transform: translateY(-3px);
  box-shadow: 0 5px 15px rgba(212, 175, 55, 0.4);
}
/*  Tombol di dalam modal dengan gaya konsisten tema emas. */

.back-to-index {
  display: block;
  margin: 3rem auto;
  background: linear-gradient(90deg, #d4af37, #e6c74a);
  border: 2px solid #d4af37;
  color: #0f0f1a;
  border-radius: 50px;
  padding: 0.6rem 1.8rem;
  font-family: 'Playfair Display', serif;
  font-weight: 700;
  font-size: 1.1rem;
  text-align: center;
  position: relative;
  overflow: hidden;
  transition: all 0.5s ease;
  box-shadow: 0 0 10px rgba(212, 175, 55, 0.4);
}
.back-to-index::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
  transition: 0.8s;
}
.back-to-index:hover::before {
  left: 100%;
}
.back-to-index:hover {
  background: linear-gradient(90deg, #e6c74a, #d4af37);
  transform: translateY(-5px) scale(1.05);
  box-shadow: 0 8px 20px rgba(212, 175, 55, 0.6);
  color: #0f0f1a;
}
.back-to-index i {
  margin-right: 0.5rem;
  transition: transform 0.3s ease;
}
.back-to-index:hover i {
  transform: translateX(-5px);
}
 /*Tombol "Back to Home" dengan efek gradien emas, animasi kilau saat hover, dan ikon panah yang bergerak ke kiri. */

@media (max-width: 768px) {
  .section-title {
    font-size: 2.8rem;
  }
  .menu-item .card-img-container {
    height: 200px;
  }
  .menu-item .card-title {
    font-size: 1.8rem;
  }
  .menu-item .card-text {
    font-size: 0.95rem;
  }
  .menu-item .price {
    font-size: 1.2rem;
  }
  .filter-btn {
    padding: 0.5rem 1.5rem;
    font-size: 0.95rem;
  }
  .back-to-index {
    font-size: 0.95rem;
    padding: 0.5rem 1.5rem;
  }
}
@media (max-width: 576px) {
  .section-title {
    font-size: 2.2rem;
  }
  .menu-item .card-img-container {
    height: 180px;
  }
  .menu-item .card-body {
    padding: 1.5rem;
  }
  .menu-item .card-title {
    font-size: 1.5rem;
  }
  .menu-item .card-text {
    font-size: 0.9rem;
  }
  .menu-item .price {
    font-size: 1.1rem;
  }
  .filter-btn {
    padding: 0.4rem 1.2rem;
    font-size: 0.9rem;
  }
  .back-to-index {
    font-size: 0.9rem;
    padding: 0.4rem 1.2rem;
  }
}
/* Media queries untuk responsivitas, menyesuaikan ukuran font, padding, dan tinggi gambar untuk tablet dan ponsel.*/

</style>

<?php
include '../includes/config.php';
// Memasukkan file konfigurasi (biasanya untuk koneksi database).

// Pagination settings
$items_per_page = 6;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $items_per_page;
// Mengatur paginasi: 6 item per halaman, halaman saat ini dari parameter GET, dan offset untuk query.

// Filter settings
$category = isset($_GET['category']) ? $conn->real_escape_string($_GET['category']) : 'all';
$where_clause = ($category !== 'all') ? "WHERE tersedia = 1 AND kategori = '$category'" : "WHERE tersedia = 1";
// Mengatur filter kategori berdasarkan parameter GET, dengan sanitasi untuk mencegah SQL injection.

// Fetch total items for pagination
$total_query = $conn->query("SELECT COUNT(*) as total FROM menu $where_clause");
$total_items = $total_query->fetch_assoc()['total'];
$total_pages = ceil($total_items / $items_per_page);
// Menghitung total item dan jumlah halaman untuk paginasi.

// Fetch menu items
$menus = [];
$query = "SELECT nama_menu, deskripsi, harga, stok, gambar_menu, kategori FROM menu $where_clause LIMIT $items_per_page OFFSET $offset";
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $menus[] = $row;
    }
    $result->free();
}
// Mengambil data menu dari database berdasarkan filter dan paginasi, menyimpan hasil dalam array $menus.

// Fetch categories for filter
$categories = [];
$cat_query = $conn->query("SELECT DISTINCT kategori FROM menu WHERE tersedia = 1");
if ($cat_query) {
    while ($row = $cat_query->fetch_assoc()) {
        $categories[] = $row['kategori'];
    }
    $cat_query->free();
}
// Mengambil daftar kategori unik untuk tombol filter.

?>

<!-- Menu Section -->
<section class="py-5 bg-dark position-relative" id="menu">
  <div id="particles-bg"></div>
  <div class="container position-relative">
    <!-- // Section menu dengan latar belakang gelap dan elemen untuk animasi partikel. -->

    <h2 class="text-center section-title" data-aos="fade-down">Our Full Menu</h2>
    <!-- // Judul section dengan animasi AOS fade-down. -->

    <!-- Filter Buttons -->
    <div class="text-center mb-5" data-aos="fade-up">
      <a href="?page=1&category=all" class="filter-btn <?php echo $category === 'all' ? 'active' : ''; ?>" data-aos="zoom-in">All</a>
      <?php foreach ($categories as $cat): ?>
        <a href="?page=1&category=<?php echo urlencode($cat); ?>" class="filter-btn <?php echo $category === $cat ? 'active' : ''; ?>" data-aos="zoom-in" data-aos-delay="<?php echo (array_search($cat, $categories) + 1) * 100; ?>">
          <?php echo htmlspecialchars($cat); ?>
        </a>
      <?php endforeach; ?>
    </div>
    <!-- // Tombol filter untuk semua kategori dan kategori spesifik, dengan animasi AOS dan status aktif. -->

    <!-- Menu Items -->
    <div class="row g-4">
      <?php
      if (!empty($menus)) {
        foreach ($menus as $index => $menu) {
          $gambar = !empty($menu['gambar_menu']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $menu['gambar_menu']) 
              ? htmlspecialchars('/' . $menu['gambar_menu']) 
              : '/img/default-menu.jpg';
          $alt_text = htmlspecialchars($menu['nama_menu']) . ' - ' . substr(htmlspecialchars($menu['deskripsi']), 0, 50) . '...';
          $button_text = $menu['stok'] > 0 
              ? '<a href="#" class="btn btn-primary rounded-pill px-4 order-btn" data-bs-toggle="modal" data-bs-target="#loginModal">Order Now</a>' 
              : '<button class="btn btn-secondary rounded-pill px-4" disabled>Sold Out</button>';
          // Menentukan gambar (default jika tidak ada), teks alt, dan tombol berdasarkan stok.

          echo '<div class="col-lg-4 col-md-6" data-aos="zoom-in" data-aos-delay="' . ($index * 150) . '">
            <div class="card h-100 shadow menu-item" data-tilt data-tilt-max="10" data-tilt-speed="400" data-tilt-perspective="1000">
              <div class="card-img-container">
                <img src="' . $gambar . '" class="card-img-top lazy" alt="' . $alt_text . '" loading="lazy">
                <div class="card-img-overlay">
                  <h5>' . htmlspecialchars($menu['nama_menu']) . '</h5>
                </div>
              </div>
              <div class="card-body p-4">
                <h5 class="card-title fw-bold">' . htmlspecialchars($menu['nama_menu']) . '</h5>
                <p class="card-text">' . htmlspecialchars($menu['deskripsi']) . '</p>
                <p class="price">Rp ' . number_format($menu['harga'], 0, ',', '.') . '</p>
                <div class="btn-container">
                  ' . $button_text . '
                </div>
              </div>
            </div>
          </div>';
          // Menampilkan kartu menu dengan gambar, overlay, judul, deskripsi, harga, dan tombol.
        }
      } else {
        echo '<div class="col-12 text-center text-muted" role="alert">No menus available in this category.</div>';
        // Pesan jika tidak ada menu dalam kategori yang dipilih.
      }
      ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
      <nav aria-label="Menu pagination" class="mt-5" data-aos="fade-up">
        <ul class="pagination justify-content-center">
          <?php if ($page > 1): ?>
            <li class="page-item">
              <a class="page-link" href="?page=<?php echo $page - 1; ?>&category=<?php echo urlencode($category); ?>" aria-label="Previous">
                <span aria-hidden="true">«</span>
              </a>
            </li>
          <?php endif; ?>
          <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
              <a class="page-link" href="?page=<?php echo $i; ?>&category=<?php echo urlencode($category); ?>"><?php echo $i; ?></a>
            </li>
          <?php endfor; ?>
          <?php if ($page < $total_pages): ?>
            <li class="page-item">
              <a class="page-link" href="?page=<?php echo $page + 1; ?>&category=<?php echo urlencode($category); ?>" aria-label="Next">
                <span aria-hidden="true">»</span>
              </a>
            </li>
          <?php endif; ?>
        </ul>
      </nav>
    <?php endif; ?>
    <!-- // Navigasi paginasi dengan tombol Previous, nomor halaman, dan Next, hanya ditampilkan jika ada lebih dari satu halaman. -->

    <!-- Refined Back to Index Button -->
    <a href="../customer.php" class="back-to-index" data-aos="fade-up">
      <i class="fas fa-arrow-left"></i> Back to Home
    </a>
    <!-- // Tombol kembali ke halaman utama dengan ikon panah dan animasi AOS. -->

  </div>
</section>

<!-- Reservation Notification Modal -->
<div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="loginModalLabel">Make a Reservation</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Ready to secure your spot? Click below to make a reservation for an exclusive dining experience.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Close</button>
        <a href="./reservasi.php" class="btn btn-primary rounded-pill">Reserve Now</a>
      </div>
    </div>
  </div>
</div>
<!-- // Modal untuk notifikasi reservasi, muncul saat tombol "Order Now" diklik, dengan tombol Close dan Reserve Now. -->

<?php include '../includes/footer.php'; ?>
<!-- // Memasukkan file footer.php, biasanya berisi elemen penutup seperti </body> dan </html>. -->

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
<!-- // Memuat JavaScript Bootstrap 5 untuk komponen interaktif seperti modal. -->

<!-- AOS JS -->
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<!-- // Memuat library AOS untuk animasi saat scroll. -->

<!-- Particles.js -->
<script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
<!-- // Memuat Particles.js untuk animasi partikel di latar belakang. -->

<!-- Vanilla Tilt -->
<script src="https://cdn.jsdelivr.net/npm/vanilla-tilt@1.8.1/dist/vanilla-tilt.min.js"></script>
<!-- // Memuat Vanilla Tilt untuk efek kemiringan pada kartu menu. -->

<script>
  AOS.init({
    duration: 1000,
    once: true,
    easing: 'ease-out'
  });
  // Menginisialisasi AOS dengan durasi animasi 1 detik, sekali per elemen, dan efek easing halus.

  // Particles.js for menu section
  if (window.innerWidth > 576) {
    particlesJS('particles-bg', {
      particles: {
        number: { value: 80, density: { enable: true, value_area: 800 } },
        color: { value: ['#d4af37', '#e6c74a'] },
        shape: { type: ['circle', 'triangle', 'star'], stroke: { width: 0 } },
        opacity: { value: 0.6, random: true },
        size: { value: 4, random: true },
        line_linked: {
          enable: true,
          distance: 150,
          color: '#d4af37',
          opacity: 0.3,
          width: 1.5
        },
        move: {
          enable: true,
          speed: 2,
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
        },
        modes: {
          grab: { distance: 200, line_linked: { opacity: 0.5 } },
          push: { particles_nb: 4 }
        }
      },
      retina_detect: true
    });
  }
  // Animasi partikel di latar belakang section menu, hanya aktif pada layar > 576px, dengan partikel emas berbentuk lingkaran, segitiga, atau bintang.

  // Ripple effect on button click
  document.querySelectorAll('.btn-primary, .back-to-index').forEach(btn => {
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
  // Efek riak saat tombol utama atau "Back to Home" diklik, dengan lingkaran menyebar dari titik klik.

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
  // Lazy loading untuk gambar dengan kelas 'lazy', memuat gambar hanya saat masuk ke viewport.

  // Navbar shadow on scroll (removed since navbar is deleted, but kept for reference)
  window.addEventListener('scroll', () => {
    const navbar = document.querySelector('.navbar');
    if (navbar && window.scrollY > 50) {
      navbar.style.boxShadow = '0 4px 10px rgba(0, 0, 0, 0.3)';
    } else if (navbar) {
      navbar.style.boxShadow = '0 2px 5px rgba(0, 0, 0, 0.2)';
    }
  });
  // Kode untuk bayangan navbar saat scroll, meskipun navbar dihapus (disimpan untuk referensi).
</script>