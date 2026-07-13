<?php
require 'common/config.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $cart_key = $_POST['cart_key'] ?? ''; 
    
    if ($action == 'update' && isset($_POST['qty'])) {
        $qty = max(1, (int)$_POST['qty']);
        $_SESSION['cart'][$cart_key] = $qty;
    } elseif ($action == 'remove') {
        unset($_SESSION['cart'][$cart_key]);
    }
    header("Location: cart.php");
    exit;
}

$cart_items = [];
$total = 0;

if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $cart_key => $qty) {
        $parts = explode('_', $cart_key);
        $pid = (int)$parts[0];
        $vid = isset($parts[1]) && $parts[1] !== '' ? (int)$parts[1] : 0;
        
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$pid]);
        $p = $stmt->fetch();
        
        if ($p) {
            $item_price = $p['price'];
            $variant_name = '';
            
            if ($vid > 0) {
                $vStmt = $pdo->prepare("SELECT * FROM product_variants WHERE id = ?");
                $vStmt->execute([$vid]);
                $v = $vStmt->fetch();
                if ($v) {
                    $item_price += $v['price_modifier'];
                    $variant_name = $v['variant_name'];
                }
            }
            
            $cart_items[] = [
                'cart_key' => $cart_key,
                'id' => $p['id'],
                'name' => $p['name'],
                'image' => $p['image'],
                'variant_name' => $variant_name,
                'price' => $item_price,
                'qty' => $qty
            ];
            $total += ($item_price * $qty);
        }
    }
}

require 'common/header.php';
?>
<div class="p-4 pb-24 max-w-2xl mx-auto">
    <div class="flex items-center justify-between mb-8 pb-4 border-b border-gray-200">
        <h2 class="text-3xl font-black tracking-tight text-gray-900">Your Cart</h2>
        <span class="text-sm font-bold text-gray-500"><?= count($cart_items) ?> items</span>
    </div>
    
    <?php if(empty($cart_items)): ?>
        <div class="text-center py-16">
            <h3 class="text-xl font-bold text-gray-900 mb-2">Your cart is currently empty.</h3>
            <p class="text-gray-500 mb-8">Browse our marketplace to find something you love.</p>
            <a href="index.php" class="inline-block bg-transparent text-white px-8 py-4 font-bold uppercase tracking-widest text-sm hover:bg-gray-800 transition">Continue Shopping</a>
        </div>
    <?php else: ?>
        <div class="space-y-6">
            <?php foreach($cart_items as $item): ?>
            <div class="flex items-start justify-between pb-6 border-b border-gray-100">
                <div class="flex space-x-4 w-3/4">
                    <div class="w-24 h-24 bg-gray-50 border border-gray-200 rounded-sm flex-shrink-0 flex items-center justify-center overflow-hidden">
                        <?php if($item['image']): ?>
                            <img src="uploads/<?= htmlspecialchars($item['image']) ?>" class="object-cover w-full h-full">
                        <?php else: ?>
                            <i class="fas fa-image text-gray-300 text-2xl"></i>
                        <?php endif; ?>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 leading-tight"><?= htmlspecialchars($item['name']) ?></h3>
                        <?php if($item['variant_name']): ?>
                            <p class="text-xs text-gray-500 mt-1 uppercase tracking-wider"><?= htmlspecialchars($item['variant_name']) ?></p>
                        <?php endif; ?>
                        <p class="text-sm font-bold text-gray-900 mt-2">₹<?= number_format($item['price'], 2) ?></p>
                    </div>
                </div>
                
                <div class="flex flex-col items-end justify-between h-24">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="cart_key" value="<?= $item['cart_key'] ?>">
                        <button type="submit" class="text-gray-400 hover:text-black transition text-sm underline">Remove</button>
                    </form>
                    
                    <form method="POST" class="flex items-center border border-gray-300 rounded-sm">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="cart_key" value="<?= $item['cart_key'] ?>">
                        <input type="number" name="qty" value="<?= $item['qty'] ?>" min="1" class="w-12 py-1 text-center text-sm border-none outline-none bg-transparent">
                        <button type="submit" class="px-2 text-gray-500 hover:text-black"><i class="fas fa-sync-alt text-xs"></i></button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="mt-8 pt-6">
            <div class="flex justify-between text-xl font-black text-gray-900 mb-2">
                <span>Subtotal</span>
                <span>₹<?= number_format($total, 2) ?></span>
            </div>
            <p class="text-sm text-gray-500 mb-6">Taxes and shipping calculated at checkout</p>
            
            <a href="checkout.php" class="block w-full bg-black hover:bg-gray-900 text-white text-center py-4 font-bold uppercase tracking-widest text-sm transition shadow-md">Check out</a>
        </div>
    <?php endif; ?>
</div>
<?php require 'common/bottom.php'; ?>
