<?php
require '../common/config.php';
$store_slug = $_GET['name'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM sellers WHERE store_slug = ?");
$stmt->execute([$store_slug]);
$seller = $stmt->fetch();
if (!$seller) die("Store not found.");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?name=" . urlencode($store_slug));
    exit;
}

$user_id = $_SESSION['user_id'];
$seller_id = $seller['id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'logout') {
    verify_csrf();
    session_destroy();
    header("Location: store.php?name=" . urlencode($store_slug));
    exit;
}

// Fetch User Info
$user = $pdo->query("SELECT * FROM users WHERE id = $user_id")->fetch();

// Fetch ONLY orders related to THIS seller
$stmt = $pdo->prepare("
    SELECT DISTINCT o.* FROM orders o 
    JOIN order_items oi ON o.id = oi.order_id 
    WHERE o.user_id = ? AND oi.seller_id = ? 
    ORDER BY o.created_at DESC
");
$stmt->execute([$user_id, $seller_id]);
$orders = $stmt->fetchAll();

// THEME LOGIC
$theme = $seller['theme'] ?? 'dawn';
if ($theme === 'ocean') {
    $font_family = "'Nunito', sans-serif"; $btn_class = "bg-blue-600 hover:bg-blue-700 text-white rounded-full";
} elseif ($theme === 'sunset') {
    $font_family = "'Poppins', sans-serif"; $btn_class = "bg-orange-500 hover:bg-orange-600 text-white rounded-lg";
} else {
    $font_family = "'Inter', sans-serif"; $btn_class = "bg-black hover:bg-gray-800 text-white rounded-sm";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - <?= htmlspecialchars($seller['store_name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Nunito:wght@400;700;900&family=Poppins:wght@400;700;900&display=swap" rel="stylesheet">
    <style> body { font-family: <?= $font_family ?>; background-color: #ffffff; color: #121212; } </style>
</head>
<body class="antialiased flex flex-col min-h-screen">

    <header class="sticky top-0 z-50 bg-white border-b border-gray-200 shadow-sm px-4 py-4 md:px-8 flex items-center justify-between">
        <a href="store.php?name=<?= $seller['store_slug'] ?>" class="text-gray-500 hover:text-black transition">
            <i class="fas fa-arrow-left mr-2"></i> Return to Store
        </a>
        <a href="store.php?name=<?= $seller['store_slug'] ?>" class="text-xl md:text-2xl font-black tracking-tight text-center">
            <?= htmlspecialchars($seller['store_name']) ?>
        </a>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="action" value="logout">
            <button type="submit" class="text-xs font-bold text-gray-500 hover:text-red-500 uppercase tracking-widest transition">Log out</button>
        </form>
    </header>

    <main class="flex-grow max-w-4xl mx-auto px-4 w-full py-12">
        <h1 class="text-3xl font-black tracking-tight mb-2 uppercase">My Account</h1>
        <p class="text-gray-500 mb-8 font-bold">Welcome back, <?= htmlspecialchars($user['username']) ?></p>

        <h2 class="text-lg font-bold uppercase tracking-wider border-b border-gray-200 pb-2 mb-4">Order History at <?= htmlspecialchars($seller['store_name']) ?></h2>
        
        <?php if(empty($orders)): ?>
            <div class="bg-gray-50 border border-gray-200 p-8 text-center rounded">
                <p class="text-gray-500 text-sm mb-4">You haven't placed any orders with this store yet.</p>
                <a href="store.php?name=<?= urlencode($store_slug) ?>" class="inline-block <?= $btn_class ?> px-6 py-3 text-xs font-bold uppercase tracking-widest transition">Start Shopping</a>
            </div>
        <?php else: ?>
            <div class="space-y-4 mb-8">
                <?php foreach($orders as $o): ?>
                    <div class="border border-gray-200 p-4 rounded flex justify-between items-center hover:bg-gray-50 transition">
                        <div>
                            <p class="font-bold text-sm text-gray-900">Order #<?= $o['id'] ?></p>
                            <p class="text-[11px] text-gray-500 uppercase tracking-wider mt-1"><?= date('M d, Y', strtotime($o['created_at'])) ?></p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-bold text-gray-900">₹<?= number_format($o['total_price'], 2) ?></p>
                            <p class="text-[10px] text-gray-500 uppercase tracking-widest mt-1"><?= $o['status'] ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <footer class="bg-gray-50 border-t border-gray-200 py-8 text-center mt-auto">
        <p class="text-[11px] text-gray-400 font-medium">&copy; <?= date('Y') ?> <?= htmlspecialchars($seller['store_name']) ?>.</p>
    </footer>
</body>
</html>
