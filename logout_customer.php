<?php
session_start();

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
    <title>Thank You || Logout</title>
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
            max-width: 600px;
            opacity: 0;
            animation: elegantFadeIn 1.5s ease-in-out forwards;
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
            line-height: 1.6;
            max-width: 400px;
            margin: 0 auto 1.5rem;
        }

        .sparkle {
            position: absolute;
            width: 8px;
            height: 8px;
            background: var(--primary-gold);
            border-radius: 50%;
            animation: sparkle 2s infinite;
            pointer-events: none;
        }

        @keyframes elegantFadeIn {
            0% { opacity: 0; transform: translateY(30px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        @keyframes sparkle {
            0% { transform: scale(0); opacity: 0.8; }
            50% { transform: scale(1); opacity: 0.3; }
            100% { transform: scale(0); opacity: 0; }
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
        <h1>Thank You!</h1>
        <p>Your presence has been a delight. We look forward to welcoming you back soon!</p>
    </div>
    <script>
        // Add sparkles for elegance
        function createSparkle() {
            const sparkle = document.createElement('div');
            sparkle.classList.add('sparkle');
            sparkle.style.left = Math.random() * window.innerWidth + 'px';
            sparkle.style.top = Math.random() * window.innerHeight + 'px';
            document.body.appendChild(sparkle);
            setTimeout(() => sparkle.remove(), 2000);
        }
        setInterval(createSparkle, 300);

        // Redirect after 4 seconds
        setTimeout(() => {
            window.location.href = '../index.php';
        }, 4000);
    </script>
</body>
</html>