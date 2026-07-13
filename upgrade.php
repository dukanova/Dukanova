<?php
require 'common/config.php';

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Alter Existing Tables (Store Customization & Optimization)
    $pdo->exec("ALTER TABLE sellers ADD COLUMN IF NOT EXISTS banner_image VARCHAR(255) DEFAULT NULL");
    $pdo->exec("ALTER TABLE sellers ADD COLUMN IF NOT EXISTS social_links TEXT DEFAULT NULL");
    $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS sku VARCHAR(100) DEFAULT NULL");
    $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS rating_avg DECIMAL(3,2) DEFAULT 0.00");
    $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS total_reviews INT DEFAULT 0");

    // 2. Category System
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (id INT AUTO_INCREMENT PRIMARY KEY, parent_id INT DEFAULT 0, name VARCHAR(100), slug VARCHAR(100) UNIQUE, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX(parent_id))");
    $pdo->exec("CREATE TABLE IF NOT EXISTS product_category_map (product_id INT, category_id INT, PRIMARY KEY(product_id, category_id))");

    // 3. Advanced Product System (Variants)
    $pdo->exec("CREATE TABLE IF NOT EXISTS product_variants (id INT AUTO_INCREMENT PRIMARY KEY, product_id INT, variant_name VARCHAR(100), sku VARCHAR(100), price_modifier DECIMAL(10,2) DEFAULT 0.00, stock INT DEFAULT 0, INDEX(product_id))");

    // 4. Review & Rating System
    $pdo->exec("CREATE TABLE IF NOT EXISTS reviews (id INT AUTO_INCREMENT PRIMARY KEY, product_id INT, user_id INT, rating INT CHECK(rating >= 1 AND rating <= 5), review_text TEXT, status VARCHAR(20) DEFAULT 'Approved', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX(product_id))");

    // 5. Customer Account & Wishlist
    $pdo->exec("CREATE TABLE IF NOT EXISTS wishlist (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, product_id INT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, UNIQUE(user_id, product_id))");
    $pdo->exec("CREATE TABLE IF NOT EXISTS customer_addresses (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, label VARCHAR(50), address TEXT, phone VARCHAR(20), is_default BOOLEAN DEFAULT FALSE)");

    // 6. Discount & Coupon System
    $pdo->exec("CREATE TABLE IF NOT EXISTS coupons (id INT AUTO_INCREMENT PRIMARY KEY, seller_id INT, code VARCHAR(50) UNIQUE, type VARCHAR(20), discount_value DECIMAL(10,2), min_order DECIMAL(10,2) DEFAULT 0.00, expires_at DATETIME, is_active BOOLEAN DEFAULT TRUE)");

    // 7. Shipping & Tax Systems
    $pdo->exec("CREATE TABLE IF NOT EXISTS shipping_rules (id INT AUTO_INCREMENT PRIMARY KEY, seller_id INT, name VARCHAR(100), type VARCHAR(50), rate DECIMAL(10,2), condition_value DECIMAL(10,2) DEFAULT 0.00)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS tax_rules (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(50), percentage DECIMAL(5,2), is_active BOOLEAN DEFAULT TRUE)");

    // 8. Seller Payout Automation
    $pdo->exec("CREATE TABLE IF NOT EXISTS payout_requests (id INT AUTO_INCREMENT PRIMARY KEY, seller_id INT, amount DECIMAL(10,2), status VARCHAR(20) DEFAULT 'Pending', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");

    // 9. Performance Indexes
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_prod_price ON products(price)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_prod_type ON products(product_type)");

    echo "<div style='background:#111827; color:#10b981; padding:20px; font-family:sans-serif; text-align:center;'>";
    echo "<h1>Database Upgraded Successfully!</h1>";
    echo "<p>All SaaS modules (Categories, Variants, Coupons, Payouts) have been injected.</p>";
    echo "<a href='index.php' style='color:#6366f1;'>Go to Homepage</a></div>";

} catch (PDOException $e) {
    die("Upgrade Failed: " . $e->getMessage());
}
?>
