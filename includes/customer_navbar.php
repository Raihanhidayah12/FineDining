<?php
// Get the current page name
$current_page = basename($_SERVER['PHP_SELF']);

// Fetch customer profile picture
session_start();
include './includes/config.php'; // Adjust path if necessary

$profile_picture = null;
$default_picture = '../pages/uploads/default_profile.jpg'; // Fixed default image path
if (isset($_SESSION['id_user'])) {
    $user_id = $_SESSION['id_user'];
    $query_customer = "SELECT gambar_user FROM customer WHERE id_user = ?";
    if ($stmt_customer = mysqli_prepare($conn, $query_customer)) {
        mysqli_stmt_bind_param($stmt_customer, "i", $user_id);
        mysqli_stmt_execute($stmt_customer);
        $result_customer = mysqli_stmt_get_result($stmt_customer);
        if (mysqli_num_rows($result_customer) > 0) {
            $customer = mysqli_fetch_assoc($result_customer);
            $profile_picture = $customer['gambar_user'];
            // Debug: Output profile picture path and check if file exists
            echo "<!-- Debug: profile_picture = " . htmlspecialchars($profile_picture ?? 'NULL') . ", id_user = " . htmlspecialchars($user_id) . ", file_exists = " . (file_exists($profile_picture ?? '') ? 'true' : 'false') . " -->";
        } else {
            echo "<!-- Debug: No customer found for id_user = " . htmlspecialchars($user_id) . " -->";
        }
        mysqli_stmt_close($stmt_customer);
    } else {
        echo "<!-- Debug: Query preparation failed: " . mysqli_error($conn) . " -->";
    }
} else {
    echo "<!-- Debug: id_user not set in session -->";
}

// Use relative path adjusted for includes directory
$base_path = './pages/pages/uploads/'; // Direct path to uploads from includes/
$display_picture = $profile_picture ? ($base_path . basename($profile_picture)) : $default_picture;
?>

<style>
  /* Sticky & transparent navbar */
  .navbar-custom {
    background-color: rgba(0, 0, 0, 0.3) !important;
    backdrop-filter: blur(6px);
    position: fixed;
    top: 0;
    width: 100%;
    z-index: 999;
    transition: background-color 0.3s ease;
  }

  .navbar-custom .nav-link,
  .navbar-custom .navbar-brand {
    color: #fff !important;
  }

  .navbar-custom .nav-link:hover {
    color: #d4af37 !important;
  }

  /* Active nav-link style */
  .navbar-custom .nav-link.active {
    color: #d4af37 !important;
    font-weight: bold;
  }

  /* Profile picture styling */
  .profile-picture-nav {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #d4af37;
  }

    /* Logo styling */
  .navbar-custom .navbar-brand img {
    height: 60px; /* Mengecilkan logo dari 40px menjadi 30px */
    width: auto;
    vertical-align: middle;
  }
</style>

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<!-- Customer Navbar with Logout -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom">
  <div class="container">
    <a class="navbar-brand fw-bold text-warning" href="customer.php">
    <img src="assets/img/logo.png" alt="FineDining Logo"> <!-- Ganti dengan path logo Anda -->  
    FineDining</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <ul class="navbar-nav align-items-center">
        <li class="nav-item">
          <a class="nav-link <?php echo $current_page == 'customer.php' ? 'active' : ''; ?>" href="customer.php">Home</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo $current_page == 'reserve.php' ? 'active' : ''; ?>" href="/pages/reservasi.php">Reserve</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo $current_page == 'menu.php' ? 'active' : ''; ?>" href="/pages/menu.php">Menu</a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo $current_page == 'myorder.php' ? 'active' : ''; ?>" href="/pages/myorder.php">My Orders</a>
        </li>

        <li class="nav-item">
          <a class="nav-link <?php echo $current_page == '/pages/profile.php' ? 'active' : ''; ?>" href="/pages/profile.php">
            <img src="<?php echo htmlspecialchars($display_picture) . '?t=' . time(); ?>" alt="Profile Picture" class="profile-picture-nav">
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?php echo $current_page == 'logout_customer.php' ? 'active' : ''; ?>" href="./logout_customer.php"><i class="bi bi-box-arrow-right fs-5"></i></a>
        </li>
      </ul>
    </div>
  </div>
</nav>