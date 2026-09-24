</main>

<div class="newsletter">
  <div class="container">
    <div>
      <h3 style="color:#fff;margin-bottom:4px;">Subscribe to our newsletter</h3>
      <p style="color:rgba(255,255,255,.7); font-size:13.5px;">Get exclusive deals and new arrival alerts.</p>
    </div>
    <form id="newsletterForm">
      <input type="email" name="email" placeholder="Your email address" required>
      <button type="submit" class="btn btn-gold">Subscribe</button>
    </form>
  </div>
</div>

<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <div class="brand" style="color:#fff; margin-bottom:12px;">
          <span class="logo-badge">B&amp;B</span>
          <span>B&amp;B TRADERS BD</span>
        </div>
        <p style="font-size:13.5px; color:#9db3a8; max-width:280px;">
          <?= clean(get_setting('site_address', 'Chattogram, Bangladesh')) ?><br>
          <?= clean(get_setting('site_phone', '')) ?> &middot; <?= clean(get_setting('site_email', '')) ?>
        </p>
        <div class="social-icons" style="margin-top:14px;">
          <?php if (get_setting('facebook_url')): ?><a href="<?= clean(get_setting('facebook_url')) ?>">f</a><?php endif; ?>
          <?php if (get_setting('instagram_url')): ?><a href="<?= clean(get_setting('instagram_url')) ?>">ig</a><?php endif; ?>
          <?php if (get_setting('youtube_url')): ?><a href="<?= clean(get_setting('youtube_url')) ?>">yt</a><?php endif; ?>
          <?php if (get_setting('whatsapp_url')): ?><a href="<?= clean(get_setting('whatsapp_url')) ?>">wa</a><?php endif; ?>
        </div>
      </div>
      <div>
        <h5>Shop</h5>
        <ul>
          <li><a href="<?= BASE_URL ?>shop.php">All Products</a></li>
          <li><a href="<?= BASE_URL ?>best-sellers.php">Best Sellers</a></li>
        </ul>
      </div>
      <div>
        <h5>Account</h5>
        <ul>
          <li><a href="<?= BASE_URL ?>account/dashboard.php">My Account</a></li>
          <li><a href="<?= BASE_URL ?>account/orders.php">My Orders</a></li>
          <li><a href="<?= BASE_URL ?>account/wishlist.php">Wishlist</a></li>
          <li><a href="<?= BASE_URL ?>track-order.php">Track Order</a></li>
        </ul>
      </div>
      <div>
        <h5>Help</h5>
        <ul>
          <li><a href="<?= BASE_URL ?>contact.php">Contact Us</a></li>
          <li><a href="<?= BASE_URL ?>shop.php">Returns Policy</a></li>
          <li><a href="<?= BASE_URL ?>shop.php">Shipping Info</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <span>&copy; <?= date('Y') ?> B&amp;B TRADERS BD. All rights reserved.</span>
      <span>Made with care in Bangladesh</span>
    </div>
  </div>
</footer>

<div class="toast" id="toast"></div>
<a href="<?= BASE_URL ?>cart.php" class="floating-cart" id="floatingCart">&#128722;</a>

<script>
  window.SITE_BASE_URL = "<?= BASE_URL ?>";
  window.CSRF_TOKEN = "<?= generate_csrf_token() ?>";
</script>
<script src="<?= BASE_URL ?>assets/js/main.js"></script>
</body>
</html>
