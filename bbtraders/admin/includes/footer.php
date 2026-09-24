    </div>
  </div>
</div>
<div class="toast" id="toast"></div>
<script>
  window.SITE_BASE_URL = "<?= BASE_URL ?>";
  window.CSRF_TOKEN = "<?= generate_csrf_token() ?>";
</script>
<script src="<?= BASE_URL ?>assets/js/main.js"></script>
</body>
</html>
