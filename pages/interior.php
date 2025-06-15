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

$base_url = 'http://' . $_SERVER['HTTP_HOST'] . '/';
$fallback_image = '/img/default-menu.jpg';
if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $fallback_image)) {
    $fallback_image = 'https://via.placeholder.com/80x80.png?text=Image+Not+Found';
} else {
    $fallback_image = $base_url . $fallback_image;
}

$nama_area = isset($_GET['nama_area']) ? htmlspecialchars(urldecode($_GET['nama_area'])) : '';
$nomor_meja = isset($_GET['nomor_meja']) ? htmlspecialchars($_GET['nomor_meja']) : '';
$kapasitas = isset($_GET['kapasitas']) ? htmlspecialchars($_GET['kapasitas']) : '';
$date = isset($_GET['date']) ? htmlspecialchars($_GET['date']) : '';
$time = isset($_GET['time']) ? htmlspecialchars($_GET['time']) : '';
$food = isset($_GET['food']) ? explode(',', $_GET['food']) : [];
$drinks = isset($_GET['drinks']) ? explode(',', $_GET['drinks']) : [];
$dessert = isset($_GET['dessert']) ? explode(',', $_GET['dessert']) : [];

$areas = [];
if ($conn) {
    $result = $conn->query("SELECT nama_area, gambar_area FROM area WHERE tersedia = 1 AND nama_area = '$nama_area'");
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $gambar_area = !empty($row['gambar_area']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $row['gambar_area']) ? $base_url . $row['gambar_area'] : $fallback_image;
        $areas[$nama_area] = $gambar_area;
    }
} else {
    die("Database connection failed.");
}

$food_display = !empty($food) ? implode(', ', array_filter($food)) : 'None selected';
$drinks_display = !empty($drinks) ? implode(', ', array_filter($drinks)) : 'None selected';
$dessert_display = !empty($dessert) ? implode(', ', array_filter($dessert)) : 'None selected';

date_default_timezone_set('Asia/Jakarta');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customize Interior || FineDining</title>
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
            --text-light: #f1f5f9;
            --text-muted: #d3d4db;
            --text-highlight: #ffcc00;
            --shadow: 0 10px 20px rgba(0, 0, 0, 0.5);
            --glass-bg: rgba(255, 255, 255, 0.05);
            --button-bg: #ff8c00;
            --button-bg-hover: #ffa500;
        }

         html, body {
      margin: 0;
      padding: 0;
      overflow-x: hidden !important;
    }
    * {
      box-sizing: border-box;
    }
    body::-webkit-scrollbar {
      display: none;
    }

        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(to bottom, var(--dark-bg) 0%, var(--light-bg) 70%);
            color: var(--text-light);
            overflow-y: auto;
            min-height: 100vh;
        }

        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.1);
            z-index: -1;
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 900;
            color: var(--text-highlight);
            text-shadow: 0 2px 15px rgba(255, 204, 0, 0.3);
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

        .card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(212, 175, 55, 0.1);
            border-radius: 15px;
            overflow: hidden;
            transition: transform 0.5s ease, box-shadow 0.5s ease;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(212, 175, 55, 0.2);
            border: 1px solid rgba(212, 175, 55, 0.3);
        }

/* Tambahkan atau ubah di dalam <style> */
.form-select option {
    background: rgba(30, 41, 59, 0.8); /* Latar belakang opsi lebih terang */
    color: var(--text-light); /* Teks lebih jelas */
}

.form-select option:disabled {
    background: rgba(75, 85, 99, 0.5); /* Warna abu-abu lebih lembut untuk opsi default */
    color: var(--text-muted); /* Teks abu-abu lebih halus */
}

/* Tambahan penting */
.form-select option {
    color: white !important;         /* Pastikan teks putih */
    background-color: #1e293b !important; /* Warna latar belakang agar kontras */
}

/* Saat option sedang dipilih (beberapa browser hanya render ini saat dropdown aktif) */
.form-select:focus option {
    color: white !important;
    background-color: #1e293b !important;
}

/* Agar teks default juga putih */
.form-select {
    color: white !important;
}


  /* Ubah warna placeholder menjadi putih */
  #specialRequests::placeholder {
    color: white;
    opacity: 1; /* Penting agar warnanya tidak transparan */
  }

  /* Untuk kompatibilitas browser (optional tapi disarankan) */
  #specialRequests:-ms-input-placeholder { color: white; } /* IE 10+ */
  #specialRequests::-ms-input-placeholder { color: white; } /* Edge */

  /* Ubah warna teks dan background textarea jika perlu */
  #specialRequests {
    color: white;
    background-color: #222; /* Ubah sesuai kebutuhan */
    border: 1px solid #555; /* Opsional, biar kelihatan */
  }

        .form-select, .form-control, textarea {
            background: rgba(15, 23, 42, 0.9); /* Pastikan latar belakang cukup kontras */
            border: 1px solid var(--primary-gold);
            color: var(--text-light); /* Pastikan warna teks terlihat */
            border-radius: 10px;
            padding: 1rem;
            font-size: 1.1rem;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            resize: vertical; /* Izinkan resize vertikal saja */
        }

        .form-select:focus, .form-control:focus, textarea:focus {
            border-color: var(--accent-gold);
            box-shadow: 0 0 15px rgba(212, 175, 55, 0.3);
            background: rgba(15, 23, 42, 0.6); /* Sedikit transparan saat fokus */
            color: var(--text-light); /* Pastikan teks tetap terlihat saat fokus */
        }

        .form-label {
            font-family: 'Playfair Display', serif;
            color: var(--text-highlight);
            font-weight: 700;
            font-size: 1.2rem;
        }

        .text-anchor {
            color: var(--text-highlight);
            font-weight: 700;
        }

        p strong {
            color: var(--text-highlight);
            font-weight: 600;
            text-shadow: 0 1px 5px rgba(255, 204, 0, 0.2);
        }

        p {
            color: var(--text-light);
            font-size: 1.1rem;
            line-height: 1.6;
            transition: color 0.3s ease;
        }

        p:hover {
            color: var(--accent-gold);
        }

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

        .container {
            padding-bottom: 6rem;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .button-container {
            margin-top: auto;
            padding-top: 2rem;
            text-align: center;
        }

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

        @media (max-width: 768px) {
            .section-title { font-size: 2rem; }
            .btn-primary, .btn-back { padding: 10px 25px; font-size: 1.2rem; }
        }

        @media (max-width: 576px) {
            .section-title { font-size: 1.8rem; }
            .btn-primary, .btn-back { padding: 8px 20px; font-size: 1rem; }
            .custom-alert { padding: 10px 15px; font-size: 0.9rem; max-width: 70%; }
        }
    </style>
    <link rel="icon" href="/assets/img/logo.png" type="image/gif" sizes="16x16">
</head>
<body>
    <div class="overlay"></div>

    <section class="container py-5 position-relative">
        <h1 class="section-title text-center">Customize Interior</h1>
        <div class="row g-4">
            <div class="col-12" data-aos="fade-up">
                <div class="card p-4">
                    <h4 class="text-anchor mb-4">Reservation Details</h4>
                    <p><strong>Seating Area:</strong> <?php echo $nama_area ?: 'Not selected'; ?></p>
                    <p><strong>Table Number:</strong> <?php echo $nomor_meja ?: 'Not selected'; ?></p>
                    <p><strong>Capacity:</strong> <?php echo $kapasitas ?: 'Not selected'; ?> persons</p>
                    <p><strong>Date:</strong> <?php echo $date ?: 'Not selected'; ?></p>
                    <p><strong>Time:</strong> <?php echo $time ?: 'Not selected'; ?></p>
                    <h5 class="text-anchor mt-4">Pre-order Menu</h5>
                    <p><strong>Makanan:</strong> <?php echo $food_display; ?></p>
                    <p><strong>Minuman:</strong> <?php echo $drinks_display; ?></p>
                    <p><strong>Dessert:</strong> <?php echo $dessert_display; ?></p>
                </div>
            </div>

            <div class="col-12" data-aos="fade-up">
                <div class="card p-4">
                    <h4 class="text-anchor mb-4">Interior Customization</h4>
                    <div class="mb-3">
                        <label class="form-label">Decoration Theme</label>
                        <select class="form-select" id="decorationTheme" aria-label="Select Decoration Theme">
                            <option value="">Select Theme</option>
                            <option value="classic">Classic</option>
                            <option value="modern">Modern</option>
                            <option value="romantic">Romantic</option>
                            <option value="festive">Festive</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Special Requests</label>

<textarea class="form-control" id="specialRequests" rows="4"
  placeholder="Enter any special requests (e.g., flowers, candles, etc.)"></textarea>                    </div>
                    <div id="previewImage" class="mt-4 text-center" style="display: none;">
                        <img src="<?php echo isset($areas[$nama_area]) ? $areas[$nama_area] : $fallback_image; ?>" class="card-img-top" alt="Interior Preview" id="interiorPreview" style="max-width: 100%; border-radius: 10px;">
                    </div>
                </div>
            </div>

            <div class="col-12 button-container" data-aos="fade-up">
                <button class="btn btn-back" onclick="goBack()">
                    <i class="fas fa-arrow-left me-2"></i> Back
                </button>
                <button class="btn btn-primary" onclick="submitInterior()">
                    Next <i class="fas fa-arrow-right ms-2"></i>
                </button>
            </div>
        </div>
    </section>

    <div id="customAlert" class="custom-alert">
        <p id="alertMessage">Please select a decoration theme and fill in any special requests to proceed.</p>
        <button onclick="closeAlert()">OK</button>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.11.4/gsap.min.js"></script>
    <script>
        AOS.init({ duration: 1000, once: true });

function goBack() {
    const params = new URLSearchParams(window.location.search).toString();
    window.history.back();
}

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

        function closeAlert() {
            const alert = document.getElementById('customAlert');
            if (alert) {
                gsap.to(alert, { opacity: 0, scale: 0.9, duration: 0.5, ease: 'power2.out', onComplete: () => {
                    alert.style.display = 'none';
                    alert.style.opacity = 1;
                }});
            }
        }

        function submitInterior() {
            const decorationTheme = document.getElementById('decorationTheme').value;
            const specialRequests = document.getElementById('specialRequests').value.trim();

            if (!decorationTheme) {
                showAlert('Please select a decoration theme to proceed.');
                return;
            }

            const interiorData = {
                nama_area: '<?php echo $nama_area; ?>',
                nomor_meja: '<?php echo $nomor_meja; ?>',
                kapasitas: '<?php echo $kapasitas; ?>',
                date: '<?php echo $date; ?>',
                time: '<?php echo $time; ?>',
                food: '<?php echo implode(',', $food); ?>',
                drinks: '<?php echo implode(',', $drinks); ?>',
                dessert: '<?php echo implode(',', $dessert); ?>',
                decorationTheme: decorationTheme,
                specialRequests: specialRequests
            };

            const params = new URLSearchParams(interiorData).toString();
            window.location.href = `./confirmation.php?${params}`;
        }

        document.getElementById('decorationTheme').addEventListener('change', function() {
            const previewImage = document.getElementById('previewImage');
            const interiorPreview = document.getElementById('interiorPreview');
            const theme = this.value;
            if (theme) {
                interiorPreview.src = '<?php echo isset($areas[$nama_area]) ? $areas[$nama_area] : $fallback_image; ?>';
                previewImage.style.display = 'block';
                gsap.fromTo(previewImage, { opacity: 0, scale: 0.95 }, { opacity: 1, scale: 1, duration: 0.7, ease: 'power2.out' });
            } else {
                previewImage.style.display = 'none';
            }
        });

        // Tambahan: Pastikan teks di textarea terlihat saat diketik
        document.getElementById('specialRequests').addEventListener('input', function() {
            if (!this.value) {
                this.style.color = 'var(--text-light)';
            }
            console.log('Text input: ' + this.value); // Untuk debugging
        });
    </script>
</body>
</html>