<?php
require 'common/config.php';
if (!isset($_SESSION['user_id']) || empty($_SESSION['cart'])) { header("Location: cart.php"); exit; }

$user_id = $_SESSION['user_id'];
$msg = '';

// Fetch Wallet Balance
$stmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$wallet = $stmt->fetchColumn();

// Handle Coupon Application
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['apply_coupon'])) {
    verify_csrf();
    $code = strtoupper(trim($_POST['coupon_code']));
    // Validate coupon
    $stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1 AND expires_at >= NOW()");
    $stmt->execute([$code]);
    $coupon = $stmt->fetch();
    if ($coupon) {
        $_SESSION['applied_coupon'][$coupon['seller_id']] = $coupon;
        $msg = "<p class='text-green-400 mb-4'>Coupon Applied Successfully!</p>";
    } else {
        $msg = "<p class='text-red-400 mb-4'>Invalid or expired coupon code.</p>";
    }
}

// Handle Order Placement
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    verify_csrf();
    $address_id = $_POST['address_id'] ?? 0;
    $grand_total = $_POST['grand_total'] ?? 0;

    if ($wallet < $grand_total) {
        $msg = "<p class='text-red-400 mb-4'>Insufficient wallet balance. Please add funds.</p>";
    } else {
        $pdo->beginTransaction();
        try {
            // Deduct from Buyer
            $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?")->execute([$grand_total, $user_id]);
            
            // Create Order
            $pdo->prepare("INSERT INTO orders (user_id, total_price) VALUES (?, ?)")->execute([$user_id, $grand_total]);
            $order_id = $pdo->lastInsertId();

            foreach ($_SESSION['cart'] as $cart_key => $qty) {
                $parts = explode('_', $cart_key);
                $pid = (int)$parts[0];
                $vid = isset($parts[1]) && $parts[1] !== '' ? (int)$parts[1] : 0;

                // Get Product Price & Variant
                $p = $pdo->prepare("SELECT * FROM products WHERE id = ?"); $p->execute([$pid]); $prod = $p->fetch();
                $item_price = $prod['price'];
                if ($vid > 0) {
                    $v = $pdo->prepare("SELECT price_modifier FROM product_variants WHERE id = ?"); $v->execute([$vid]);
                    $item_price += $v->fetchColumn();
                }

                // Platform Commission (2%)
                $commission = ($item_price * $qty) * 0.02;
                $seller_amount = ($item_price * $qty) - $commission;
                
                // Add to order_items
                $pdo->prepare("INSERT INTO order_items (order_id, product_id, seller_id, price, quantity, commission, seller_amount) VALUES (?, ?, ?, ?, ?, ?, ?)")
                    ->execute([$order_id, $pid, $prod['seller_id'], $item_price, $qty, $commission, $seller_amount]);
                
                // Credit Seller Wallet (Pending logic can be added here later)
                $seller_user = $pdo->prepare("SELECT user_id FROM sellers WHERE id = ?"); $seller_user->execute([$prod['seller_id']]);
                $s_uid = $seller_user->fetchColumn();
                $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?")->execute([$seller_amount, $s_uid]);
            }
            $pdo->commit();
            unset($_SESSION['cart']);
            unset($_SESSION['applied_coupon']);
            header("Location: order_success.php");
            exit;
        } catch(Exception $e) {
            $pdo->rollBack();
            $msg = "<p class='text-red-400 mb-4'>Checkout failed: " . $e->getMessage() . "</p>";
        }
    }
}

// Calculate Cart Totals Grouped By Seller
$seller_groups = [];
$subtotal = 0;

foreach ($_SESSION['cart'] as $cart_key => $qty) {
    $parts = explode('_', $cart_key);
    $pid = (int)$parts[0];
    $vid = isset($parts[1]) && $parts[1] !== '' ? (int)$parts[1] : 0;
    
    $stmt = $pdo->prepare("SELECT p.*, s.store_name FROM products p JOIN sellers s ON p.seller_id = s.id WHERE p.id = ?");
    $stmt->execute([$pid]);
    $p = $stmt->fetch();
    
    if ($p) {
        $price = $p['price'];
        if ($vid > 0) {
            $vStmt = $pdo->prepare("SELECT price_modifier FROM product_variants WHERE id = ?");
            $vStmt->execute([$vid]);
            $price += $vStmt->fetchColumn();
        }
        
        $seller_id = $p['seller_id'];
        if(!isset($seller_groups[$seller_id])) {
            $seller_groups[$seller_id] = ['store_name' => $p['store_name'], 'items_total' => 0, 'discount' => 0, 'shipping' => 0];
        }
        $seller_groups[$seller_id]['items_total'] += ($price * $qty);
        $subtotal += ($price * $qty);
    }
}

// Apply Logic (Shipping & Coupons)
$total_discount = 0;
$total_shipping = 0;

foreach ($seller_groups as $sid => &$data) {
    // 1. Apply Coupons
    if (isset($_SESSION['applied_coupon'][$sid])) {
        $c = $_SESSION['applied_coupon'][$sid];
        if ($data['items_total'] >= $c['min_order']) {
            if ($c['type'] == 'Percentage') {
                $data['discount'] = $data['items_total'] * ($c['discount_value'] / 100);
            } else {
                $data['discount'] = $c['discount_value'];
            }
            $data['discount'] = min($data['discount'], $data['items_total']); // Cap discount
            $total_discount += $data['discount'];
        }
    }
    
    // 2. Apply Shipping Rules (Fetch active rule for seller)
    $stmt = $pdo->prepare("SELECT * FROM shipping_rules WHERE seller_id = ? LIMIT 1");
    $stmt->execute([$sid]);
    $shipping_rule = $stmt->fetch();
    
    if ($shipping_rule) {
        if ($shipping_rule['type'] == 'Free Above' && $data['items_total'] >= $shipping_rule['condition_value']) {
            $data['shipping'] = 0;
        } else {
            $data['shipping'] = $shipping_rule['rate'];
        }
    } else {
        $data['shipping'] = 50; // Default flat rate if seller has no rules
    }
    $total_shipping += $data['shipping'];
}

// Apply Global Platform Tax (e.g., 18% GST on discounted subtotal)
$taxable_amount = max(0, $subtotal - $total_discount);
$tax_rate = $pdo->query("SELECT percentage FROM tax_rules WHERE is_active = 1 LIMIT 1")->fetchColumn() ?: 18; // Default 18%
$total_tax = $taxable_amount * ($tax_rate / 100);

$grand_total = $taxable_amount + $total_shipping + $total_tax;

// Fetch Customer Addresses
$addresses = $pdo->prepare("SELECT * FROM customer_addresses WHERE user_id = ?");
$addresses->execute([$user_id]);
$addresses = $addresses->fetchAll();

require 'common/header.php';
?>
<div class="p-4 pb-24">
    <h2 class="text-2xl font-bold mb-4">Secure Checkout</h2>
    <?= $msg ?>

    <form method="POST" class="bg-gray-800 p-4 rounded-lg mb-6 shadow-lg border border-gray-700 flex space-x-2">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="apply_coupon" value="1">
        <input type="text" name="coupon_code" placeholder="Enter Store Promo Code" class="w-full p-3 bg-gray-900 border border-gray-600 rounded text-white uppercase focus:border-indigo-500 outline-none">
        <button type="submit" class="bg-indigo-600 px-6 py-3 rounded font-bold transition hover:bg-indigo-700">Apply</button>
    </form>

    <div class="bg-gray-800 p-5 rounded-lg mb-6 shadow-lg border border-gray-700">
        <h3 class="font-bold text-lg mb-4 border-b border-gray-700 pb-2">Order Summary</h3>
        
        <div class="space-y-2 text-sm">
            <div class="flex justify-between text-gray-400">
                <span>Subtotal</span>
                <span>₹<?= number_format($subtotal, 2) ?></span>
            </div>
            <?php if($total_discount > 0): ?>
            <div class="flex justify-between text-green-400">
                <span>Store Discounts</span>
                <span>-₹<?= number_format($total_discount, 2) ?></span>
            </div>
            <?php endif; ?>
            <div class="flex justify-between text-gray-400">
                <span>Shipping Fees</span>
                <span>+₹<?= number_format($total_shipping, 2) ?></span>
            </div>
            <div class="flex justify-between text-gray-400">
                <span>Estimated Tax (<?= $tax_rate ?>%)</span>
                <span>+₹<?= number_format($total_tax, 2) ?></span>
            </div>
        </div>
        
        <div class="flex justify-between text-xl font-bold mt-4 pt-4 border-t border-gray-700">
            <span>Grand Total</span>
            <span class="text-green-400">₹<?= number_format($grand_total, 2) ?></span>
        </div>
        <p class="text-xs text-indigo-400 mt-2 text-right">Wallet Balance: ₹<?= number_format($wallet, 2) ?></p>
    </div>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="place_order" value="1">
        <input type="hidden" name="grand_total" value="<?= $grand_total ?>">
        
        <h3 class="font-bold text-lg mb-3">Select Delivery Address</h3>
        <?php if(empty($addresses)): ?>
            <div class="bg-red-900 p-4 rounded-lg text-red-200 text-sm mb-4">
                You have no saved addresses. <a href="profile.php" class="font-bold underline">Add one in your profile first</a>.
            </div>
        <?php else: ?>
            <div class="space-y-3 mb-6">
                <?php foreach($addresses as $idx => $addr): ?>
                <label class="block bg-gray-800 p-4 rounded-lg border border-gray-700 cursor-pointer hover:border-indigo-500 transition">
                    <div class="flex items-start space-x-3">
                        <input type="radio" name="address_id" value="<?= $addr['id'] ?>" <?= $idx === 0 ? 'checked' : '' ?> class="mt-1">
                        <div>
                            <p class="font-bold text-sm text-indigo-400"><?= htmlspecialchars($addr['label']) ?></p>
                            <p class="text-xs text-gray-300 mt-1"><?= htmlspecialchars($addr['address']) ?></p>
                            <p class="text-xs text-gray-400 mt-1">Ph: <?= htmlspecialchars($addr['phone']) ?></p>
                        </div>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
            
            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 p-4 rounded-lg font-bold text-lg shadow-lg transition flex items-center justify-center">
                <i class="fas fa-lock mr-2"></i> Pay & Place Order
            </button>
        <?php endif; ?>
    </form>
</div>
<?php require 'common/bottom.php'; ?>
