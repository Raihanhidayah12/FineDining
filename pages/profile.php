<?php
session_start();
include '../includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['id_user'])) {
    header("Location: ../login.php");
    exit();
}

$success_message = "";
$error_message = "";
$customer = null;

// Fetch customer data
$user_id = $_SESSION['id_user'];
$query_customer = "SELECT id_customer, nama_lengkap, email, no_hp, gambar_user FROM customer WHERE id_user = ?";
if ($stmt_customer = mysqli_prepare($conn, $query_customer)) {
    mysqli_stmt_bind_param($stmt_customer, "i", $user_id);
    mysqli_stmt_execute($stmt_customer);
    $result_customer = mysqli_stmt_get_result($stmt_customer);
    if (mysqli_num_rows($result_customer) > 0) {
        $customer = mysqli_fetch_assoc($result_customer);
        $_SESSION['email'] = $customer['email'];
    } else {
        $error_message = "Data pelanggan tidak ditemukan.";
    }
    mysqli_stmt_close($stmt_customer);
} else {
    $error_message = "Gagal mempersiapkan pernyataan customer: " . mysqli_error($conn);
}

// Handle profile update and picture upload via AJAX
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_lengkap = $_POST['nama_lengkap'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';

    // Validate text inputs
    if (empty($nama_lengkap) || empty($email) || empty($phone)) {
        $error_message = "Semua kolom teks harus diisi.";
    } else {
        // Update customer profile
        $update_customer = "UPDATE customer SET nama_lengkap = ?, email = ?, no_hp = ? WHERE id_customer = ?";
        if ($stmt_update = mysqli_prepare($conn, $update_customer)) {
            mysqli_stmt_bind_param($stmt_update, "sssi", $nama_lengkap, $email, $phone, $customer['id_customer']);
            if (mysqli_stmt_execute($stmt_update)) {
                // Update user email if changed
                if ($email !== $_SESSION['email']) {
                    $update_user = "UPDATE user SET email = ? WHERE id_user = ?";
                    if ($stmt_user = mysqli_prepare($conn, $update_user)) {
                        mysqli_stmt_bind_param($stmt_user, "si", $email, $user_id);
                        if (mysqli_stmt_execute($stmt_user)) {
                            $_SESSION['email'] = $email;
                        } else {
                            $error_message = "Gagal memperbarui email pengguna: " . mysqli_error($conn);
                        }
                        mysqli_stmt_close($stmt_user);
                    } else {
                        $error_message = "Gagal mempersiapkan pernyataan update user: " . mysqli_error($conn);
                    }
                }
                $success_message = "Profil berhasil diperbarui.";
                $customer['nama_lengkap'] = $nama_lengkap;
                $customer['email'] = $email;
                $customer['no_hp'] = $phone;
            } else {
                $error_message = "Gagal memperbarui profil: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt_update);
        } else {
            $error_message = "Gagal mempersiapkan pernyataan update: " . mysqli_error($conn);
        }
    }

    // Handle profile picture upload if provided
    $new_image_path = $customer['gambar_user'];
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $max_size = 50 * 1024 * 1024; // 50MB
        $upload_dir = 'pages/uploads/';
        $file = $_FILES['profile_picture'];

        // Validate file size (server-side as a fallback)
        if ($file['size'] > $max_size) {
            $error_message = $error_message ? $error_message . " Ukuran file maksimal 50MB." : "Ukuran file maksimal 50MB.";
        } else {
            // Validate that the file is an image using getimagesize
            $image_info = @getimagesize($file['tmp_name']);
            if ($image_info === false) {
                $error_message = $error_message ? $error_message . " File harus berupa gambar." : "File harus berupa gambar.";
            } else {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = uniqid('profile_') . '.' . strtolower($ext);
                $destination = $upload_dir . $filename;

                // Ensure the upload directory exists and is writable
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    // Delete old profile picture if exists
                    if ($customer['gambar_user'] && file_exists($customer['gambar_user'])) {
                        unlink($customer['gambar_user']);
                    }
                    // Update database with relative path
                    $relative_path = 'pages/uploads/' . $filename;
                    $update_picture = "UPDATE customer SET gambar_user = ? WHERE id_customer = ?";
                    if ($stmt_picture = mysqli_prepare($conn, $update_picture)) {
                        mysqli_stmt_bind_param($stmt_picture, "si", $relative_path, $customer['id_customer']);
                        if (mysqli_stmt_execute($stmt_picture)) {
                            $success_message = $success_message ? $success_message . " Foto profil berhasil diperbarui." : "Foto profil berhasil diperbarui.";
                            $new_image_path = $relative_path;
                            $customer['gambar_user'] = $relative_path;
                        } else {
                            $error_message = $error_message ? $error_message . " Gagal memperbarui foto profil di database: " . mysqli_error($conn) : "Gagal memperbarui foto profil di database: " . mysqli_error($conn);
                        }
                        mysqli_stmt_close($stmt_picture);
                    } else {
                        $error_message = $error_message ? $error_message . " Gagal mempersiapkan pernyataan foto: " . mysqli_error($conn) : "Gagal mempersiapkan pernyataan foto: " . mysqli_error($conn);
                    }
                } else {
                    $error_message = $error_message ? $error_message . " Gagal mengunggah file. Periksa izin folder uploads." : "Gagal mengunggah file. Periksa izin folder uploads.";
                }
            }
        }
    }

    // If this is an AJAX request, return JSON response
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        $response = [
            'success' => empty($error_message),
            'message' => $success_message ?: $error_message,
            'image_path' => $new_image_path ?: '/pages/uploads/default_profile.jpg'
        ];
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/assets/img/logo.png" type="image/gif" sizes="16x16">
    <title>Profil Customer || FineDining</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- AOS Animation Library -->
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha384-3B6N4N7H5F7D1F2D6G8H9I0J9K5L2F3G4H5J6K7L8M9N0O1P2Q3R4S5T6U7V8W" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700;900&family=Roboto:wght@300;400&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gold: #d4af37;
            --dark-bg: #0f0f1a;
            --light-bg: #1e1e2f;
            --text-light: #ffffff;
            --text-muted: #d1d1d1;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Roboto', sans-serif;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(to right, #0f0f1a, #2c2c54);
            overflow-x: hidden;
            position: relative;
        }

        /* Hide scrollbar but keep scrollable */
        html, body {
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        html::-webkit-scrollbar {
            display: none;
        }

        .wrapper {
            width: 350px;
            background: rgba(30, 30, 47, 0.95);
            backdrop-filter: blur(12px);
            border-radius: 25px;
            padding: 30px 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.6);
            position: relative;
            z-index: 10;
            animation: wrapperEntrance 0.8s ease-out;
        }

        @keyframes wrapperEntrance {
            0% { opacity: 0; transform: scale(0.95) translateY(20px); }
            100% { opacity: 1; transform: scale(1) translateY(0); }
        }

        h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            font-weight: 900;
            color: var(--primary-gold);
            text-align: center;
            margin-bottom: 1.5rem;
            position: relative;
        }

        h2::after {
            content: '';
            display: block;
            width: 60px;
            height: 3px;
            background: linear-gradient(to right, transparent, var(--primary-gold), transparent);
            margin: 0.5rem auto;
        }

        .profile-picture {
            display: flex;
            justify-content: center;
            margin-bottom: 1.2rem;
        }

        .profile-picture img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary-gold);
            transition: transform 0.3s ease;
        }

        .profile-picture img:hover {
            transform: scale(1.05);
        }

        .input-group {
            position: relative;
            margin-bottom: 1.5rem;
            border-bottom: 2px solid var(--primary-gold);
        }

        .input-group label {
            position: absolute;
            top: 50%;
            left: 10px;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 0.9rem;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            pointer-events: none;
        }

        .input-group input {
            width: 100%;
            padding: 8px 10px;
            background: transparent;
            border: none;
            color: var(--text-light);
            outline: none;
            font-size: 0.9rem;
            transition: border-color 0.3s ease;
        }

        .input-group input:focus ~ label,
        .input-group input:valid ~ label {
            top: -10px;
            font-size: 0.75rem;
            color: var(--primary-gold);
        }

        .input-group input:invalid:focus {
            border-bottom-color: #dc3545;
        }

        .file-input {
            margin-bottom: 1.2rem;
            text-align: center;
        }

        .file-input label {
            display: inline-block;
            padding: 6px 18px;
            background: var(--primary-gold);
            color: #0f0f1a;
            border-radius: 50px;
            cursor: pointer;
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }

        .file-input label:hover {
            background: #e6c74a;
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(212, 175, 55, 0.5);
        }

        .file-input input[type="file"] {
            display: none;
        }

        button {
            width: 100%;
            padding: 10px;
            background: var(--primary-gold);
            color: #0f0f1a;
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            font-size: 0.9rem;
        }

        button:hover {
            background: #e6c74a;
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(212, 175, 55, 0.5);
        }

        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        button .spinner {
            display: none;
            border: 3px solid #0f0f1a;
            border-top: 3px solid transparent;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        button.loading .spinner {
            display: block;
        }

        button.loading span {
            display: none;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Ripple Effect */
        button .ripple {
            position: absolute;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            transform: scale(0);
            animation: ripple 0.6s linear;
            pointer-events: none;
        }

        @keyframes ripple {
            to { transform: scale(4); opacity: 0; }
        }

        .alert {
            padding: 10px;
            font-size: 0.8rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
        }

        .alert-success {
            background-color: rgba(212, 237, 218, 0.9);
            color: #155724;
        }

        .alert-danger {
            background-color: rgba(248, 215, 218, 0.9);
            color: #721c24;
            animation: shake 0.5s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-4px); }
            40%, 80% { transform: translateX(4px); }
        }

        .btn-back {
            display: block;
            margin: 1.2rem auto 0;
            width: fit-content;
            background: transparent;
            color: var(--primary-gold);
            border: 2px solid var(--primary-gold);
            padding: 6px 18px;
            border-radius: 50px;
            text-decoration: none;
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 0.85rem;
            transition: all 0.3s ease;
        }

        .btn-back:hover {
            background: var(--primary-gold);
            color: #0f0f1a;
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(212, 175, 55, 0.5);
        }

        /* Background Particles */
        #particles-js {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .wrapper {
                width: 85%;
                padding: 25px 20px;
            }

            h2 {
                font-size: 1.75rem;
            }
        }

        @media (max-width: 576px) {
            .wrapper {
                width: 90%;
                padding: 20px 15px;
            }

            h2 {
                font-size: 1.5rem;
            }

            .input-group input {
                font-size: 0.8rem;
                padding: 6px 8px;
            }

            .input-group label {
                font-size: 0.8rem;
            }

            button, .btn-back {
                padding: 8px;
                font-size: 0.8rem;
            }

            .alert {
                font-size: 0.75rem;
                padding: 8px;
            }

            .profile-picture img {
                width: 60px;
                height: 60px;
            }
        }
    </style>
</head>
<body>
    <div id="particles-js"></div>
    <div class="wrapper" data-aos="fade-up" data-aos-duration="800">
        <h2 data-aos="fade-down" data-aos-delay="100">Profil Pelanggan</h2>

        <div id="alert-container">
            <?php if ($success_message): ?>
                <div class="alert alert-success" data-aos="fade-up" data-aos-delay="150"><?php echo $success_message; ?></div>
            <?php elseif ($error_message): ?>
                <div class="alert alert-danger" data-aos="fade-up" data-aos-delay="150"><?php echo $error_message; ?></div>
            <?php endif; ?>
        </div>

        <?php if ($customer): ?>
            <div class="profile-picture" data-aos="fade-up" data-aos-delay="200">
                <img id="profile-image" src="<?php echo htmlspecialchars($customer['gambar_user'] ?: '/pages/uploads/default_profile.jpg'); ?>" alt="Foto Profil" onerror="this.src='/pages/uploads/default_profile.jpg';">
            </div>

            <form id="profile-form" enctype="multipart/form-data" data-aos="fade-up" data-aos-delay="250">
                <div class="file-input" data-aos="fade-up" data-aos-delay="300">
                    <input type="file" name="profile_picture" id="profile_picture" accept="image/*">
                    <label for="profile_picture">Pilih Foto Profil</label>
                </div>
                <div class="input-group" data-aos="fade-up" data-aos-delay="350">
                    <input type="text" name="nama_lengkap" id="nama_lengkap" value="<?php echo htmlspecialchars($customer['nama_lengkap']); ?>" required>
                    <label for="nama_lengkap">Nama</label>
                </div>
                <div class="input-group" data-aos="fade-up" data-aos-delay="400">
                    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($customer['email']); ?>" required>
                    <label for="email">Email</label>
                </div>
                <div class="input-group" data-aos="fade-up" data-aos-delay="450">
                    <input type="tel" name="phone" id="phone" value="<?php echo htmlspecialchars($customer['no_hp']); ?>" required>
                    <label for="phone">Nomor Telepon</label>
                </div>
                <button type="submit" id="update-btn" data-aos="fade-up" data-aos-delay="500">
                    <span>Simpan Perubahan</span>
                    <div class="spinner"></div>
                </button>
            </form>
        <?php endif; ?>

        <a href="../customer.php" class="btn-back" data-aos="fade-up" data-aos-delay="550"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <!-- AOS JS -->
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <!-- Particles.js -->
    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            once: true,
            easing: 'ease-out'
        });

        // Particles.js Configuration
        particlesJS('particles-js', {
            particles: {
                number: { value: 50, density: { enable: true, value_area: 1000 } },
                color: { value: '#d4af37' },
                shape: { type: 'circle', stroke: { width: 0, color: '#000000' } },
                opacity: { value: 0.4, random: true },
                size: { value: 2, random: true },
                line_linked: {
                    enable: true,
                    distance: 100,
                    color: '#d4af37',
                    opacity: 0.15,
                    width: 1
                },
                move: {
                    enable: true,
                    speed: 1.2,
                    direction: 'none',
                    random: false,
                    straight: false,
                    out_mode: 'out',
                    bounce: false
                }
            },
            interactivity: {
                detect_on: 'canvas',
                events: {
                    onhover: { enable: true, mode: 'repulse' },
                    onclick: { enable: true, mode: 'push' },
                    resize: true
                }
            },
            retina_detect: true
        });

        // File size validation before submission
        const fileInput = document.getElementById('profile_picture');
        const maxSize = 50 * 1024 * 1024; // 50MB in bytes
        const alertContainer = document.getElementById('alert-container');

        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                const file = this.files[0];
                if (file.size > maxSize) {
                    alertContainer.innerHTML = '';
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-danger';
                    alertDiv.textContent = 'Ukuran file maksimal 50MB.';
                    alertContainer.appendChild(alertDiv);
                    this.value = ''; // Clear the file input
                }
            }
        });

        // Handle form submission with AJAX
        const form = document.getElementById('profile-form');
        form.addEventListener('submit', function(e) {
            e.preventDefault(); // Prevent default form submission

            const btn = this.querySelector('button[type="submit"]');
            btn.classList.add('loading');
            btn.disabled = true;

            // Check if file size exceeds limit (redundant but ensures validation)
            const file = fileInput.files[0];
            if (file && file.size > maxSize) {
                btn.classList.remove('loading');
                btn.disabled = false;
                alertContainer.innerHTML = '';
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger';
                alertDiv.textContent = 'Ukuran file maksimal 50MB.';
                alertContainer.appendChild(alertDiv);
                fileInput.value = '';
                return;
            }

            const formData = new FormData(this);
            fetch('', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                btn.classList.remove('loading');
                btn.disabled = false;

                // Update alert message
                alertContainer.innerHTML = '';
                const alertDiv = document.createElement('div');
                alertDiv.className = `alert ${data.success ? 'alert-success' : 'alert-danger'}`;
                alertDiv.textContent = data.message;
                alertContainer.appendChild(alertDiv);

                // Update profile picture if a new one was uploaded
                if (data.success && data.image_path) {
                    const profileImage = document.getElementById('profile-image');
                    profileImage.src = data.image_path + '?' + new Date().getTime(); // Prevent caching
                }

                // Reset file input
                fileInput.value = '';
            })
            .catch(error => {
                btn.classList.remove('loading');
                btn.disabled = false;
                console.error('Error:', error);

                alertContainer.innerHTML = '';
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-danger';
                alertDiv.textContent = 'Terjadi kesalahan saat menyimpan perubahan.';
                alertContainer.appendChild(alertDiv);
            });
        });

        // Ripple effect on button click
        const button = document.getElementById('update-btn');
        button.addEventListener('click', function(e) {
            const rect = this.getBoundingClientRect();
            const ripple = document.createElement('div');
            ripple.classList.add('ripple');
            const diameter = Math.max(this.clientWidth, this.clientHeight);
            const radius = diameter / 2;
            ripple.style.width = ripple.style.height = `${diameter}px`;
            ripple.style.left = `${e.clientX - rect.left - radius}px`;
            ripple.style.top = `${e.clientY - rect.top - radius}px`;
            this.appendChild(ripple);
            setTimeout(() => ripple.remove(), 600);
        });

        // Input validation feedback
        const inputs = document.querySelectorAll('.input-group input');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                if (this.checkValidity()) {
                    this.style.borderBottomColor = 'var(--primary-gold)';
                } else {
                    this.style.borderBottomColor = '#dc3545';
                }
            });
        });
    </script>
</body>
</html>