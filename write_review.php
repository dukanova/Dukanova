<?php
require 'common/config.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$user_id = $_SESSION['user_id'];
$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$msg = '';

// Verify Product Exists
$stmt = $pdo->prepare("SELECT name, image FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();
if (!$product) die("<h1 class='text-white text-center mt-10'>Product not found.</h1>");

// VERIFIED BUYER CHECK: Did this user actually buy this product?
$check = $pdo->prepare("SELECT COUNT(*) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.user_id = ? AND oi.product_id = ?");
$check->execute([$user_id, $product_id]);
if ($check->fetchColumn() == 0) {
    die("
    <div class='bg-gray-900 min-h-screen p-6 text-center text-white flex flex-col justify-center items-center'>
        <i class='fas fa-lock text-5xl text-red-500 mb-4'></i>
        <h2 class='text-2xl font-bold'>Verified Buyers Only</h2>
        <p class='text-gray-400 mt-2 mb-6'>You can only review products you have successfully purchased.</p>
        <a href='profile.php' class='bg-indigo-600 px-6 py-2 rounded font-bold'>Back to Profile</a>
    </div>
    ");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verify_csrf();
    $rating = max(1, min(5, (int)$_POST['rating']));
    $review_text = trim($_POST['review_text']);

    // Insert Review
    $stmt = $pdo->prepare("INSERT INTO reviews (product_id, user_id, rating, review_text) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$product_id, $user_id, $rating, $review_text])) {
        
        // MAGIC AUTO-CALCULATOR: Update product's total reviews and average rating
        $pdo->prepare("
            UPDATE products SET 
            rating_avg = (SELECT IFNULL(AVG(rating), 0) FROM reviews WHERE product_id = ? AND status = 'Approved'),
            total_reviews = (SELECT COUNT(*) FROM reviews WHERE product_id = ? AND status = 'Approved')
            WHERE id = ?
        ")->execute([$product_id, $product_id, $product_id]);

        $msg = "<p class='text-green-400 mb-4 font-bold'>Thank you! Your review has been published.</p>";
    }
}

require 'common/header.php';
?>
<div class="p-4 pb-24">
    <div class="flex items-center mb-6">
        <a href="profile.php" class="text-gray-400 mr-3 hover:text-white transition"><i class="fas fa-arrow-left text-xl"></i></a>
        <h2 class="text-2xl font-bold">Write a Review</h2>
    </div>

    <?= $msg ?>

    <div class="bg-gray-800 p-5 rounded-lg mb-6 border border-gray-700 shadow-lg flex items-center space-x-4">
        <div class="w-16 h-16 bg-gray-900 rounded flex-shrink-0 flex items-center justify-center overflow-hidden">
            <?php if($product['image']): ?>
                <img src="uploads/<?= htmlspecialchars($product['image']) ?>" class="object-cover w-full h-full">
            <?php else: ?>
                <i class="fas fa-box text-gray-500"></i>
            <?php endif; ?>
        </div>
        <h3 class="font-bold text-lg text-white"><?= htmlspecialchars($product['name']) ?></h3>
    </div>

    <form method="POST" class="bg-gray-800 p-6 rounded-lg border border-gray-700 shadow-lg">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        
        <div class="mb-4">
            <label class="text-xs font-bold text-gray-400 block mb-2">Rate the Product</label>
            <select name="rating" required class="w-full p-3 bg-gray-900 border border-gray-600 rounded text-white focus:border-yellow-500 outline-none">
                <option value="5">⭐⭐⭐⭐⭐ (5/5) - Excellent</option>
                <option value="4">⭐⭐⭐⭐ (4/5) - Very Good</option>
                <option value="3">⭐⭐⭐ (3/5) - Average</option>
                <option value="2">⭐⭐ (2/5) - Poor</option>
                <option value="1">⭐ (1/5) - Terrible</option>
            </select>
        </div>

        <div class="mb-4">
            <label class="text-xs font-bold text-gray-400 block mb-2">Review Details</label>
            <textarea name="review_text" required placeholder="What did you like or dislike about this product?" class="w-full p-3 bg-gray-900 border border-gray-600 rounded text-white h-32 focus:border-indigo-500 outline-none"></textarea>
        </div>

        <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 p-3 rounded-lg font-bold text-lg transition flex items-center justify-center">
            <i class="fas fa-paper-plane mr-2"></i> Submit Review
        </button>
    </form>
</div>
<?php require 'common/bottom.php'; ?>
