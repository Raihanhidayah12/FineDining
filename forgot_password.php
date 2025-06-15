<?php
session_start();
include 'includes/config.php';

$success_message = "";
$error_message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validasi input
    if (empty($email) || empty($phone) || empty($password) || empty($confirm_password)) {
        $error_message = "Semua kolom harus diisi.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Kata sandi tidak cocok.";
    } else {
        // Verifikasi email dan nomor telepon di tabel customer
        $query_customer = "SELECT email FROM customer WHERE email = ? AND no_hp = ?";
        if ($stmt_customer = mysqli_prepare($conn, $query_customer)) {
            mysqli_stmt_bind_param($stmt_customer, "ss", $email, $phone);
            mysqli_stmt_execute($stmt_customer);
            $result_customer = mysqli_stmt_get_result($stmt_customer);

            if (mysqli_num_rows($result_customer) > 0) {
                // Hash kata sandi baru
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Update kata sandi di tabel user berdasarkan email
                $update_user = "UPDATE user SET password = ? WHERE email = ?";
                if ($stmt_user = mysqli_prepare($conn, $update_user)) {
                    mysqli_stmt_bind_param($stmt_user, "ss", $hashed_password, $email);
                    if (mysqli_stmt_execute($stmt_user)) {
                        $success_message = "Kata sandi berhasil diubah. <a href='login.php'>Kembali ke login</a>";
                    } else {
                        $error_message = "Gagal mengubah kata sandi di tabel user: " . mysqli_error($conn);
                    }
                    mysqli_stmt_close($stmt_user);
                } else {
                    $error_message = "Gagal mempersiapkan pernyataan update user: " . mysqli_error($conn);
                }
            } else {
                $error_message = "Email atau nomor telepon tidak ditemukan di tabel customer.";
            }
            mysqli_stmt_close($stmt_customer);
        } else {
            $error_message = "Gagal mempersiapkan pernyataan customer: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/assets/img/logo.png" type="image/gif" sizes="16x16">
    <title>Reset Password || FineDining</title>
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
            width: 400px;
            background: rgba(30, 30, 47, 0.95);
            backdrop-filter: blur(12px);
            border-radius: 25px;
            padding: 40px 30px;
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
            font-size: 2.25rem;
            font-weight: 900;
            color: var(--primary-gold);
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
        }

        h2::after {
            content: '';
            display: block;
            width: 80px;
            height: 3px;
            background: linear-gradient(to right, transparent, var(--primary-gold), transparent);
            margin: 0.5rem auto;
        }

        .input-group {
            position: relative;
            margin-bottom: 1.8rem;
            border-bottom: 2px solid var(--primary-gold);
        }

        .input-group label {
            position: absolute;
            top: 50%;
            left: 10px;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 0.95rem;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            pointer-events: none;
        }

        .input-group input {
            width: 100%;
            padding: 10px 10px;
            background: transparent;
            border: none;
            color: var(--text-light);
            outline: none;
            font-size: 0.95rem;
            transition: border-color 0.3s ease;
        }

        .input-group input:focus ~ label,
        .input-group input:valid ~ label {
            top: -10px;
            font-size: 0.8rem;
            color: var(--primary-gold);
        }

        .input-group input:invalid:focus {
            border-bottom-color: #dc3545;
        }

        button {
            width: 100%;
            padding: 12px;
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
            width: 20px;
            height: 20px;
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
            padding: 12px;
            font-size: 0.85rem;
            border-radius: 10px;
            margin-bottom: 1.2rem;
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
            margin: 1.5rem auto 0;
            width: fit-content;
            background: transparent;
            color: var(--primary-gold);
            border: 2px solid var(--primary-gold);
            padding: 8px 20px;
            border-radius: 50px;
            text-decoration: none;
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 0.9rem;
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
                width: 90%;
                padding: 30px 25px;
            }

            h2 {
                font-size: 2rem;
            }
        }

        @media (max-width: 576px) {
            .wrapper {
                width: 90%;
                padding: 25px 20px;
            }

            h2 {
                font-size: 1.75rem;
            }

            .input-group input {
                font-size: 0.85rem;
                padding: 8px 8px;
            }

            .input-group label {
                font-size: 0.85rem;
            }

            button, .btn-back {
                padding: 10px;
                font-size: 0.85rem;
            }

            .alert {
                font-size: 0.8rem;
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div id="particles-js"></div>
    <div class="wrapper" data-aos="fade-up" data-aos-duration="800">
        <form method="POST" id="reset-form">
            <h2 data-aos="fade-down" data-aos-delay="100">Reset Your Password</h2>

            <?php if ($success_message): ?>
                <div class="alert alert-success" data-aos="fade-up" data-aos-delay="150"><?php echo $success_message; ?></div>
            <?php elseif ($error_message): ?>
                <div class="alert alert-danger" data-aos="fade-up" data-aos-delay="150"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="input-group" data-aos="fade-up" data-aos-delay="200">
                <input type="email" name="email" id="email" required>
                <label for="email">Enter Your Email</label>
            </div>

            <div class="input-group" data-aos="fade-up" data-aos-delay="250">
                <input type="tel" name="phone" id="phone" required>
                <label for="phone">Enter Phone Number</label>
            </div>
            
            <div class="input-group" data-aos="fade-up" data-aos-delay="300">
                <input type="password" name="password" id="password" required>
                <label for="password">New Password</label>
            </div>
            
            <div class="input-group" data-aos="fade-up" data-aos-delay="350">
                <input type="password" name="confirm_password" id="confirm_password" required>
                <label for="confirm_password">Confirm Password</label>
            </div>

            <button type="submit" id="reset-btn" data-aos="fade-up" data-aos-delay="400">
                <span>Change Password</span>
                <div class="spinner"></div>
            </button>
        </form>
        <a href="login.php" class="btn-back" data-aos="fade-up" data-aos-delay="450"><i class="fas fa-arrow-left"></i> Return to Login</a>
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

        // Loading animation and ripple effect for submit button
        document.getElementById('reset-form').addEventListener('submit', function(e) {
            const btn = document.getElementById('reset-btn');
            btn.classList.add('loading');
            btn.disabled = true;
            setTimeout(() => {
                btn.classList.remove('loading');
                btn.disabled = false;
            }, 2000); // Simulate loading for 2 seconds
        });

        // Ripple effect on button click
        document.getElementById('reset-btn').addEventListener('click', function(e) {
            const btn = this;
            const rect = btn.getBoundingClientRect();
            const ripple = document.createElement('span');
            ripple.classList.add('ripple');
            const diameter = Math.max(btn.clientWidth, btn.clientHeight);
            const radius = diameter / 2;
            ripple.style.width = ripple.style.height = `${diameter}px`;
            ripple.style.left = `${e.clientX - rect.left - radius}px`;
            ripple.style.top = `${e.clientY - rect.top - radius}px`;
            btn.appendChild(ripple);
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