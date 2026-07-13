<?php
require 'common/config.php';
verify_csrf(); // Enforce CSRF on POST
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch Product & Store
$stmt = $pdo->prepare("SELECT p.*, s.store_name, s.store_slug FROM products p JOIN sellers s ON p.seller_id = s.id WHERE p.id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) die("<h1 class='text-white text-center mt-10'>Product not found.</h1>");

// Fetch Variants
$stmt = $pdo->prepare("SELECT * FROM product_variants WHERE product_id = ? AND stock > 0");
$stmt->execute([$product_id]);
$variants = $stmt->fetchAll();

// Handle Add to Cart
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $variant_id = $_POST['variant_id'] ?? 0;
    $cart_key = $product_id . '_' . $variant_id; // Unique key for product+variant combination
    
    if(!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
    $_SESSION['cart'][$cart_key] = ($_SESSION['cart'][$cart_key] ?? 0) + 1;
    header("Location: cart.php");
    exit;
}

require 'common/header.php';
?>
<div class="pb-24">
    <div class="w-full h-64 bg-gray-800 flex items-center justify-center relative">
        <a href="index.php" class="absolute top-4 left-4 bg-black bg-opacity-50 p-2 rounded-full text-white"><i class="fas fa-arrow-left"></i></a>
        <?php if($product['image']): ?>
            <img src="uploads/<?= htmlspecialchars($product['image']) ?>" class="object-cover w-full h-full">
        <?php else: ?>
            <i class="fas fa-box-open text-6xl text-gray-500"></i>
        <?php endif; ?>
    </div>

    <div class="p-4">
        <div class="flex justify-between items-start mb-2">
            <h1 class="text-2xl font-bold"><?= htmlspecialchars($product['name']) ?></h1>
            <a href="store/store.php?name=<?= $product['store_slug'] ?>" class="text-indigo-400 text-sm font-bold"><i class="fas fa-store"></i> <?= htmlspecialchars($product['store_name']) ?></a>
        </div>
        
        <div class="flex items-center space-x-2 mb-4">
            <span class="text-yellow-400"><i class="fas fa-star"></i> <?= $product['rating_avg'] ?></span>
            <span class="text-gray-400 text-sm">(<?= $product['total_reviews'] ?> reviews)</span>
        </div>

        <p class="text-3xl font-bold text-green-400 mb-6">₹<?= number_format($product['price'], 2) ?></p>

        <p class="text-gray-300 text-sm mb-6 leading-relaxed"><?= nl2br(htmlspecialchars($product['description'])) ?></p>

        <form method="POST" class="bg-gray-800 p-4 rounded-lg border border-gray-700">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            
            <?php if(!empty($variants)): ?>
            <div class="mb-4">
                <label class="block text-gray-400 text-sm font-bold mb-2">Select Variant</label>
                <select name="variant_id" required class="w-full p-3 bg-gray-900 border border-gray-600 rounded text-white outline-none focus:border-indigo-500">
                    <option value="">-- Choose Option --</option>
                    <?php foreach($variants as $v): ?>
                        <option value="<?= $v['id'] ?>">
                            <?= htmlspecialchars($v['variant_name']) ?> (+₹<?= number_format($v['price_modifier'], 2) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <button type="submit" name="add_to_cart" class="w-full bg-green-600 hover:bg-green-700 p-4 rounded-lg font-bold text-lg transition shadow-lg flex items-center justify-center">
                <i class="fas fa-cart-plus mr-2"></i> Add to Cart
            </button>
        </form>
    </div>
</div>
<?php require 'common/bottom.php'; ?>
