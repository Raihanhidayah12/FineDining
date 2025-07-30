<!-- Footer -->
<footer class="footer">
  <div class="container">
    <div class="row py-5">
      <!-- FineDining Branding -->
      <div class="col-md-4 mb-4 mb-md-0">
        <h5 class="footer-title">FineDining</h5>
        <p class="footer-text">
          FineDining offers a luxurious culinary experience, presenting exquisite dishes with artistic flair, an elegant ambiance, and impeccable service. Every detail—from decor to flavor and presentation etiquette—is crafted to deliver an exclusive, unforgettable dining journey, perfect for special occasions and premium taste.
        </p>
      </div>

      <!-- Menu Links -->
      <div class="col-md-2 mb-4 mb-md-0">
        <h6 class="footer-subtitle">Menu</h6>
        <ul class="list-unstyled">
          <li><a href="/pages/menu.php?category=Makanan#makanan" class="footer-link">Foods</a></li>
          <li><a href="/pages/menu.php?category=Minuman#minuman" class="footer-link">Drink</a></li>
          <li><a href="/pages/menu.php?category=Dessert#dessert" class="footer-link">Dessert</a></li>
        </ul>
      </div>

      <!-- Information Links -->
      <div class="col-md-3 mb-4 mb-md-0">
        <h6 class="footer-subtitle">INFORMATION</h6>
        <ul class="list-unstyled">
          <li><a class="footer-link <?php echo $current_page == 'customer.php' ? 'active' : ''; ?>" href="customer.php">Home</a></li>
          <li><a class="footer-link <?php echo ($current_page == 'customer.php' && isset($_GET['section']) && $_GET['section'] == 'seating-experiences') ? 'active' : ''; ?>" href="customer.php#seating-experiences">Seating</a></li>
        </ul>
      </div>

      <!-- Social Media Links -->
      <div class="col-md-3">
        <h6 class="footer-subtitle">FOLLOW US</h6>
        <div class="social-links">
          <a href="#" class="footer-social-link" title="Facebook">
            <i class="fab fa-facebook-f"></i>
            <span class="fallback-text">FB</span>
          </a>
          <a href="#" class="footer-social-link" title="Instagram">
            <i class="fab fa-instagram"></i>
            <span class="fallback-text">IG</span>
          </a>
          <a href="#" class="footer-social-link" title="Twitter">
            <i class="fab fa-twitter"></i>
            <span class="fallback-text">TW</span>
          </a>
        </div>
      </div>
    </div>

    <!-- Divider -->
    <hr class="footer-divider">

    <!-- Copyright -->
    <p class="text-center mb-0 footer-copyright">© 2025 FineDining. All Rights Reserved.</p>
  </div>
</footer>

<style>
/* Footer Styling */
.footer {
  background: linear-gradient(145deg, #1a1a1a 0%, #2d1e3e 100%);
  color: #e0e0e0;
  padding: 4rem 0 2rem;
  font-family: 'Cinzel', serif;
  position: relative;
  overflow: hidden;
  opacity: 0;
  transform: translateY(30px);
  animation: fadeInUp 1.2s ease-out 0.3s forwards;
}

.footer::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: radial-gradient(circle, rgba(255, 215, 0, 0.1) 0%, transparent 70%);
  opacity: 0.3;
  z-index: 0;
}

.footer > .container {
  position: relative;
  z-index: 1;
}

@keyframes fadeInUp {
  0% {
    opacity: 0;
    transform: translateY(30px);
  }
  100% {
    opacity: 1;
    transform: translateY(0);
  }
}

.footer-title {
  font-size: 2rem;
  font-weight: 700;
  color: #ffd700;
  margin-bottom: 1.25rem;
  letter-spacing: 1.5px;
  position: relative;
  display: inline-block;
}

.footer-title::after {
  content: '';
  position: absolute;
  bottom: -5px;
  left: 0;
  width: 50px;
  height: 2px;
  background: #ffd700;
  transition: width 0.4s ease;
}

.footer-title:hover::after {
  width: 80px;
}

.footer-subtitle {
  font-size: 1.2rem;
  font-weight: 600;
  color: #e0e0e0;
  margin-bottom: 1.5rem;
  text-transform: uppercase;
  letter-spacing: 2px;
  position: relative;
  padding-bottom: 8px;
}

.footer-subtitle::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  width: 30px;
  height: 1px;
  background: #ffd700;
}

.footer-text {
  font-family: 'Lora', serif;
  font-size: 0.95rem;
  line-height: 1.6;
  color: #d0d0d0;
  text-align: justify;
  opacity: 0.9;
}

.footer-link {
  color: #d0d0d0;
  text-decoration: none;
  font-family: 'Lora', serif;
  font-size: 1rem;
  display: block;
  margin-bottom: 0.75rem;
  transition: all 0.3s ease;
  position: relative;
  padding-left: 15px;
}

.footer-link::before {
  content: '→';
  position: absolute;
  left: 0;
  opacity: 0;
  color: #ffd700;
  transition: all 0.3s ease;
}

.footer-link:hover {
  color: #ffd700;
  padding-left: 25px;
}

.footer-link:hover::before {
  opacity: 1;
}

.social-links {
  display: flex;
  gap: 1.5rem;
  align-items: center;
}

.footer-social-link {
  color: #e0e0e0 !important;
  font-size: 1.8rem;
  text-decoration: none;
  transition: all 0.4s ease;
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.footer-social-link:hover {
  color: #ffd700 !important;
  transform: translateY(-5px) scale(1.1);
}

.footer-social-link::after {
  content: attr(title);
  position: absolute;
  bottom: -25px;
  left: 50%;
  transform: translateX(-50%);
  background: rgba(0, 0, 0, 0.8);
  color: #ffd700;
  font-family: 'Lora', serif;
  font-size: 0.75rem;
  padding: 5px 10px;
  border-radius: 3px;
  opacity: 0;
  visibility: hidden;
  transition: all 0.3s ease;
  white-space: nowrap;
}

.footer-social-link:hover::after {
  opacity: 1;
  visibility: visible;
}

.fallback-text {
  display: none;
  font-family: 'Lora', serif;
  font-size: 0.9rem;
  margin-left: 8px;
}

.footer-social-link i:empty + .fallback-text,
.footer-social-link i:not(:before) + .fallback-text {
  display: inline;
}

.social-links i {
  display: inline-block !important;
  min-width: 24px;
  min-height: 24px;
}

.footer-divider {
  border-color: rgba(255, 215, 0, 0.3);
  margin: 2.5rem 0 1.5rem;
  border-style: solid;
  border-width: 1px;
}

.footer-copyright {
  font-family: 'Lora', serif;
  font-size: 0.9rem;
  color: #d0d0d0;
  opacity: 0.8;
  letter-spacing: 0.5px;
}

@media (max-width: 767.98px) {
  .footer {
    padding: 2.5rem 0 1.5rem;
  }

  .footer-title {
    font-size: 1.6rem;
  }

  .footer-subtitle {
    font-size: 1.1rem;
  }

  .footer-text {
    font-size: 0.9rem;
  }

  .footer-link {
    font-size: 0.95rem;
  }

  .footer-social-link {
    font-size: 1.5rem;
  }

  .footer-copyright {
    font-size: 0.85rem;
  }
}
</style>

<!-- External Dependencies -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Lora:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet">

<script>
document.addEventListener('DOMContentLoaded', function() {
  const footer = document.querySelector('.footer');
  footer.style.animationPlayState = 'running';

  // Smooth scroll for anchor links
  const footerLinks = document.querySelectorAll('.footer-link');
  footerLinks.forEach(link => {
    link.addEventListener('click', function(e) {
      const href = this.getAttribute('href');
      if (href.includes('#')) {
        e.preventDefault();
        const targetId = href.split('#')[1];
        const target = document.getElementById(targetId);
        if (target) {
          window.scrollTo({
            top: target.offsetTop - 50,
            behavior: 'smooth'
          });
        } else {
          window.location.href = href;
        }
      }
    });
  });

  // Debug: Check if Font Awesome icons are loaded
  const icons = document.querySelectorAll('.footer-social-link i');
  icons.forEach(icon => {
    const computedStyle = window.getComputedStyle(icon, ':before');
    if (!computedStyle.content || computedStyle.content === 'none') {
      console.warn('Font Awesome icon not loaded for:', icon.className);
    }
  });
});
</script>