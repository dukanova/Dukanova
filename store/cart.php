<?php
require '../common/config.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$user_id = $_SESSION['user_id'];
$store_slug = isset($_GET['name']) ? trim($_GET['name']) : '';

// Get Seller & Theme Info
$stmt = $pdo->prepare("SELECT * FROM sellers WHERE store_slug = ?");
$stmt->execute([$store_slug]);
$seller = $stmt->fetch();
if (!$seller) { die("Store not found!"); }
$seller_id = $seller['id'];

// 🔥 DYNAMIC THEME ENGINE 🔥
$themes = [
    'dawn' => ['bg' => '#f4f6f8', 'text' => '#202223', 'card' => '#ffffff', 'border' => '#e1e3e5', 'primary' => '#000000', 'primary_text' => '#ffffff', 'font' => 'Inter, sans-serif'],
    'ocean' => ['bg' => '#e0f2fe', 'text' => '#1e3a8a', 'card' => '#ffffff', 'border' => '#bae6fd', 'primary' => '#0284c7', 'primary_text' => '#ffffff', 'font' => 'Inter, sans-serif'],
    'sunset' => ['bg' => '#ffedd5', 'text' => '#7c2d12', 'card' => '#ffffff', 'border' => '#fed7aa', 'primary' => '#ea580c', 'primary_text' => '#ffffff', 'font' => 'Inter, sans-serif'],
    'pastel' => ['bg' => '#fdf2f8', 'text' => '#831843', 'card' => '#ffffff', 'border' => '#fbcfe8', 'primary' => '#db2777', 'primary_text' => '#ffffff', 'font' => 'Quicksand, sans-serif'],
    'vintage' => ['bg' => '#fef3c7', 'text' => '#451a03', 'card' => '#fffbeb', 'border' => '#fde68a', 'primary' => '#b45309', 'primary_text' => '#ffffff', 'font' => 'Merriweather, serif'],
    'midnight' => ['bg' => '#111827', 'text' => '#f3f4f6', 'card' => '#1f2937', 'border' => '#374151', 'primary' => '#ffffff', 'primary_text' => '#000000', 'font' => 'Inter, sans-serif'],
    'cyber' => ['bg' => '#000000', 'text' => '#22c55e', 'card' => '#052e16', 'border' => '#166534', 'primary' => '#22c55e', 'primary_text' => '#000000', 'font' => 'Courier New, monospace'],
    'street' => ['bg' => '#e5e7eb', 'text' => '#111827', 'card' => '#ffffff', 'border' => '#000000', 'primary' => '#ef4444', 'primary_text' => '#ffffff', 'font' => 'Impact, sans-serif'],
    'neon' => ['bg' => '#0f172a', 'text' => '#2dd4bf', 'card' => '#1e293b', 'border' => '#2dd4bf', 'primary' => '#a855f7', 'primary_text' => '#ffffff', 'font' => 'Inter, sans-serif']
];
$current_theme = $seller['theme'] ?? 'dawn';
$t = $themes[$current_theme] ?? $themes['dawn'];

// Handle Quantity Update
if (isset($_POST['update_quantity'])) {
    $cart_id = (int)$_POST['cart_id'];
    $action = $_POST['action']; 
    
    $c_stmt = $pdo->prepare("SELECT quantity FROM cart WHERE id = ? AND user_id = ?");
    $c_stmt->execute([$cart_id, $user_id]);
    $curr_cart = $c_stmt->fetch();
    
    if ($curr_cart) {
        $new_qty = $curr_cart['quantity'];
        if ($action == 'increase') $new_qty++;
        if ($action == 'decrease' && $new_qty > 1) $new_qty--;
        $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?")->execute([$new_qty, $cart_id]);
    }
    header("Location: cart.php?name=" . $store_slug); exit;
}

// Handle Remove Item
if (isset($_GET['remove'])) {
    $remove_id = (int)$_GET['remove'];
    $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?")->execute([$remove_id, $user_id]);
    header("Location: cart.php?name=" . $store_slug); exit;
}

$stmt = $pdo->prepare("SELECT c.*, p.name, p.price, p.image, p.product_type FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ? AND p.seller_id = ?");
$stmt->execute([$user_id, $seller_id]);
$cart_items = $stmt->fetchAll();

$is_digital_only = true;
$subtotal = 0;
if (!empty($cart_items)) {
    foreach ($cart_items as $item) {
        $subtotal += ($item['price'] * $item['quantity']);
        if (strtolower(trim($item['product_type'] ?? '')) !== 'digital') { $is_digital_only = false; }
    }
}
$shipping_charge = $is_digital_only ? 0 : 50; 
$total_amount = $subtotal + $shipping_charge;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - <?= htmlspecialchars($seller['store_name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-color: <?= $t['bg'] ?>;
            --text-color: <?= $t['text'] ?>;
            --card-color: <?= $t['card'] ?>;
            --border-color: <?= $t['border'] ?>;
            --primary-color: <?= $t['primary'] ?>;
            --primary-text: <?= $t['primary_text'] ?>;
            --font-family: <?= $t['font'] ?>;
        }
        body { font-family: var(--font-family); background-color: var(--bg-color); color: var(--text-color); }
        .theme-card { background-color: var(--card-color); border: 1px solid var(--border-color); }
        .theme-btn { background-color: var(--primary-color); color: var(--primary-text); }
        .theme-btn:hover { opacity: 0.9; }
        .theme-header { background-color: var(--card-color); border-bottom: 1px solid var(--border-color); }
    </style>
</head>
<body class="pb-20">

    <header class="theme-header px-6 py-4 sticky top-0 z-30 shadow-sm flex items-center justify-between">
        <a href="store.php?name=<?= $store_slug ?>" style="color: var(--text-color);"><i class="fas fa-arrow-left text-xl"></i></a>
        <h1 class="text-xl font-black tracking-tight text-center flex-grow">Your Cart</h1>
        <div class="w-6"></div>
    </header>

    <div class="max-w-4xl mx-auto p-4 md:p-8">
        <?php if(empty($cart_items)): ?>
            <div class="theme-card p-12 rounded-2xl shadow-sm text-center mt-10">
                <div class="w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4 shadow-sm" style="background: var(--bg-color); border: 1px solid var(--border-color); color: var(--text-color); opacity: 0.5;"><i class="fas fa-shopping-cart text-3xl"></i></div>
                <h3 class="text-xl font-black mb-2">Your cart is empty</h3>
                <p class="text-sm opacity-75 font-medium mb-6">Looks like you haven't added anything yet.</p>
                <a href="store.php?name=<?= $store_slug ?>" class="theme-btn px-6 py-3 rounded-xl text-sm font-black uppercase tracking-widest inline-block transition shadow-md">Continue Shopping</a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="md:col-span-2 space-y-4">
                    <?php foreach($cart_items as $item): ?>
                    <div class="theme-card p-4 rounded-2xl shadow-sm flex items-center space-x-4">
                        <div class="w-20 h-20 rounded-xl overflow-hidden shrink-0" style="border: 1px solid var(--border-color);">
                            <?php if($item['image']): ?><img src="../uploads/<?= $item['image'] ?>" class="w-full h-full object-cover"><?php else: ?><i class="fas fa-image text-gray-400 text-2xl flex items-center justify-center h-full"></i><?php endif; ?>
                        </div>
                        <div class="flex-grow">
                            <h4 class="font-bold text-sm"><?= htmlspecialchars($item['name']) ?></h4>
                            <p class="text-xs font-bold uppercase mt-1 opacity-80">
                                ₹<?= number_format($item['price'], 2) ?> 
                                <?php if(strtolower(trim($item['product_type'] ?? '')) === 'digital'): ?>
                                    <span style="color: var(--primary-color);" class="ml-1 font-black">(Digital)</span>
                                <?php endif; ?>
                            </p>
                            
                            <div class="flex items-center space-x-4 mt-3">
                                <form method="POST" action="cart.php?name=<?= $store_slug ?>" class="flex items-center rounded-lg overflow-hidden w-fit" style="border: 1px solid var(--border-color);">
                                    <input type="hidden" name="cart_id" value="<?= $item['id'] ?>">
                                    <input type="hidden" name="update_quantity" value="1">
                                    <button type="submit" name="action" value="decrease" class="px-3 py-1 font-bold" style="background: var(--bg-color); color: var(--text-color);">-</button>
                                    <span class="px-3 py-1 text-xs font-black" style="background: var(--card-color); border-left: 1px solid var(--border-color); border-right: 1px solid var(--border-color);"><?= $item['quantity'] ?></span>
                                    <button type="submit" name="action" value="increase" class="px-3 py-1 font-bold" style="background: var(--bg-color); color: var(--text-color);">+</button>
                                </form>
                                <a href="?name=<?= $store_slug ?>&remove=<?= $item['id'] ?>" class="text-red-500 text-xs font-bold hover:underline"><i class="fas fa-trash mr-1"></i> Remove</a>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-lg font-black">₹<?= number_format($item['price'] * $item['quantity'], 2) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="theme-card p-6 rounded-2xl h-fit sticky top-24">
                    <h3 class="font-black text-lg mb-4 pb-2" style="border-bottom: 1px solid var(--border-color);">Order Summary</h3>
                    <div class="space-y-3 mb-6">
                        <div class="flex justify-between text-sm font-bold opacity-80"><p>Subtotal</p><p>₹<?= number_format($subtotal, 2) ?></p></div>
                        <div class="flex justify-between text-sm font-bold" style="color: <?= $shipping_charge == 0 ? '#10b981' : 'inherit' ?>;">
                            <p>Shipping</p><p><?= $shipping_charge == 0 ? 'FREE' : '₹'.number_format($shipping_charge, 2) ?></p>
                        </div>
                    </div>
                    <div class="flex justify-between text-xl font-black pt-4 mb-6" style="border-top: 1px solid var(--border-color);">
                        <p>Total</p><p>₹<?= number_format($total_amount, 2) ?></p>
                    </div>
                    
                    <a href="checkout.php?name=<?= $store_slug ?>" class="theme-btn w-full block text-center py-4 rounded-xl text-sm font-black uppercase tracking-widest transition shadow-lg">
                        Proceed to Checkout <i class="fas fa-arrow-right ml-2"></i>
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
