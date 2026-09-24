-- ============================================================
-- B&B TRADERS BD
-- COMPLETE PREMIUM E-COMMERCE DATABASE
-- PHP + MySQL / MariaDB
-- ============================================================

-- ============================================================
-- WARNING
-- ============================================================
-- This script creates a fresh database.
-- It will DELETE the existing bb_traders_bd database.

-- BACK UP YOUR EXISTING DATABASE BEFORE RUNNING THIS SCRIPT.
-- ============================================================




CREATE DATABASE ezyro_42998533_bb_traders_bd
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE ezyro_42998533_bb_traders_bd;


-- ============================================================
-- 1. USERS
-- ============================================================

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    first_name VARCHAR(80) NOT NULL,
    last_name VARCHAR(80) NOT NULL,

    email VARCHAR(150) NOT NULL,
    password VARCHAR(255) NOT NULL,

    phone VARCHAR(30) DEFAULT NULL,

    address TEXT DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    postal_code VARCHAR(20) DEFAULT NULL,

    avatar VARCHAR(255) DEFAULT NULL,

    role ENUM(
        'member',
        'admin',
        'super_admin'
    ) NOT NULL DEFAULT 'member',

    status ENUM(
        'active',
        'inactive',
        'blocked'
    ) NOT NULL DEFAULT 'active',

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role (role),
    KEY idx_users_status (status),
    KEY idx_users_created (created_at)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 2. USER ADDRESSES
-- ============================================================

CREATE TABLE user_addresses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    user_id INT UNSIGNED NOT NULL,

    address_name VARCHAR(100) DEFAULT NULL,

    full_name VARCHAR(160) NOT NULL,
    phone VARCHAR(30) NOT NULL,

    address_line1 VARCHAR(255) NOT NULL,
    address_line2 VARCHAR(255) DEFAULT NULL,

    city VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20) DEFAULT NULL,

    is_default TINYINT(1) NOT NULL DEFAULT 0,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_user_addresses_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    KEY idx_user_addresses_user (user_id),
    KEY idx_user_addresses_default (is_default)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 3. CATEGORIES
-- ============================================================

CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(100) NOT NULL,
    slug VARCHAR(120) NOT NULL,
    description TEXT DEFAULT NULL,

    icon VARCHAR(100) DEFAULT NULL,
    image VARCHAR(500) DEFAULT NULL,

    status ENUM(
        'active',
        'inactive'
    ) NOT NULL DEFAULT 'active',

    sort_order INT NOT NULL DEFAULT 0,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_categories_slug (slug),
    KEY idx_categories_status (status),
    KEY idx_categories_sort (sort_order)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 4. PRODUCTS
-- ============================================================

CREATE TABLE products (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    category_id INT UNSIGNED NOT NULL,

    name VARCHAR(200) NOT NULL,
    slug VARCHAR(220) NOT NULL,
    sku VARCHAR(100) NOT NULL,

    product_type ENUM(
        'Physical',
        'Digital'
    ) NOT NULL DEFAULT 'Physical',

    description TEXT DEFAULT NULL,

    price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    old_price DECIMAL(12,2) DEFAULT NULL,

    main_image VARCHAR(500) DEFAULT NULL,

    badge ENUM(
        'NEW',
        'HOT',
        'SALE'
    ) DEFAULT NULL,

    stock INT UNSIGNED NOT NULL DEFAULT 0,

    featured TINYINT(1) NOT NULL DEFAULT 0,

    status ENUM(
        'active',
        'inactive'
    ) NOT NULL DEFAULT 'active',

    meta_title VARCHAR(255) DEFAULT NULL,
    meta_description TEXT DEFAULT NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_products_category
        FOREIGN KEY (category_id)
        REFERENCES categories(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    UNIQUE KEY uq_products_slug (slug),
    UNIQUE KEY uq_products_sku (sku),

    KEY idx_products_category (category_id),
    KEY idx_products_type (product_type),
    KEY idx_products_price (price),
    KEY idx_products_stock (stock),
    KEY idx_products_featured (featured),
    KEY idx_products_status (status),
    KEY idx_products_created (created_at),

    FULLTEXT KEY ft_products_search (
        name,
        description
    )
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 5. PRODUCT IMAGES
-- ============================================================

CREATE TABLE product_images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    product_id INT UNSIGNED NOT NULL,

    image_path VARCHAR(500) NOT NULL,
    alt_text VARCHAR(255) DEFAULT NULL,

    sort_order INT NOT NULL DEFAULT 0,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_product_images_product
        FOREIGN KEY (product_id)
        REFERENCES products(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    KEY idx_product_images_product (product_id),
    KEY idx_product_images_sort (sort_order)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 6. PRODUCT VARIANTS
-- ============================================================

CREATE TABLE product_variants (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    product_id INT UNSIGNED NOT NULL,

    variant_name VARCHAR(100) NOT NULL,
    variant_value VARCHAR(100) NOT NULL,

    -- NULL means use the main product price
    price DECIMAL(12,2) DEFAULT NULL,

    stock INT UNSIGNED NOT NULL DEFAULT 0,

    sku VARCHAR(100) DEFAULT NULL,

    is_default TINYINT(1) NOT NULL DEFAULT 0,

    status ENUM(
        'active',
        'inactive'
    ) NOT NULL DEFAULT 'active',

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_product_variants_product
        FOREIGN KEY (product_id)
        REFERENCES products(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    UNIQUE KEY uq_product_variant_sku (sku),

    KEY idx_product_variants_product (product_id),
    KEY idx_product_variants_name (variant_name),
    KEY idx_product_variants_status (status)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 7. PRODUCT DIGITAL FILES
-- ============================================================

CREATE TABLE product_digital_files (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    product_id INT UNSIGNED NOT NULL,

    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_product_digital_files_product
        FOREIGN KEY (product_id)
        REFERENCES products(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    KEY idx_product_digital_files_product (product_id)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 8. WISHLISTS
-- ============================================================

CREATE TABLE wishlists (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    user_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_wishlists_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_wishlists_product
        FOREIGN KEY (product_id)
        REFERENCES products(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    UNIQUE KEY uq_wishlist_user_product (
        user_id,
        product_id
    ),

    KEY idx_wishlists_user (user_id),
    KEY idx_wishlists_product (product_id)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 9. COUPONS
-- ============================================================

CREATE TABLE coupons (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    code VARCHAR(50) NOT NULL,

    discount_type ENUM(
        'percentage',
        'fixed'
    ) NOT NULL,

    discount_value DECIMAL(12,2) NOT NULL DEFAULT 0.00,

    minimum_order DECIMAL(12,2) NOT NULL DEFAULT 0.00,

    maximum_discount DECIMAL(12,2) DEFAULT NULL,

    usage_limit INT UNSIGNED DEFAULT NULL,
    used_count INT UNSIGNED NOT NULL DEFAULT 0,

    per_user_limit INT UNSIGNED DEFAULT NULL,

    starts_at DATETIME DEFAULT NULL,
    expires_at DATETIME DEFAULT NULL,

    status ENUM(
        'active',
        'inactive'
    ) NOT NULL DEFAULT 'active',

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_coupons_code (code),

    KEY idx_coupons_status (status),
    KEY idx_coupons_expiry (expires_at)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 10. ORDERS
-- ============================================================

CREATE TABLE orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    user_id INT UNSIGNED DEFAULT NULL,

    order_number VARCHAR(80) NOT NULL,

    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    shipping_cost DECIMAL(12,2) NOT NULL DEFAULT 0.00,

    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,

    currency VARCHAR(10) NOT NULL DEFAULT 'BDT',

    coupon_id INT UNSIGNED DEFAULT NULL,
    coupon_code VARCHAR(50) DEFAULT NULL,

    payment_method ENUM(
        'cod',
        'bkash',
        'nagad',
        'stripe'
    ) NOT NULL,

    payment_status ENUM(
        'pending',
        'paid',
        'failed',
        'refunded'
    ) NOT NULL DEFAULT 'pending',

    order_status ENUM(
        'pending',
        'confirmed',
        'processing',
        'shipped',
        'delivered',
        'cancelled',
        'returned'
    ) NOT NULL DEFAULT 'pending',

    -- Customer snapshot at the time of order
    shipping_name VARCHAR(160) NOT NULL,
    shipping_email VARCHAR(150) DEFAULT NULL,
    shipping_phone VARCHAR(30) NOT NULL,

    shipping_address TEXT NOT NULL,
    shipping_city VARCHAR(100) NOT NULL,
    shipping_postal_code VARCHAR(20) DEFAULT NULL,

    notes TEXT DEFAULT NULL,

    stripe_session_id VARCHAR(255) DEFAULT NULL,
    stripe_payment_intent_id VARCHAR(255) DEFAULT NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_orders_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    CONSTRAINT fk_orders_coupon
        FOREIGN KEY (coupon_id)
        REFERENCES coupons(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    UNIQUE KEY uq_orders_order_number (order_number),

    KEY idx_orders_user (user_id),
    KEY idx_orders_coupon (coupon_id),
    KEY idx_orders_payment_method (payment_method),
    KEY idx_orders_payment_status (payment_status),
    KEY idx_orders_order_status (order_status),
    KEY idx_orders_created (created_at),
    KEY idx_orders_stripe_session (stripe_session_id)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 11. ORDER ITEMS
-- ============================================================

CREATE TABLE order_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    order_id INT UNSIGNED NOT NULL,

    product_id INT UNSIGNED NOT NULL,
    variant_id INT UNSIGNED DEFAULT NULL,

    -- Historical snapshot
    product_name VARCHAR(200) NOT NULL,
    variant_name VARCHAR(255) DEFAULT NULL,

    quantity INT UNSIGNED NOT NULL DEFAULT 1,

    unit_price DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0.00,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_order_items_order
        FOREIGN KEY (order_id)
        REFERENCES orders(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_order_items_product
        FOREIGN KEY (product_id)
        REFERENCES products(id)
        ON DELETE RESTRICT
        ON UPDATE CASCADE,

    CONSTRAINT fk_order_items_variant
        FOREIGN KEY (variant_id)
        REFERENCES product_variants(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    KEY idx_order_items_order (order_id),
    KEY idx_order_items_product (product_id),
    KEY idx_order_items_variant (variant_id)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 12. PAYMENTS
-- ============================================================

CREATE TABLE payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    order_id INT UNSIGNED NOT NULL,

    payment_method ENUM(
        'cod',
        'bkash',
        'nagad',
        'stripe'
    ) NOT NULL,

    transaction_id VARCHAR(255) DEFAULT NULL,

    amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,

    currency VARCHAR(10) NOT NULL DEFAULT 'BDT',

    status ENUM(
        'pending',
        'paid',
        'failed',
        'refunded'
    ) NOT NULL DEFAULT 'pending',

    gateway_reference VARCHAR(255) DEFAULT NULL,

    gateway_response TEXT DEFAULT NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_payments_order
        FOREIGN KEY (order_id)
        REFERENCES orders(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    KEY idx_payments_order (order_id),
    KEY idx_payments_transaction (transaction_id),
    KEY idx_payments_status (status),
    KEY idx_payments_method (payment_method)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 13. REVIEWS
-- ============================================================

CREATE TABLE reviews (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    product_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    order_id INT UNSIGNED NOT NULL,

    rating TINYINT UNSIGNED NOT NULL,

    review_text TEXT DEFAULT NULL,

    status ENUM(
        'pending',
        'approved',
        'rejected'
    ) NOT NULL DEFAULT 'pending',

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_reviews_product
        FOREIGN KEY (product_id)
        REFERENCES products(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_reviews_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_reviews_order
        FOREIGN KEY (order_id)
        REFERENCES orders(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    UNIQUE KEY uq_review_user_order_product (
        user_id,
        order_id,
        product_id
    ),

    KEY idx_reviews_product (product_id),
    KEY idx_reviews_user (user_id),
    KEY idx_reviews_order (order_id),
    KEY idx_reviews_status (status),
    KEY idx_reviews_rating (rating)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 14. COUPON USAGES
-- ============================================================

CREATE TABLE coupon_usages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    coupon_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED DEFAULT NULL,
    order_id INT UNSIGNED NOT NULL,

    discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,

    used_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_coupon_usages_coupon
        FOREIGN KEY (coupon_id)
        REFERENCES coupons(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_coupon_usages_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE SET NULL
        ON UPDATE CASCADE,

    CONSTRAINT fk_coupon_usages_order
        FOREIGN KEY (order_id)
        REFERENCES orders(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    UNIQUE KEY uq_coupon_usage_order (
        coupon_id,
        order_id
    ),

    KEY idx_coupon_usages_coupon (coupon_id),
    KEY idx_coupon_usages_user (user_id),
    KEY idx_coupon_usages_order (order_id)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 15. NEWSLETTER SUBSCRIBERS
-- ============================================================

CREATE TABLE newsletter_subscribers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    email VARCHAR(150) NOT NULL,

    status ENUM(
        'active',
        'unsubscribed'
    ) NOT NULL DEFAULT 'active',

    subscribed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    unsubscribed_at TIMESTAMP NULL DEFAULT NULL,

    UNIQUE KEY uq_newsletter_email (email),

    KEY idx_newsletter_status (status)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 16. SETTINGS
-- ============================================================

CREATE TABLE settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT NOT NULL,

    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_settings_key (setting_key)
)
ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 17. INSERT CATEGORIES
-- ============================================================

INSERT INTO categories (
    id,
    name,
    slug,
    description,
    icon,
    status,
    sort_order
)
VALUES

(1,
 'World Cup Jersey',
 'world-cup-jersey',
 'Football jerseys and fan wear.',
 '⚽',
 'active',
 1),

(2,
 'Fashion',
 'fashion',
 'Modern fashion products for everyday wear.',
 '👕',
 'active',
 2),

(3,
 'Beauty',
 'beauty',
 'Beauty and personal care products.',
 '💄',
 'active',
 3),

(4,
 'Appliances',
 'appliances',
 'Useful home and kitchen appliances.',
 '🧺',
 'active',
 4),

(5,
 'Three-Piece',
 'three-piece',
 'Elegant three-piece fashion collections.',
 '🛍️',
 'active',
 5),

(6,
 'Kids',
 'kids',
 'Comfortable and stylish products for kids.',
 '🧸',
 'active',
 6),

(7,
 'Couple & Combo Set',
 'couple-combo-set',
 'Matching couple and combo products.',
 '👩‍❤️‍👨',
 'active',
 7),

(8,
 'Trendy',
 'trendy',
 'Latest and trendy lifestyle products.',
 '📈',
 'active',
 8),

(9,
 'Smart Watch',
 'smart-watch',
 'Smart watches and wearable products.',
 '⌚',
 'active',
 9),

(10,
 'Women''s Fashion',
 'womens-fashion',
 'Modern women''s fashion products.',
 '💃',
 'active',
 10);


-- ============================================================
-- 18. INSERT SUPER ADMIN
-- ============================================================
-- Email:
-- admin@bbtradersbd.com
--
-- Password:
-- Admin@1234
--
-- The password hash below was generated using password_hash().
-- ============================================================

INSERT INTO users (
    first_name,
    last_name,
    email,
    password,
    role,
    status
)
VALUES (
    'Super',
    'Admin',
    'admin@bbtradersbd.com',
    '$2y$10$IHDQwagf1JxAncjpyZj5oefo9utzJ7AQG0bez9kMXj2aEexbnIFBa',
    'super_admin',
    'active'
);


-- ============================================================
-- 19. INSERT DEMO CUSTOMER
-- ============================================================

INSERT INTO users (
    first_name,
    last_name,
    email,
    password,
    phone,
    address,
    city,
    postal_code,
    role,
    status
)
VALUES (
    'Demo',
    'Customer',
    'demo@bbtradersbd.com',
    '$2y$12$yL/.Tu0dJvtvZbAoVLFe6.zBgMPpq4XfMEiaRJlWXp6coNpBoHeSy',
    '01700000000',
    'Demo Customer Address',
    'Chattogram',
    '4000',
    'member',
    'active'
);


-- ============================================================
-- 20. INSERT DEMO USER ADDRESS
-- ============================================================

INSERT INTO user_addresses (
    user_id,
    address_name,
    full_name,
    phone,
    address_line1,
    city,
    postal_code,
    is_default
)
VALUES (
    2,
    'Home',
    'Demo Customer',
    '01700000000',
    'Demo Customer Address',
    'Chattogram',
    '4000',
    1
);


-- ============================================================
-- 21. INSERT PRODUCTS
-- ============================================================

INSERT INTO products (
    id,
    category_id,
    name,
    slug,
    sku,
    product_type,
    description,
    price,
    old_price,
    main_image,
    badge,
    stock,
    featured,
    status
)
VALUES

(
    1,
    1,
    'Premium World Cup Football Jersey',
    'premium-world-cup-football-jersey',
    'BB-WCJ-001',
    'Physical',
    'Premium football jersey designed for football fans with a comfortable fit and sporty appearance.',
    1499.00,
    1999.00,
    'https://images.unsplash.com/photo-1579952363873-27f3bade9f55?w=1000&q=85',
    'HOT',
    120,
    1,
    'active'
),

(
    2,
    1,
    'Classic Football Fan Jersey',
    'classic-football-fan-jersey',
    'BB-WCJ-002',
    'Physical',
    'Comfortable football jersey suitable for match days, casual wear and sports activities.',
    1299.00,
    1699.00,
    'https://images.unsplash.com/photo-1526232761682-d26e03ac148e?w=1000&q=85',
    'SALE',
    150,
    1,
    'active'
),

(
    3,
    2,
    'Premium Casual T Shirt',
    'premium-casual-t-shirt',
    'BB-FSH-001',
    'Physical',
    'Modern casual t-shirt designed for comfortable everyday wear.',
    899.00,
    1199.00,
    'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=1000&q=85',
    'NEW',
    200,
    1,
    'active'
),

(
    4,
    2,
    'Modern Casual Shirt',
    'modern-casual-shirt',
    'BB-FSH-002',
    'Physical',
    'Stylish casual shirt suitable for everyday outfits and smart casual occasions.',
    1299.00,
    1699.00,
    'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?w=1000&q=85',
    'HOT',
    100,
    1,
    'active'
),

(
    5,
    3,
    'Professional Beauty Care Set',
    'professional-beauty-care-set',
    'BB-BEA-001',
    'Physical',
    'Complete beauty care set designed for convenient everyday personal care.',
    1899.00,
    2499.00,
    'https://images.unsplash.com/photo-1596462502278-27bfdc403348?w=1000&q=85',
    'SALE',
    80,
    1,
    'active'
),

(
    6,
    4,
    'Modern Kitchen Appliance',
    'modern-kitchen-appliance',
    'BB-APP-001',
    'Physical',
    'Practical modern kitchen appliance designed to make everyday household tasks easier.',
    4999.00,
    5999.00,
    'https://images.unsplash.com/photo-1556911220-bff31c812dba?w=1000&q=85',
    'HOT',
    40,
    1,
    'active'
),

(
    7,
    5,
    'Elegant Three Piece Dress',
    'elegant-three-piece-dress',
    'BB-TP-001',
    'Physical',
    'Elegant three-piece outfit suitable for celebrations, occasions and stylish everyday wear.',
    2499.00,
    3299.00,
    'https://images.unsplash.com/photo-1583391733956-6c78276477e2?w=1000&q=85',
    'NEW',
    75,
    1,
    'active'
),

(
    8,
    6,
    'Kids Casual Fashion Set',
    'kids-casual-fashion-set',
    'BB-KID-001',
    'Physical',
    'Comfortable and stylish casual clothing set designed for kids.',
    1199.00,
    1499.00,
    'https://images.unsplash.com/photo-1503919545889-aef636e10ad4?w=1000&q=85',
    'NEW',
    90,
    1,
    'active'
),

(
    9,
    7,
    'Matching Couple Fashion Set',
    'matching-couple-fashion-set',
    'BB-COUP-001',
    'Physical',
    'Matching couple outfit designed for coordinated casual looks and special occasions.',
    2999.00,
    3999.00,
    'https://images.unsplash.com/photo-1529139574466-a303027c1d8b?w=1000&q=85',
    'HOT',
    60,
    1,
    'active'
),

(
    10,
    8,
    'Trendy Everyday Fashion Item',
    'trendy-everyday-fashion-item',
    'BB-TRN-001',
    'Physical',
    'A modern trendy fashion item designed for stylish everyday looks.',
    1599.00,
    1999.00,
    'https://images.unsplash.com/photo-1445205170230-053b83016050?w=1000&q=85',
    'SALE',
    110,
    1,
    'active'
),

(
    11,
    9,
    'Smart Watch Pro',
    'smart-watch-pro',
    'BB-SW-001',
    'Physical',
    'Modern smart watch with fitness tracking, notifications and everyday smart features.',
    4999.00,
    5999.00,
    'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=1000&q=85',
    'NEW',
    70,
    1,
    'active'
),

(
    12,
    10,
    'Elegant Womens Fashion Collection',
    'elegant-womens-fashion-collection',
    'BB-WF-001',
    'Physical',
    'Elegant womens fashion collection designed for modern and stylish everyday looks.',
    2199.00,
    2899.00,
    'https://images.unsplash.com/photo-1483985988355-763728e1935b?w=1000&q=85',
    'HOT',
    85,
    1,
    'active'
),

(
    13,
    8,
    'Digital Fashion Guide',
    'digital-fashion-guide',
    'BB-DIG-001',
    'Digital',
    'A downloadable digital fashion guide with useful styling ideas and fashion references.',
    499.00,
    699.00,
    'https://images.unsplash.com/photo-1483985988355-763728e1935b?w=1000&q=85',
    'NEW',
    0,
    0,
    'active'
);


-- ============================================================
-- 22. PRODUCT IMAGES
-- ============================================================

INSERT INTO product_images (
    product_id,
    image_path,
    alt_text,
    sort_order
)
VALUES

(1,
 'https://images.unsplash.com/photo-1579952363873-27f3bade9f55?w=1000&q=85',
 'Premium World Cup Football Jersey',
 1),

(2,
 'https://images.unsplash.com/photo-1526232761682-d26e03ac148e?w=1000&q=85',
 'Classic Football Fan Jersey',
 1),

(3,
 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=1000&q=85',
 'Premium Casual T Shirt',
 1),

(4,
 'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?w=1000&q=85',
 'Modern Casual Shirt',
 1),

(5,
 'https://images.unsplash.com/photo-1596462502278-27bfdc403348?w=1000&q=85',
 'Professional Beauty Care Set',
 1),

(6,
 'https://images.unsplash.com/photo-1556911220-bff31c812dba?w=1000&q=85',
 'Modern Kitchen Appliance',
 1),

(7,
 'https://images.unsplash.com/photo-1583391733956-6c78276477e2?w=1000&q=85',
 'Elegant Three Piece Dress',
 1),

(8,
 'https://images.unsplash.com/photo-1503919545889-aef636e10ad4?w=1000&q=85',
 'Kids Casual Fashion Set',
 1),

(9,
 'https://images.unsplash.com/photo-1529139574466-a303027c1d8b?w=1000&q=85',
 'Matching Couple Fashion Set',
 1),

(10,
 'https://images.unsplash.com/photo-1445205170230-053b83016050?w=1000&q=85',
 'Trendy Everyday Fashion Item',
 1),

(11,
 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=1000&q=85',
 'Smart Watch Pro',
 1),

(12,
 'https://images.unsplash.com/photo-1483985988355-763728e1935b?w=1000&q=85',
 'Elegant Womens Fashion Collection',
 1),

(13,
 'https://images.unsplash.com/photo-1483985988355-763728e1935b?w=1000&q=85',
 'Digital Fashion Guide',
 1);


-- ============================================================
-- 23. PRODUCT VARIANTS
-- ============================================================

INSERT INTO product_variants (
    product_id,
    variant_name,
    variant_value,
    price,
    stock,
    sku,
    is_default,
    status
)
VALUES

-- World Cup Jersey
(1, 'Size', 'S',   1499.00, 20, 'BB-WCJ-001-S',   0, 'active'),
(1, 'Size', 'M',   1499.00, 25, 'BB-WCJ-001-M',   1, 'active'),
(1, 'Size', 'L',   1499.00, 30, 'BB-WCJ-001-L',   0, 'active'),
(1, 'Size', 'XL',  1499.00, 25, 'BB-WCJ-001-XL',  0, 'active'),
(1, 'Size', 'XXL', 1499.00, 20, 'BB-WCJ-001-XXL', 0, 'active'),

-- Classic Jersey
(2, 'Size', 'S',   1299.00, 30, 'BB-WCJ-002-S',  0, 'active'),
(2, 'Size', 'M',   1299.00, 35, 'BB-WCJ-002-M',  1, 'active'),
(2, 'Size', 'L',   1299.00, 35, 'BB-WCJ-002-L',  0, 'active'),
(2, 'Size', 'XL',  1299.00, 30, 'BB-WCJ-002-XL', 0, 'active'),
(2, 'Size', 'XXL', 1299.00, 20, 'BB-WCJ-002-XXL',0, 'active'),

-- T Shirt
(3, 'Size', 'S',  899.00, 40, 'BB-FSH-001-S', 0, 'active'),
(3, 'Size', 'M',  899.00, 45, 'BB-FSH-001-M', 1, 'active'),
(3, 'Size', 'L',  899.00, 45, 'BB-FSH-001-L', 0, 'active'),
(3, 'Size', 'XL', 899.00, 40, 'BB-FSH-001-XL',0, 'active'),
(3, 'Size', 'XXL',899.00, 30, 'BB-FSH-001-XXL',0,'active'),

-- Casual Shirt
(4, 'Size', 'M',  1299.00, 25, 'BB-FSH-002-M', 1, 'active'),
(4, 'Size', 'L',  1299.00, 30, 'BB-FSH-002-L', 0, 'active'),
(4, 'Size', 'XL', 1299.00, 25, 'BB-FSH-002-XL',0, 'active'),
(4, 'Size', 'XXL',1299.00,20, 'BB-FSH-002-XXL',0,'active'),

-- Three Piece
(7, 'Size', 'S',  2499.00, 15, 'BB-TP-001-S', 1, 'active'),
(7, 'Size', 'M',  2499.00, 20, 'BB-TP-001-M', 0, 'active'),
(7, 'Size', 'L',  2499.00, 20, 'BB-TP-001-L', 0, 'active'),
(7, 'Size', 'XL', 2499.00, 20, 'BB-TP-001-XL',0, 'active'),

-- Kids
(8, 'Age', '2Y',  1199.00, 15, 'BB-KID-001-2Y',1,'active'),
(8, 'Age', '4Y',  1199.00, 15, 'BB-KID-001-4Y',0,'active'),
(8, 'Age', '6Y',  1199.00, 15, 'BB-KID-001-6Y',0,'active'),
(8, 'Age', '8Y',  1199.00, 15, 'BB-KID-001-8Y',0,'active'),
(8, 'Age', '10Y', 1199.00,15, 'BB-KID-001-10Y',0,'active'),
(8, 'Age', '12Y', 1199.00,15, 'BB-KID-001-12Y',0,'active'),

-- Couple Set
(9, 'Size', 'S',  2999.00, 10, 'BB-COUP-001-S',1,'active'),
(9, 'Size', 'M',  2999.00, 15, 'BB-COUP-001-M',0,'active'),
(9, 'Size', 'L',  2999.00, 15, 'BB-COUP-001-L',0,'active'),
(9, 'Size', 'XL', 2999.00, 15, 'BB-COUP-001-XL',0,'active'),

-- Trendy
(10, 'Size', 'S',  1599.00, 25, 'BB-TRN-001-S',1,'active'),
(10, 'Size', 'M',  1599.00, 30, 'BB-TRN-001-M',0,'active'),
(10, 'Size', 'L',  1599.00, 30, 'BB-TRN-001-L',0,'active'),
(10, 'Size', 'XL', 1599.00, 25, 'BB-TRN-001-XL',0,'active'),

-- Women's Fashion
(12, 'Size', 'S',  2199.00, 15, 'BB-WF-001-S',1,'active'),
(12, 'Size', 'M',  2199.00, 20, 'BB-WF-001-M',0,'active'),
(12, 'Size', 'L',  2199.00, 25, 'BB-WF-001-L',0,'active'),
(12, 'Size', 'XL', 2199.00, 25, 'BB-WF-001-XL',0,'active');


-- ============================================================
-- 24. DIGITAL FILE
-- ============================================================
-- Example protected path.
-- Actual PHP application must NOT expose this directly.
-- ============================================================

INSERT INTO product_digital_files (
    product_id,
    file_name,
    file_path
)
VALUES (
    13,
    'digital-fashion-guide.pdf',
    'storage/private/digital/digital-fashion-guide.pdf'
);


-- ============================================================
-- 25. DEMO COUPON
-- ============================================================

INSERT INTO coupons (
    code,
    discount_type,
    discount_value,
    minimum_order,
    maximum_discount,
    usage_limit,
    used_count,
    per_user_limit,
    starts_at,
    expires_at,
    status
)
VALUES (
    'WELCOME10',
    'percentage',
    10.00,
    500.00,
    500.00,
    1000,
    0,
    1,
    NOW(),
    DATE_ADD(NOW(), INTERVAL 1 YEAR),
    'active'
);


-- ============================================================
-- 26. DEMO ORDER
-- ============================================================
-- Creates realistic demo data for dashboard testing.
-- ============================================================

INSERT INTO orders (
    id,
    user_id,
    order_number,
    subtotal,
    discount_amount,
    shipping_cost,
    total_amount,
    currency,
    coupon_id,
    coupon_code,
    payment_method,
    payment_status,
    order_status,
    shipping_name,
    shipping_email,
    shipping_phone,
    shipping_address,
    shipping_city,
    shipping_postal_code,
    notes
)
VALUES (
    1,
    2,
    'BBBD-20260904-00001',
    1499.00,
    0.00,
    80.00,
    1579.00,
    'BDT',
    NULL,
    NULL,
    'cod',
    'pending',
    'confirmed',
    'Demo Customer',
    'demo@bbtradersbd.com',
    '01700000000',
    'Demo Customer Address',
    'Chattogram',
    '4000',
    'Demo order for development and testing.'
);


-- ============================================================
-- 27. DEMO ORDER ITEM
-- ============================================================

INSERT INTO order_items (
    order_id,
    product_id,
    variant_id,
    product_name,
    variant_name,
    quantity,
    unit_price,
    subtotal
)
VALUES (
    1,
    1,
    2,
    'Premium World Cup Football Jersey',
    'Size: M',
    1,
    1499.00,
    1499.00
);


-- ============================================================
-- 28. DEMO PAYMENT
-- ============================================================

INSERT INTO payments (
    order_id,
    payment_method,
    transaction_id,
    amount,
    currency,
    status
)
VALUES (
    1,
    'cod',
    NULL,
    1579.00,
    'BDT',
    'pending'
);


-- ============================================================
-- 29. DEMO REVIEW
-- ============================================================

INSERT INTO reviews (
    product_id,
    user_id,
    order_id,
    rating,
    review_text,
    status
)
VALUES (
    1,
    2,
    1,
    5,
    'Very comfortable and good quality jersey.',
    'approved'
);


-- ============================================================
-- 30. DEMO NEWSLETTER SUBSCRIBER
-- ============================================================

INSERT INTO newsletter_subscribers (
    email,
    status
)
VALUES (
    'demo@bbtradersbd.com',
    'active'
);


-- ============================================================
-- 31. SETTINGS
-- ============================================================

INSERT INTO settings (
    setting_key,
    setting_value
)
VALUES

('site_name',
 'B&B TRADERS BD'),

('site_email',
 'info@bbtradersbd.com'),

('site_phone',
 '01700000000'),

('site_address',
 'Chattogram, Bangladesh'),

('active_currency',
 'BDT'),

('currency_symbol',
 '৳'),

('shipping_fee',
 '80.00'),

('free_shipping_threshold',
 '3000.00'),

('announcement_text',
 'Free Delivery on Selected Products'),

('stripe_publishable_key',
 'pk_test_REPLACE_WITH_YOUR_KEY'),

('stripe_secret_key',
 'sk_test_REPLACE_WITH_YOUR_KEY'),

('stripe_webhook_secret',
 ''),

('bkash_app_key',
 ''),

('bkash_app_secret',
 ''),

('bkash_username',
 ''),

('bkash_password',
 ''),

('nagad_merchant_id',
 ''),

('nagad_merchant_number',
 ''),

('nagad_public_key',
 ''),

('nagad_private_key',
 ''),

('facebook_url',
 ''),

('instagram_url',
 ''),

('youtube_url',
 ''),

('whatsapp_url',
 '');


-- ============================================================
-- 32. USEFUL VERIFICATION QUERIES
-- ============================================================

-- Categories
SELECT
    id,
    name,
    slug,
    status,
    sort_order
FROM categories
ORDER BY sort_order;


-- Products + Category
SELECT
    p.id,
    p.name,
    p.sku,
    p.product_type,
    c.name AS category_name,
    p.price,
    p.old_price,
    p.stock,
    p.badge,
    p.featured,
    p.status
FROM products p
INNER JOIN categories c
    ON p.category_id = c.id
ORDER BY p.id;


-- Product Variants
SELECT
    pv.id,
    p.name AS product_name,
    pv.variant_name,
    pv.variant_value,
    pv.price,
    pv.stock,
    pv.sku,
    pv.status
FROM product_variants pv
INNER JOIN products p
    ON pv.product_id = p.id
ORDER BY p.id, pv.id;


-- Orders
SELECT
    o.id,
    o.order_number,
    o.shipping_name,
    o.total_amount,
    o.payment_method,
    o.payment_status,
    o.order_status,
    o.created_at
FROM orders o
ORDER BY o.id DESC;


-- Reviews
SELECT
    r.id,
    p.name AS product_name,
    CONCAT(u.first_name, ' ', u.last_name) AS customer_name,
    r.rating,
    r.review_text,
    r.status
FROM reviews r
INNER JOIN products p
    ON r.product_id = p.id
INNER JOIN users u
    ON r.user_id = u.id
ORDER BY r.id DESC;


-- Settings
SELECT
    setting_key,
    setting_value
FROM settings
ORDER BY id;


-- ============================================================
-- 33. DATABASE SUMMARY
-- ============================================================

SELECT 'users' AS table_name, COUNT(*) AS total FROM users
UNION ALL
SELECT 'categories', COUNT(*) FROM categories
UNION ALL
SELECT 'products', COUNT(*) FROM products
UNION ALL
SELECT 'product_images', COUNT(*) FROM product_images
UNION ALL
SELECT 'product_variants', COUNT(*) FROM product_variants
UNION ALL
SELECT 'product_digital_files', COUNT(*) FROM product_digital_files
UNION ALL
SELECT 'orders', COUNT(*) FROM orders
UNION ALL
SELECT 'order_items', COUNT(*) FROM order_items
UNION ALL
SELECT 'payments', COUNT(*) FROM payments
UNION ALL
SELECT 'reviews', COUNT(*) FROM reviews
UNION ALL
SELECT 'wishlists', COUNT(*) FROM wishlists
UNION ALL
SELECT 'coupons', COUNT(*) FROM coupons
UNION ALL
SELECT 'coupon_usages', COUNT(*) FROM coupon_usages
UNION ALL
SELECT 'newsletter_subscribers', COUNT(*) FROM newsletter_subscribers
UNION ALL
SELECT 'settings', COUNT(*) FROM settings;


-- ============================================================
-- B&B TRADERS BD DATABASE SETUP COMPLETED
-- ============================================================