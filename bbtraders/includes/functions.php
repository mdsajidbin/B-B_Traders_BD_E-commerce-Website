<?php
/**
 * B&B TRADERS BD - Shared Helper Functions
 */

/* ---------------------------------------------------------
   Security helpers
--------------------------------------------------------- */

function clean($value) {
    return htmlspecialchars(trim($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . generate_csrf_token() . '">';
}

function verify_csrf($token) {
    return !empty($_SESSION['csrf_token']) && !empty($token) && hash_equals($_SESSION['csrf_token'], $token);
}

function redirect($path) {
    header('Location: ' . (strpos($path, 'http') === 0 ? $path : BASE_URL . ltrim($path, '/')));
    exit;
}

function json_response($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = trim($text, '-');
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text) ?: $text;
    $text = strtolower(preg_replace('~[^-\w]+~', '', $text));
    return $text ?: 'n-a';
}

function normalize_frontend_link($link, $default = '') {
    $link = trim((string) $link);
    if ($link === '') return $default;

    if (preg_match('#^(https?:|mailto:|tel:|#|javascript:)#i', $link) === 1) {
        return $link;
    }

    $baseUrl = parse_url(BASE_URL);
    $host = ($baseUrl['scheme'] ?? 'http') . '://' . ($baseUrl['host'] ?? 'localhost');
    if (!empty($baseUrl['port'])) {
        $host .= ':' . $baseUrl['port'];
    }
    $basePath = rtrim($baseUrl['path'] ?? '/', '/');

    if (strpos($link, '/') === 0) {
        if ($basePath !== '' && $basePath !== '/') {
            if (strpos($link, $basePath . '/') === 0 || $link === $basePath) {
                return $host . $link;
            }
            return $host . $basePath . $link;
        }
        return $host . $link;
    }

    return BASE_URL . ltrim($link, '/');
}

/* ---------------------------------------------------------
   Settings (DB-driven site configuration)
--------------------------------------------------------- */

function get_settings() {
    static $settings = null;
    if ($settings === null) {
        global $pdo;
        $settings = [];
        try {
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
            foreach ($stmt->fetchAll() as $row) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (Exception $e) {
            $settings = [];
        }
    }
    return $settings;
}

function get_setting($key, $default = '') {
    $settings = get_settings();
    return $settings[$key] ?? $default;
}

function format_price($amount) {
    $symbol = get_setting('currency_symbol', '৳');
    return $symbol . number_format((float) $amount, 2);
}

/* ---------------------------------------------------------
   Categories / Products
--------------------------------------------------------- */

function get_categories($activeOnly = true) {
    global $pdo;
    $sql = "SELECT c.*,
                (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id AND p.status = 'active') AS product_count
            FROM categories c";
    if ($activeOnly) $sql .= " WHERE c.status = 'active'";
    $sql .= " ORDER BY c.sort_order ASC, c.name ASC";
    return $pdo->query($sql)->fetchAll();
}

function get_category_by_slug($slug) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE slug = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$slug]);
    return $stmt->fetch();
}

function get_home_posters() {
    global $pdo;
    try {
        $stmt = $pdo->query("SELECT * FROM home_posters WHERE status = 'active' ORDER BY sort_order ASC, id ASC");
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

function product_rating($productId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT ROUND(AVG(rating),1) AS avg_rating, COUNT(*) AS total
                            FROM reviews WHERE product_id = ? AND status = 'approved'");
    $stmt->execute([$productId]);
    $row = $stmt->fetch();
    return [
        'avg'   => $row['avg_rating'] ?? 0,
        'total' => (int) ($row['total'] ?? 0),
    ];
}

function product_main_image($product) {
    if (!empty($product['main_image'])) return $product['main_image'];
    return BASE_URL . 'assets/img/placeholder.png';
}

function discount_percent($price, $oldPrice) {
    if ($oldPrice && $oldPrice > $price) {
        return round((($oldPrice - $price) / $oldPrice) * 100);
    }
    return 0;
}

/* ---------------------------------------------------------
   Cart (session based, guest + member)
   $_SESSION['cart'][key] = ['product_id'=>, 'variant_id'=>, 'qty'=>]
--------------------------------------------------------- */

function cart_key($productId, $variantId) {
    return $productId . '-' . ($variantId ?: 0);
}

function &cart_items() {
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    return $_SESSION['cart'];
}

function cart_add($productId, $qty = 1, $variantId = null) {
    $cart = &cart_items();
    $key = cart_key($productId, $variantId);
    if (isset($cart[$key])) {
        $cart[$key]['qty'] += $qty;
    } else {
        $cart[$key] = ['product_id' => (int) $productId, 'variant_id' => $variantId ? (int) $variantId : null, 'qty' => (int) $qty];
    }
}

function cart_update($key, $qty) {
    $cart = &cart_items();
    if (isset($cart[$key])) {
        if ($qty <= 0) unset($cart[$key]);
        else $cart[$key]['qty'] = (int) $qty;
    }
}

function cart_remove($key) {
    $cart = &cart_items();
    unset($cart[$key]);
}

function cart_clear() {
    $_SESSION['cart'] = [];
}

function cart_count() {
    $count = 0;
    foreach (cart_items() as $item) $count += $item['qty'];
    return $count;
}

/**
 * Loads live cart data from the database. Prices/stock are NEVER trusted from
 * client/session — only product_id, variant_id and qty come from session.
 */
function cart_details() {
    global $pdo;
    $result = [];
    $subtotal = 0;
    foreach (cart_items() as $key => $item) {
        $stmt = $pdo->prepare("SELECT p.id, p.name, p.slug, p.main_image, p.price, p.stock, p.status
                                FROM products p WHERE p.id = ?");
        $stmt->execute([$item['product_id']]);
        $product = $stmt->fetch();
        if (!$product || $product['status'] !== 'active') continue;

        $price = (float) $product['price'];
        $stock = (int) $product['stock'];
        $variantLabel = null;

        if (!empty($item['variant_id'])) {
            $vstmt = $pdo->prepare("SELECT * FROM product_variants WHERE id = ? AND product_id = ? AND status = 'active'");
            $vstmt->execute([$item['variant_id'], $item['product_id']]);
            $variant = $vstmt->fetch();
            if ($variant) {
                if ($variant['price'] !== null) $price = (float) $variant['price'];
                $stock = (int) $variant['stock'];
                $variantLabel = $variant['variant_name'] . ': ' . $variant['variant_value'];
            }
        }

        $qty = min($item['qty'], max($stock, 0));
        if ($qty <= 0) continue;

        $lineTotal = $price * $qty;
        $subtotal += $lineTotal;

        $result[] = [
            'key'          => $key,
            'product_id'   => $product['id'],
            'variant_id'   => $item['variant_id'],
            'name'         => $product['name'],
            'slug'         => $product['slug'],
            'image'        => $product['main_image'],
            'variant_label'=> $variantLabel,
            'price'        => $price,
            'qty'          => $qty,
            'stock'        => $stock,
            'line_total'   => $lineTotal,
        ];
    }
    return ['items' => $result, 'subtotal' => $subtotal];
}

/* ---------------------------------------------------------
   Coupons
--------------------------------------------------------- */

function validate_coupon($code, $subtotal) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$code]);
    $coupon = $stmt->fetch();
    if (!$coupon) return ['valid' => false, 'message' => 'Invalid coupon code.'];

    $now = date('Y-m-d H:i:s');
    if ($coupon['starts_at'] && $coupon['starts_at'] > $now) return ['valid' => false, 'message' => 'Coupon is not active yet.'];
    if ($coupon['expires_at'] && $coupon['expires_at'] < $now) return ['valid' => false, 'message' => 'Coupon has expired.'];
    if ($coupon['usage_limit'] !== null && $coupon['used_count'] >= $coupon['usage_limit']) {
        return ['valid' => false, 'message' => 'Coupon usage limit reached.'];
    }
    if ($subtotal < $coupon['minimum_order']) {
        return ['valid' => false, 'message' => 'Minimum order of ' . format_price($coupon['minimum_order']) . ' required.'];
    }

    if (!empty($_SESSION['user_id']) && $coupon['per_user_limit'] !== null) {
        $ustmt = $pdo->prepare("SELECT COUNT(*) FROM coupon_usages WHERE coupon_id = ? AND user_id = ?");
        $ustmt->execute([$coupon['id'], $_SESSION['user_id']]);
        if ($ustmt->fetchColumn() >= $coupon['per_user_limit']) {
            return ['valid' => false, 'message' => 'You have already used this coupon.'];
        }
    }

    $discount = $coupon['discount_type'] === 'percentage'
        ? $subtotal * ($coupon['discount_value'] / 100)
        : $coupon['discount_value'];

    if ($coupon['maximum_discount'] !== null) {
        $discount = min($discount, $coupon['maximum_discount']);
    }
    $discount = min($discount, $subtotal);

    return ['valid' => true, 'coupon' => $coupon, 'discount' => round($discount, 2)];
}

/* ---------------------------------------------------------
   Misc
--------------------------------------------------------- */

function generate_order_number() {
    return 'BB' . date('ymd') . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
}

function time_ago($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 2592000) return floor($diff / 86400) . 'd ago';
    return date('M j, Y', strtotime($datetime));
}

function paginate($totalItems, $perPage, $currentPage) {
    $totalPages = max(1, (int) ceil($totalItems / $perPage));
    $currentPage = max(1, min($currentPage, $totalPages));
    return [
        'total_pages'  => $totalPages,
        'current_page' => $currentPage,
        'offset'       => ($currentPage - 1) * $perPage,
    ];
}

/* ---------------------------------------------------------
   Best Sellers / Verified Merchants
--------------------------------------------------------- */

function get_best_sellers_list() {
    return [
        [
            'id'             => 'bb-official',
            'name'           => 'B&B Official Flagship Store',
            'owner'          => 'B&B Corporate Team',
            'badge'          => '🏆 Flagship Store',
            'badge_color'    => '#d4a72c',
            'rating'         => 4.9,
            'reviews_count'  => 520,
            'orders_count'   => '4,800+ Orders',
            'response_rate'  => '99% Response Rate',
            'response_time'  => 'Under 10 mins',
            'location'       => 'GEC Circle, Chattogram',
            'phone'          => '+880 1812-345678',
            'whatsapp'       => '8801812345678',
            'email'          => 'flagship@bbtradersbd.com',
            'joined'         => 'March 2023',
            'category_slug'  => 'fashion',
            'category_name'  => 'Fashion & Apparel',
            'specialty'      => 'Original Branded Apparel & Premium Goods',
            'bio'            => 'The premier flagship store of B&B Traders BD offering direct factory-certified products with guaranteed original quality and same-day express handling.',
            'avatar'         => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=200&q=80',
        ],
        [
            'id'             => 'chattogram-sports-hub',
            'name'           => 'Chattogram Jersey & Sports Hub',
            'owner'          => 'Tanvir Ahmed',
            'badge'          => '⭐ Top Rated Seller',
            'badge_color'    => '#087a3d',
            'rating'         => 4.9,
            'reviews_count'  => 410,
            'orders_count'   => '3,250+ Orders',
            'response_rate'  => '98% Response Rate',
            'response_time'  => 'Under 20 mins',
            'location'       => 'Agrabad Commercial Area, Chattogram',
            'phone'          => '+880 1711-223344',
            'whatsapp'       => '8801711223344',
            'email'          => 'sports.hub@bbtradersbd.com',
            'joined'         => 'July 2023',
            'category_slug'  => 'world-cup-jersey',
            'category_name'  => 'World Cup Jersey & Sportswear',
            'specialty'      => 'Authentic Player & Fan Edition Football Kits',
            'bio'            => 'Specialized in premium World Cup jerseys, fan edition kits, and athletic gear. Direct imports with embroidery detailing and sweat-wicking dri-fit fabric.',
            'avatar'         => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=200&q=80',
        ],
        [
            'id'             => 'glamour-glow-bd',
            'name'           => 'Glamour & Glow Cosmetics',
            'owner'          => 'Nusrat Jahan',
            'badge'          => '💎 Gold Merchant',
            'badge_color'    => '#b83280',
            'rating'         => 4.8,
            'reviews_count'  => 340,
            'orders_count'   => '2,600+ Orders',
            'response_rate'  => '97% Response Rate',
            'response_time'  => 'Under 30 mins',
            'location'       => 'Dhanmondi, Dhaka',
            'phone'          => '+880 1912-987654',
            'whatsapp'       => '8801912987654',
            'email'          => 'glamour.bd@bbtradersbd.com',
            'joined'         => 'November 2023',
            'category_slug'  => 'beauty',
            'category_name'  => 'Beauty & Personal Care',
            'specialty'      => 'Dermatologically Tested Skin & Personal Care',
            'bio'            => '100% authentic international and herbal beauty care products, skincare sets, and premium cosmetics with safe packaging and prompt delivery.',
            'avatar'         => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=200&q=80',
        ],
        [
            'id'             => 'heritage-boutique-bd',
            'name'           => 'Heritage Women Boutique & Crafts',
            'owner'          => 'Farhana Kabir',
            'badge'          => '👑 Verified Artisan',
            'badge_color'    => '#c05621',
            'rating'         => 4.9,
            'reviews_count'  => 290,
            'orders_count'   => '1,950+ Orders',
            'response_rate'  => '99% Response Rate',
            'response_time'  => 'Under 15 mins',
            'location'       => 'Nasirabad, Chattogram',
            'phone'          => '+880 1823-456789',
            'whatsapp'       => '8801823456789',
            'email'          => 'heritage@bbtradersbd.com',
            'joined'         => 'January 2024',
            'category_slug'  => 'three-piece',
            'category_name'  => 'Three-Piece & Women\'s Fashion',
            'specialty'      => 'Handcrafted Embroidery & Premium Lawn / Silk Collections',
            'bio'            => 'Exclusive women’s traditional three-piece suits, formal party wear, and casual comfortable cotton dresses crafted with high thread-count fabric.',
            'avatar'         => 'https://images.unsplash.com/photo-1580489944761-15a19d654956?w=200&q=80',
        ],
        [
            'id'             => 'smarttech-gadgets',
            'name'           => 'SmartTech Gadgets & Wearables',
            'owner'          => 'Kamrul Hasan',
            'badge'          => '🚀 Tech Verified',
            'badge_color'    => '#2b6cb0',
            'rating'         => 4.7,
            'reviews_count'  => 265,
            'orders_count'   => '1,800+ Orders',
            'response_rate'  => '96% Response Rate',
            'response_time'  => 'Under 45 mins',
            'location'       => 'Mirpur, Dhaka',
            'phone'          => '+880 1612-334455',
            'whatsapp'       => '8801612334455',
            'email'          => 'smarttech@bbtradersbd.com',
            'joined'         => 'February 2024',
            'category_slug'  => 'smart-watch',
            'category_name'  => 'Smart Watch & Appliances',
            'specialty'      => 'Smart Watches, Wearable Tech & Smart Home Devices',
            'bio'            => 'Authorised reseller of trending smart gadgets, smartwatches with warranty, and reliable home accessories with replacement support.',
            'avatar'         => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=200&q=80',
        ],
    ];
}

function get_seller_by_id($id) {
    foreach (get_best_sellers_list() as $s) {
        if ($s['id'] === $id) return $s;
    }
    return null;
}

