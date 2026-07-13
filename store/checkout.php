<?php
require '../common/config.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$user_id = $_SESSION['user_id'];
$store_slug = isset($_GET['name']) ? trim($_GET['name']) : '';

// 🔥 AUTO-CREATE MISSING TABLES & COLUMNS (FIXED) 🔥
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS cart ( id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, product_id INT NOT NULL, quantity INT DEFAULT 1, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP )");
    
    // Notice: customer_name is now in the CREATE TABLE statement!
    $pdo->exec("CREATE TABLE IF NOT EXISTS orders ( id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, seller_id INT NOT NULL, customer_name VARCHAR(255), total_amount DECIMAL(10,2) NOT NULL, discount_amount DECIMAL(10,2) DEFAULT 0, shipping_address TEXT, customer_phone VARCHAR(20), payment_method VARCHAR(50), payment_status VARCHAR(50) DEFAULT 'Pending', order_status VARCHAR(50) DEFAULT 'Processing', utr_number VARCHAR(100) DEFAULT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS order_items ( id INT AUTO_INCREMENT PRIMARY KEY, order_id INT NOT NULL, product_id INT NOT NULL, seller_id INT NOT NULL, quantity INT NOT NULL, price DECIMAL(10,2) NOT NULL, seller_amount DECIMAL(10,2) NOT NULL )");
    
    // Auto-Healing for existing setups
    $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS customer_name VARCHAR(255) DEFAULT 'Guest Customer'");
    $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS utr_number VARCHAR(100) DEFAULT NULL");
    $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS discount_amount DECIMAL(10,2) DEFAULT 0");
} catch(Exception $e) {}

// Get Seller Info
$stmt = $pdo->prepare("SELECT * FROM sellers WHERE store_slug = ?");
$stmt->execute([$store_slug]);
$seller = $stmt->fetch();
if (!$seller) { die("Store not found!"); }
$seller_id = $seller['id'];

// Get Cart Items
$stmt = $pdo->prepare("SELECT c.*, p.name, p.price, p.image, p.product_type FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ? AND p.seller_id = ?");
$stmt->execute([$user_id, $seller_id]);
$cart_items = $stmt->fetchAll();
$is_empty = empty($cart_items);

$is_digital_only = true;
$subtotal = 0;
if (!$is_empty) {
    foreach ($cart_items as $item) {
        $subtotal += ($item['price'] * $item['quantity']);
        if (strtolower(trim($item['product_type'] ?? '')) !== 'digital') { $is_digital_only = false; }
    }
}
$shipping_charge = $is_digital_only ? 0 : 50; 

// COUPON SYSTEM LOGIC
$coupon_msg = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['apply_coupon'])) {
        $code = trim($_POST['coupon_code']);
        $c_stmt = $pdo->prepare("SELECT * FROM coupons WHERE seller_id = ? AND code = ?");
        $c_stmt->execute([$seller_id, $code]);
        $coupon = $c_stmt->fetch();

        if ($coupon) {
            $_SESSION['applied_coupon'][$seller_id] = $coupon;
            header("Location: checkout.php?name=" . urlencode($store_slug) . "&msg=applied"); exit;
        } else {
            unset($_SESSION['applied_coupon'][$seller_id]);
            header("Location: checkout.php?name=" . urlencode($store_slug) . "&msg=invalid"); exit;
        }
    }
    if (isset($_POST['remove_coupon'])) {
        unset($_SESSION['applied_coupon'][$seller_id]);
        header("Location: checkout.php?name=" . urlencode($store_slug)); exit;
    }
}

if (isset($_GET['msg'])) {
    if ($_GET['msg'] == 'applied') $coupon_msg = "<p class='text-green-600 text-xs font-bold mt-2'><i class='fas fa-check-circle'></i> Coupon applied successfully!</p>";
    if ($_GET['msg'] == 'invalid') $coupon_msg = "<p class='text-red-600 text-xs font-bold mt-2'><i class='fas fa-times-circle'></i> Invalid or expired coupon code.</p>";
}

// CALCULATE DISCOUNT
$discount_amount = 0;
$applied_coupon = $_SESSION['applied_coupon'][$seller_id] ?? null;

if ($applied_coupon) {
    $c_type = strtolower($applied_coupon['discount_type'] ?? $applied_coupon['type'] ?? 'fixed');
    $c_val = (float)($applied_coupon['discount_value'] ?? $applied_coupon['value'] ?? $applied_coupon['amount'] ?? 0);

    if ($c_type === 'percentage' || $c_type === 'percent') {
        $discount_amount = ($subtotal * $c_val) / 100;
    } else {
        $discount_amount = $c_val;
    }
    if ($discount_amount > $subtotal) { $discount_amount = $subtotal; }
}

$total_amount = ($subtotal + $shipping_charge) - $discount_amount;
if ($total_amount < 0) $total_amount = 0;

// GENERATE DYNAMIC UPI QR
$upi_id = !empty($seller['upi_id']) ? $seller['upi_id'] : 'merchant@upi';
$store_name_encoded = urlencode($seller['store_name']);
$amount_fmt = number_format($total_amount, 2, '.', '');
$upi_url = "upi://pay?pa={$upi_id}&pn={$store_name_encoded}&am={$amount_fmt}&cu=INR";
$qr_code_url = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($upi_url);
if (!empty($seller['upi_qr']) && file_exists('../uploads/' . $seller['upi_qr'])) {
    $qr_code_url = '../uploads/' . htmlspecialchars($seller['upi_qr']);
}

// 🔥 PLACE ORDER LOGIC (NOW WITH CUSTOMER NAME) 🔥
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    $name = trim($_POST['customer_name']);
    $phone = trim($_POST['customer_phone']);
    $address = $is_digital_only ? 'Digital Delivery (Email/Download)' : trim($_POST['shipping_address']);
    $payment_method = $_POST['payment_method'];
    $utr_number = ($payment_method === 'Online' && !empty($_POST['utr_number'])) ? trim($_POST['utr_number']) : NULL;

    // 🔥 Added customer_name to the query here 🔥
    $stmt = $pdo->prepare("INSERT INTO orders (user_id, seller_id, customer_name, total_amount, discount_amount, shipping_address, customer_phone, payment_method, payment_status, order_status, utr_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', 'Processing', ?)");
    
    if ($stmt->execute([$user_id, $seller_id, $name, $total_amount, $discount_amount, $address, $phone, $payment_method, $utr_number])) {
        $order_id = $pdo->lastInsertId();

        $i_stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, seller_id, quantity, price, seller_amount) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($cart_items as $item) {
            $i_stmt->execute([$order_id, $item['product_id'], $seller_id, $item['quantity'], $item['price'], ($item['price'] * $item['quantity'])]);
        }

        $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id IN (SELECT id FROM products WHERE seller_id = ?)")->execute([$user_id, $seller_id]);
        unset($_SESSION['applied_coupon'][$seller_id]);
        
        header("Location: success.php?name=" . urlencode($store_slug) . "&order_id=" . $order_id);
        exit;
    }
}

// DYNAMIC THEME ENGINE
$themes = [
    'dawn' => ['bg' => '#f4f6f8', 'text' => '#111827', 'card' => '#ffffff', 'border' => '#e5e7eb', 'primary' => '#000000', 'primary_text' => '#ffffff', 'font' => 'Inter, sans-serif'],
    'ocean' => ['bg' => '#e0f2fe', 'text' => '#0c4a6e', 'card' => '#ffffff', 'border' => '#bae6fd', 'primary' => '#0284c7', 'primary_text' => '#ffffff', 'font' => 'Inter, sans-serif'],
    'street' => ['bg' => '#e5e7eb', 'text' => '#111827', 'card' => '#ffffff', 'border' => '#9ca3af', 'primary' => '#ef4444', 'primary_text' => '#ffffff', 'font' => 'Impact, sans-serif'],
    'pastel' => ['bg' => '#fdf2f8', 'text' => '#831843', 'card' => '#ffffff', 'border' => '#fbcfe8', 'primary' => '#db2777', 'primary_text' => '#ffffff', 'font' => 'Quicksand, sans-serif'],
    'sunset' => ['bg' => '#fff7ed', 'text' => '#7c2d12', 'card' => '#ffffff', 'border' => '#fed7aa', 'primary' => '#ea580c', 'primary_text' => '#ffffff', 'font' => 'Inter, sans-serif'],
    'vintage' => ['bg' => '#fef3c7', 'text' => '#451a03', 'card' => '#fffbeb', 'border' => '#fde68a', 'primary' => '#b45309', 'primary_text' => '#ffffff', 'font' => 'Merriweather, serif'],
    'neon' => ['bg' => '#0f172a', 'text' => '#2dd4bf', 'card' => '#1e293b', 'border' => '#2dd4bf', 'primary' => '#a855f7', 'primary_text' => '#ffffff', 'font' => 'Courier New, monospace'],
    'cyber' => ['bg' => '#000000', 'text' => '#22c55e', 'card' => '#052e16', 'border' => '#166534', 'primary' => '#22c55e', 'primary_text' => '#000000', 'font' => 'Courier New, monospace'],
    'midnight' => ['bg' => '#111827', 'text' => '#f3f4f6', 'card' => '#1f2937', 'border' => '#374151', 'primary' => '#3b82f6', 'primary_text' => '#ffffff', 'font' => 'Inter, sans-serif']
];
$current_theme = strtolower(trim($seller['theme'] ?? 'dawn'));
$t = $themes[$current_theme] ?? $themes['dawn'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - <?= htmlspecialchars($seller['store_name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --bg-color: <?= $t['bg'] ?>; --text-color: <?= $t['text'] ?>; --card-color: <?= $t['card'] ?>; --border-color: <?= $t['border'] ?>; --primary-color: <?= $t['primary'] ?>; --primary-text: <?= $t['primary_text'] ?>; --font-family: <?= $t['font'] ?>; }
        body { font-family: var(--font-family); background-color: var(--bg-color); color: var(--text-color); }
        .theme-card { background-color: var(--card-color); border: 1px solid var(--border-color); }
        .theme-btn { background-color: var(--primary-color); color: var(--primary-text); cursor: pointer; }
        .theme-btn:hover { opacity: 0.9; }
        .theme-header { background-color: var(--card-color); border-bottom: 1px solid var(--border-color); }
        .shopify-input { background-color: var(--bg-color); border: 1px solid var(--border-color); border-radius: 8px; padding: 12px; width: 100%; font-size: 14px; font-weight: 600; outline: none; color: var(--text-color); }
        .shopify-input:focus { border-color: var(--primary-color); box-shadow: 0 0 0 1px var(--primary-color); }
    </style>
</head>
<body class="pb-20">

    <header class="theme-header px-6 py-4 sticky top-0 z-30 shadow-sm flex items-center justify-between">
        <a href="cart.php?name=<?= urlencode($store_slug) ?>" style="color: var(--text-color);"><i class="fas fa-arrow-left text-xl"></i></a>
        <h1 class="text-xl font-black tracking-tight text-center flex-grow">Checkout</h1>
        <div class="w-6"></div>
    </header>

    <div class="max-w-4xl mx-auto p-4 md:p-8">
        <?php if($is_empty): ?>
            <div class="theme-card p-12 rounded-2xl shadow-sm text-center mt-10">
                <h3 class="text-xl font-black mb-2">Cart is empty</h3>
                <a href="store.php?name=<?= urlencode($store_slug) ?>" class="theme-btn px-6 py-3 rounded-xl text-sm font-black uppercase tracking-widest inline-block transition shadow-md">Back to Store</a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                
                <div>
                    <form method="POST" action="checkout.php?name=<?= urlencode($store_slug) ?>" class="space-y-6 theme-card p-6 rounded-2xl shadow-sm">
                        
                        <div>
                            <h3 class="font-black text-lg mb-4 pb-2" style="border-bottom: 1px solid var(--border-color);">Contact Details</h3>
                            <div class="space-y-4">
                                <div><label class="block text-xs font-bold uppercase tracking-widest mb-1.5 opacity-80">Full Name</label><input type="text" name="customer_name" required class="shopify-input"></div>
                                <div><label class="block text-xs font-bold uppercase tracking-widest mb-1.5 opacity-80">WhatsApp / Phone Number</label><input type="text" name="customer_phone" required class="shopify-input"></div>
                            </div>
                        </div>

                        <?php if(!$is_digital_only): ?>
                        <div>
                            <h3 class="font-black text-lg mt-6 mb-4 pb-2" style="border-bottom: 1px solid var(--border-color);">Shipping Address</h3>
                            <div><label class="block text-xs font-bold uppercase tracking-widest mb-1.5 opacity-80">Complete Address</label><textarea name="shipping_address" required rows="3" class="shopify-input"></textarea></div>
                        </div>
                        <?php else: ?>
                            <div class="p-4 rounded-xl flex items-center mt-6" style="background: var(--bg-color); border: 1px dashed var(--primary-color); color: var(--primary-color);">
                                <i class="fas fa-cloud-download-alt text-2xl mr-3"></i>
                                <div>
                                    <p class="font-bold text-sm">Digital Delivery</p>
                                    <p class="text-xs font-medium opacity-80">No shipping address required.</p>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div>
                            <h3 class="font-black text-lg mt-6 mb-4 pb-2" style="border-bottom: 1px solid var(--border-color);">Payment Method</h3>
                            <select name="payment_method" id="payment_method" class="shopify-input" onchange="togglePaymentUI()">
                                <option value="Online">UPI / Online Payment 💳</option>
                                <?php if(!$is_digital_only): ?>
                                    <option value="COD">Cash on Delivery (COD) 💵</option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div id="upi_payment_box" class="mt-4 p-6 rounded-xl border-2 text-center transition-all duration-300" style="border-color: var(--border-color); background: var(--bg-color);">
                            <p class="text-xs font-bold uppercase tracking-widest opacity-80 mb-2">Scan & Pay ₹<?= number_format($total_amount, 2) ?></p>
                            <div class="bg-white p-2 rounded-xl inline-block shadow-sm mb-4 border border-gray-200">
                                <img src="<?= $qr_code_url ?>" alt="UPI QR Code" class="w-40 h-40 object-cover rounded-lg">
                            </div>
                            <div class="text-left">
                                <label class="block text-xs font-black uppercase tracking-widest mb-1.5 text-green-600"><i class="fas fa-check-double mr-1"></i> Enter 12-Digit UTR Number *</label>
                                <input type="text" name="utr_number" id="utr_input" placeholder="e.g. 301234567890" class="shopify-input font-mono tracking-widest" required>
                                <p class="text-[10px] font-bold opacity-60 mt-1.5">You will find this in your payment app after transferring the amount.</p>
                            </div>
                        </div>

                        <button type="submit" name="place_order" class="theme-btn w-full py-4 rounded-xl text-sm font-black uppercase tracking-widest transition shadow-lg mt-4">
                            Place Order (₹<?= number_format($total_amount, 2) ?>)
                        </button>
                    </form>
                </div>

                <div class="theme-card p-6 rounded-2xl h-fit sticky top-24">
                    <h3 class="font-black text-lg mb-4 pb-2" style="border-bottom: 1px solid var(--border-color);">Order Summary</h3>
                    <div class="space-y-4 mb-6">
                        <?php foreach($cart_items as $item): ?>
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 rounded overflow-hidden shrink-0" style="border: 1px solid var(--border-color);">
                                <?php if($item['image']): ?><img src="../uploads/<?= $item['image'] ?>" class="w-full h-full object-cover"><?php endif; ?>
                            </div>
                            <div class="flex-grow">
                                <p class="text-sm font-bold"><?= htmlspecialchars($item['name']) ?></p>
                                <p class="text-[10px] font-bold uppercase tracking-widest opacity-80">Qty: <?= $item['quantity'] ?> <?= $item['product_type'] == 'digital' ? '<span style="color:var(--primary-color);">(Digital)</span>' : '' ?></p>
                            </div>
                            <p class="text-sm font-black">₹<?= number_format($item['price'] * $item['quantity'], 2) ?></p>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mb-6 pt-4" style="border-top: 1px solid var(--border-color);">
                        <form method="POST" action="checkout.php?name=<?= urlencode($store_slug) ?>" class="flex space-x-2">
                            <input type="text" name="coupon_code" placeholder="Discount code" value="<?= htmlspecialchars($applied_coupon['code'] ?? '') ?>" <?= $applied_coupon ? 'readonly' : '' ?> class="shopify-input uppercase flex-grow text-sm py-2">
                            <?php if($applied_coupon): ?>
                                <button type="submit" name="remove_coupon" class="bg-red-50 text-red-600 px-4 rounded-lg font-black text-[10px] uppercase tracking-widest border border-red-200 hover:bg-red-100">Remove</button>
                            <?php else: ?>
                                <button type="submit" name="apply_coupon" class="theme-btn px-4 rounded-lg font-black text-[10px] uppercase tracking-widest shadow-sm">Apply</button>
                            <?php endif; ?>
                        </form>
                        <?= $coupon_msg ?>
                    </div>

                    <div class="pt-4 space-y-2" style="border-top: 1px solid var(--border-color);">
                        <div class="flex justify-between text-sm font-bold opacity-80"><p>Subtotal</p><p>₹<?= number_format($subtotal, 2) ?></p></div>
                        
                        <?php if($discount_amount > 0): ?>
                        <div class="flex justify-between text-sm font-black text-green-600">
                            <p><i class="fas fa-tag mr-1"></i> Discount applied</p><p>-₹<?= number_format($discount_amount, 2) ?></p>
                        </div>
                        <?php endif; ?>

                        <div class="flex justify-between text-sm font-bold" style="color: <?= $shipping_charge == 0 ? '#10b981' : 'inherit' ?>;">
                            <p>Shipping</p><p><?= $shipping_charge == 0 ? 'FREE' : '₹'.number_format($shipping_charge, 2) ?></p>
                        </div>
                        <div class="flex justify-between text-xl font-black pt-2 mt-2" style="border-top: 1px solid var(--border-color);"><p>Total</p><p>₹<?= number_format($total_amount, 2) ?></p></div>
                    </div>
                </div>

            </div>
        <?php endif; ?>
    </div>

    <script>
        function togglePaymentUI() {
            const method = document.getElementById('payment_method').value;
            const upiBox = document.getElementById('upi_payment_box');
            const utrInput = document.getElementById('utr_input');

            if (method === 'Online') {
                upiBox.style.display = 'block';
                utrInput.required = true;
            } else {
                upiBox.style.display = 'none';
                utrInput.required = false;
                utrInput.value = ''; 
            }
        }
        window.onload = togglePaymentUI;
    </script>
</body>
</html>
