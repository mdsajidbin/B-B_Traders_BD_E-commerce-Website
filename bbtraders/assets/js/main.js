/* B&B TRADERS BD - Front-end interactions */
(function () {
  const BASE = document.body.getAttribute('data-base') || (window.SITE_BASE_URL || '/');

  function toast(msg, ok = true) {
    const el = document.getElementById('toast');
    if (!el) return;
    el.textContent = msg;
    el.style.background = ok ? '#045C2D' : '#DC2626';
    el.classList.add('show');
    clearTimeout(el._t);
    el._t = setTimeout(() => el.classList.remove('show'), 2500);
  }
  window.bbToast = toast;

  // Mobile nav toggle
  const toggle = document.getElementById('mobileToggle');
  const nav = document.getElementById('mainNav');
  if (toggle && nav) {
    toggle.addEventListener('click', () => {
      nav.style.display = nav.style.display === 'block' ? 'none' : 'block';
    });
  }

  // Floating cart button visibility
  const floatingCart = document.getElementById('floatingCart');
  if (floatingCart) {
    window.addEventListener('scroll', () => {
      floatingCart.style.display = window.scrollY > 400 ? 'flex' : 'none';
    });
  }

  function updateCartBadge(count) {
    const badge = document.getElementById('cartBadge');
    if (!badge) return;
    if (count > 0) {
      badge.textContent = count;
      badge.style.display = 'flex';
    } else {
      badge.style.display = 'none';
    }
  }

  function postJSON(url, data) {
    return fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
      body: new URLSearchParams(data)
    }).then(r => r.json());
  }
  window.bbPostJSON = postJSON;

  // Add to cart (product cards + product detail page)
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.js-add-to-cart');
    if (!btn) return;
    e.preventDefault();
    const productId = btn.dataset.productId;
    const variantId = btn.dataset.variantId || (document.getElementById('selectedVariantId') ? document.getElementById('selectedVariantId').value : '');
    const qtyInput = document.getElementById('pdQty');
    const qty = qtyInput ? qtyInput.value : (btn.dataset.qty || 1);

    btn.disabled = true;
    const originalText = btn.innerHTML;
    btn.innerHTML = 'Adding...';

    postJSON(BASE + 'ajax/cart.php', { action: 'add', product_id: productId, variant_id: variantId, qty: qty, csrf_token: window.CSRF_TOKEN || '' })
      .then(res => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        if (res.success) {
          updateCartBadge(res.cart_count);
          toast(res.message || 'Added to cart');
        } else {
          toast(res.message || 'Could not add to cart', false);
        }
      })
      .catch(() => { btn.disabled = false; btn.innerHTML = originalText; toast('Network error', false); });
  });

  // Order Now / Buy Now — add to cart then go straight to checkout
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.js-buy-now');
    if (!btn) return;
    e.preventDefault();
    const productId = btn.dataset.productId;
    const variantId = document.getElementById('selectedVariantId') ? document.getElementById('selectedVariantId').value : '';
    const qtyInput = document.getElementById('pdQty');
    const qty = qtyInput ? qtyInput.value : 1;

    btn.disabled = true;
    const originalText = btn.innerHTML;
    btn.innerHTML = 'Processing...';

    postJSON(BASE + 'ajax/cart.php', { action: 'add', product_id: productId, variant_id: variantId, qty: qty, csrf_token: window.CSRF_TOKEN || '' })
      .then(res => {
        if (res.success) {
          window.location.href = BASE + 'checkout.php';
        } else {
          btn.disabled = false;
          btn.innerHTML = originalText;
          toast(res.message || 'Could not process order', false);
        }
      })
      .catch(() => { btn.disabled = false; btn.innerHTML = originalText; toast('Network error', false); });
  });

  // Wishlist toggle
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.js-wishlist-toggle');
    if (!btn) return;
    e.preventDefault();
    const productId = btn.dataset.productId;
    postJSON(BASE + 'ajax/wishlist.php', { action: 'toggle', product_id: productId, csrf_token: window.CSRF_TOKEN || '' })
      .then(res => {
        if (res.success) {
          btn.classList.toggle('active', res.wishlisted);
          toast(res.message);
        } else if (res.login_required) {
          window.location.href = BASE + 'login.php';
        } else {
          toast(res.message || 'Error', false);
        }
      });
  });

  // Cart page: quantity update / remove
  document.addEventListener('click', function (e) {
    const removeBtn = e.target.closest('.js-cart-remove');
    if (removeBtn) {
      e.preventDefault();
      const key = removeBtn.dataset.key;
      postJSON(BASE + 'ajax/cart.php', { action: 'remove', key, csrf_token: window.CSRF_TOKEN || '' })
        .then(res => { if (res.success) window.location.reload(); });
    }
    const qtyBtn = e.target.closest('.js-qty-btn');
    if (qtyBtn) {
      const wrap = qtyBtn.closest('.qty-selector');
      const input = wrap.querySelector('input');
      let val = parseInt(input.value || '1', 10);
      val = qtyBtn.dataset.dir === 'up' ? val + 1 : Math.max(1, val - 1);
      input.value = val;
      if (input.dataset.cartKey) {
        postJSON(BASE + 'ajax/cart.php', { action: 'update', key: input.dataset.cartKey, qty: val, csrf_token: window.CSRF_TOKEN || '' })
          .then(res => { if (res.success) window.location.reload(); });
      }
    }
  });

  // Product gallery thumbs
  document.addEventListener('click', function (e) {
    const thumb = e.target.closest('.gallery-thumbs img');
    if (!thumb) return;
    document.querySelectorAll('.gallery-thumbs img').forEach(i => i.classList.remove('active'));
    thumb.classList.add('active');
    document.querySelector('.gallery-main img').src = thumb.src;
  });

  // Product variant chip selection
  document.addEventListener('click', function (e) {
    const chip = e.target.closest('.variant-chip');
    if (!chip) return;
    const group = chip.closest('.variant-group');
    group.querySelectorAll('.variant-chip').forEach(c => c.classList.remove('active'));
    chip.classList.add('active');
    const hidden = document.getElementById('selectedVariantId');
    if (hidden) hidden.value = chip.dataset.variantId;
    if (chip.dataset.price) {
      const priceEl = document.getElementById('pdCurrentPrice');
      if (priceEl) priceEl.textContent = chip.dataset.priceFormatted || chip.dataset.price;
    }
  });

  // Product detail tabs
  document.addEventListener('click', function (e) {
    const tab = e.target.closest('.pd-tab');
    if (!tab) return;
    document.querySelectorAll('.pd-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.pd-tab-content').forEach(c => c.style.display = 'none');
    tab.classList.add('active');
    document.getElementById(tab.dataset.target).style.display = 'block';
  });

  // Newsletter
  const nlForm = document.getElementById('newsletterForm');
  if (nlForm) {
    nlForm.addEventListener('submit', function (e) {
      e.preventDefault();
      const email = nlForm.email.value;
      postJSON(BASE + 'ajax/newsletter.php', { email, csrf_token: window.CSRF_TOKEN || '' })
        .then(res => { toast(res.message, res.success); if (res.success) nlForm.reset(); });
    });
  }

  // Apply coupon (cart page)
  const couponForm = document.getElementById('couponForm');
  if (couponForm) {
    couponForm.addEventListener('submit', function (e) {
      e.preventDefault();
      const code = couponForm.coupon_code.value;
      postJSON(BASE + 'ajax/cart.php', { action: 'apply_coupon', coupon_code: code, csrf_token: window.CSRF_TOKEN || '' })
        .then(res => { toast(res.message, res.success); if (res.success) window.location.reload(); });
    });
  }
})();
