<?php
session_start();

// Store the role before destroying the session
$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'customer';

// Clear all session data
$_SESSION = array();

// If "remember me" cookie exists, clear it
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Destroy the session
session_destroy();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/assets/img/logo.png" type="image/gif" sizes="16x16">
    <title>Logging Out || FineDining</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,400;1,700;1,900&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gold: #d4af37;
            --accent-gold: #e6c74a;
            --dark-bg: #0f172a;
            --light-bg: #1e293b;
            --text-light: #ffffff;
            --text-muted: #d3d4db;
            --text-highlight: #ffcc00;
            --shadow: 0 10px 20px rgba(0, 0, 0, 0.5);
            --glass-bg: rgba(255, 255, 255, 0.1);
            --button-bg: #ff8c00;
            --button-bg-hover: #ffa500;
        }

        html, body {
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            height: 100vh;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(to bottom, var(--dark-bg), var(--light-bg));
            color: var(--text-light);
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
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

        .container {
            text-align: center;
            color: var(--text-light);
            padding: 2rem 3rem;
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(212, 175, 55, 0.2);
            border-radius: 15px;
            box-shadow: var(--shadow);
            transform: scale(0.8);
            opacity: 0;
            animation: elegantFadeIn 1.5s ease-out forwards;
        }

        h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 900;
            margin-bottom: 1rem;
            color: var(--text-highlight);
            text-shadow: 0 2px 10px rgba(255, 204, 0, 0.3);
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        p {
            font-size: 1.1rem;
            color: var(--text-muted);
            font-style: italic;
            max-width: 400px;
            margin: 0 auto 1.5rem;
            opacity: 0;
            animation: slideUp 1.8s ease-out 1.5s forwards;
        }

        .signature {
            font-size: 1rem;
            color: var(--primary-gold);
            font-family: 'Roboto', sans-serif;
            opacity: 0;
            animation: fadeIn 2s ease-out 2s forwards;
        }

        @keyframes elegantFadeIn {
            0% { transform: scale(0.8); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }

        @keyframes slideUp {
            0% { opacity: 0; transform: translateY(20px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeIn {
            0% { opacity: 0; }
            100% { opacity: 1; }
        }

        @media (max-width: 768px) {
            .container { padding: 1.5rem 2rem; }
            h1 { font-size: 2rem; }
            p { font-size: 1rem; max-width: 300px; }
        }
    </style>
</head>
<body>
    <div class="overlay"></div>
    <div class="container">
        <?php if ($role === 'customer'): ?>
            <h1>À Bientôt!</h1>
            <p>We cherish your presence. We look forward to serving you again.</p>
            <div class="signature">— The FineDining Team</div>
        <?php else: ?>
            <h1>Bravo!</h1>
            <p>Your dedication inspires us. Let’s continue this journey together.</p>
            <div class="signature">— FineDining Management</div>
        <?php endif; ?>
    </div>
    <script>
        setTimeout(() => {
            window.location.href = '../index.php';
        }, 4000); // Redirect after 4 seconds
    </script>
</body>
</html>