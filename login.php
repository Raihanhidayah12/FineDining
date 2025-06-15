<?php
include 'includes/config.php';
session_start();

// Redirect if already logged in
if (isset($_SESSION['id_user'])) {
    switch ($_SESSION['role']) {
        case 'admin':
            header('Location: dashboard/admin.php');
            break;
        case 'chef':
            header('Location: dashboard/chef.php');
            break;
        case 'kasir':
            header('Location: dashboard/kasir.php');
            break;
        case 'waiter':
            header('Location: dashboard/waiter.php');
            break;
        case 'customer':
        default:
            header('Location: customer.php');
            break;
    }
    exit;
}

// Handle payment success message
$payment_message = $_SESSION['payment_success'] ?? '';
unset($_SESSION['payment_success']);

// Process login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Use prepared statements with the correct column name
    $stmt = $conn->prepare("SELECT id_user, username, password, role FROM user WHERE username = ?");
    if (!$stmt) {
        $error = "Database error: " . $conn->error;
    } else {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            // Store session data
            $_SESSION['id_user'] = $user['id_user'];
            $_SESSION['user'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Redirect based on role
            switch ($user['role']) {
                case 'admin':
                    header('Location: dashboard/admin.php');
                    break;
                case 'chef':
                    header('Location: dashboard/chef.php');
                    break;
                case 'kasir':
                    header('Location: dashboard/kasir.php');
                    break;
                case 'waiter':
                    header('Location: dashboard/waiter.php');
                    break;
                case 'customer':
                default:
                    header('Location: customer.php');
                    break;
            }
            exit;
        } else {
            $error = "Login gagal! Periksa kembali username dan password Anda.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>Login || FineDining</title>
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
        :root {
            --primary-gold: #d4af37;
            --dark-bg: #121212;
            --light-bg: #262626;
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
            background: linear-gradient(to right, #1e1e2f, #2c2c54);
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
            width: 450px;
            background: rgba(38, 38, 38, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 25px;
            padding: 50px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            position: relative;
            z-index: 10;
        }

        h2 {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
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
            background: var(--primary-gold);
            margin: 0.5rem auto;
        }

        .input-group {
            position: relative;
            margin-bottom: 2rem;
            border-bottom: 2px solid var(--primary-gold);
        }

        .input-group label {
            position: absolute;
            top: 50%;
            left: 10px;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 1rem;
            transition: all 0.4s ease;
            pointer-events: none;
        }

        .input-group input {
            width: 100%;
            padding: 12px 10px;
            background: transparent;
            border: none;
            color: var(--text-light);
            outline: none;
            font-size: 1rem;
        }

        .input-group input:focus ~ label,
        .input-group input:valid ~ label {
            top: -10px;
            font-size: 0.85rem;
            color: var(--primary-gold);
        }

        .remember {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }

        .remember input {
            accent-color: var(--primary-gold);
        }

        .remember a {
            color: var(--primary-gold);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .remember a:hover {
            color: #e6c74a;
            text-decoration: underline;
        }

        button {
            width: 100%;
            padding: 12px;
            background: var(--primary-gold);
            color: #121212;
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
            box-shadow: 0 5px 15px rgba(212, 175, 55, 0.4);
        }

        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        button .spinner {
            display: none;
            border: 3px solid #121212;
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

        .signUp-link p {
            color: var(--text-muted);
            font-size: 0.9rem;
            text-align: center;
            margin-top: 1.5rem;
        }

        .signUp-link a {
            color: var(--primary-gold);
            text-decoration: none;
            font-weight: 500;
        }

        .signUp-link a:hover {
            color: #e6c74a;
            text-decoration: underline;
        }

        .alert {
            padding: 12px;
            font-size: 0.9rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .alert-success {
            background-color: rgba(212, 237, 218, 0.9);
            color: #155724;
        }

        .alert-danger {
            background-color: rgba(248, 215, 218, 0.9);
            color: #721c24;
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
            transition: all 0.3s ease;
        }

        .btn-back:hover {
            background: var(--primary-gold);
            color: #121212;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(212, 175, 55, 0.4);
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
        @media (max-width: 576px) {
            .wrapper {
                width: 90%;
                padding: 30px;
            }

            h2 {
                font-size: 2rem;
            }

            .input-group input {
                font-size: 0.9rem;
            }

            .input-group label {
                font-size: 0.9rem;
            }

            button, .btn-back {
                padding: 10px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div id="particles-js"></div>
    <div class="wrapper" data-aos="fade-up" data-aos-duration="1000">
        <form method="POST" action="" id="login-form">
            <h2 data-aos="fade-down" data-aos-delay="100">Login to FineDining</h2>

            <?php if (!empty($payment_message)): ?>
                <div class="alert alert-success" data-aos="fade-up" data-aos-delay="200"><?php echo htmlspecialchars($payment_message); ?></div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" data-aos="fade-up" data-aos-delay="200"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="input-group" data-aos="fade-up" data-aos-delay="300">
                <input type="text" name="username" id="username" required>
                <label for="username">Username</label>
            </div>
            <div class="input-group" data-aos="fade-up" data-aos-delay="400">
                <input type="password" name="password" id="password" required>
                <label for="password">Password</label>
            </div>

            <div class="remember" data-aos="fade-up" data-aos-delay="500">
                <label><input type="checkbox" name="remember"> Remember me</label>
                <a href="forgot_password.php">Forgot Password?</a>
            </div>

            <button type="submit" id="login-btn" data-aos="fade-up" data-aos-delay="600">
                <span>Login</span>
                <div class="spinner"></div>
            </button>

            <div class="signUp-link" data-aos="fade-up" data-aos-delay="700">
                <p>Don't have an account? <a href="register.php">Sign up now</a></p>
            </div>
        </form>
        <a href="index.php" class="btn-back" data-aos="fade-up" data-aos-delay="800"><i class="fas fa-arrow-left"></i> Back to Home</a>
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
            duration: 1000,
            once: true,
            easing: 'ease-out'
        });

        // Particles.js Configuration
        particlesJS('particles-js', {
            particles: {
                number: { value: 80, density: { enable: true, value_area: 800 } },
                color: { value: '#d4af37' },
                shape: { type: 'circle', stroke: { width: 0, color: '#000000' } },
                opacity: { value: 0.3, random: true },
                size: { value: 3, random: true },
                line_linked: {
                    enable: true,
                    distance: 150,
                    color: '#d4af37',
                    opacity: 0.2,
                    width: 1
                },
                move: {
                    enable: true,
                    speed: 2,
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

        // Loading animation for submit button
        document.getElementById('login-form').addEventListener('submit', function(e) {
            const btn = document.getElementById('login-btn');
            btn.classList.add('loading');
            btn.disabled = true;
            setTimeout(() => {
                btn.classList.remove('loading');
                btn.disabled = false;
            }, 2000); // Simulate loading for 2 seconds
        });
    </script>
</body>
</html>