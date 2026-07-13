<?php
require '../common/config.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = rand(100000, 999999);
}
$user_id = $_SESSION['user_id'];
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

try {
    $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS variations TEXT DEFAULT NULL");
    $pdo->exec("ALTER TABLE cart ADD COLUMN IF NOT EXISTS variation_data TEXT DEFAULT NULL");
} catch(Exception $e) {}

$stmt = $pdo->prepare("SELECT p.*, s.store_name, s.store_slug, s.theme, s.logo_image, s.description as store_desc FROM products p JOIN sellers s ON p.seller_id = s.id WHERE p.id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    die("<div style='font-family:sans-serif; text-align:center; padding:50px;'><h1>Product Not Found 🕵️‍♂️</h1><a href='javascript:history.back()'>Go Back</a></div>");
}

$store_slug = $product['store_slug'];
$seller_id = $product['seller_id'];

$variations = [];
if (!empty($product['variations'])) {
    $parsed = json_decode($product['variations'], true);
    if (is_array($parsed)) { $variations = $parsed; }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $var_data = isset($_POST['variations']) ? json_encode($_POST['variations']) : NULL;
    
    $check_stmt = $pdo->prepare("SELECT id FROM cart WHERE user_id = ? AND product_id = ? AND IFNULL(variation_data, '') = IFNULL(?, '')");
    $check_stmt->execute([$user_id, $product_id, $var_data]);
    $existing_cart = $check_stmt->fetch();
    
    if ($existing_cart) {
        $pdo->prepare("UPDATE cart SET quantity = quantity + 1 WHERE id = ?")->execute([$existing_cart['id']]);
    } else {
        $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity, variation_data) VALUES (?, ?, 1, ?)")->execute([$user_id, $product_id, $var_data]);
    }
    header("Location: product.php?id=" . $product_id . "&added=1");
    exit;
}

$c_stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ? AND product_id IN (SELECT id FROM products WHERE seller_id = ?)");
$c_stmt->execute([$user_id, $seller_id]);
$cart_count = $c_stmt->fetchColumn() ?: 0;

$themes = [
    'dawn' => ['bg' => '#ffffff', 'text' => '#121212', 'card' => '#f4f6f8', 'border' => '#e1e3e5', 'primary' => '#000000', 'primary_text' => '#ffffff', 'font' => "'Inter', sans-serif", 'radius' => '0.5rem', 'shadow' => '0 4px 6px -1px rgba(0,0,0,0.05)'],
    'ocean' => ['bg' => '#f0f9ff', 'text' => '#0c4a6e', 'card' => '#ffffff', 'border' => '#bae6fd', 'primary' => '#0284c7', 'primary_text' => '#ffffff', 'font' => "'Inter', sans-serif", 'radius' => '1rem', 'shadow' => '0 10px 15px -3px rgba(2,132,199,0.1)'],
    'pastel' => ['bg' => '#fdf2f8', 'text' => '#831843', 'card' => '#ffffff', 'border' => '#fbcfe8', 'primary' => '#db2777', 'primary_text' => '#ffffff', 'font' => "'Quicksand', sans-serif", 'radius' => '1.5rem', 'shadow' => '0 10px 25px -5px rgba(219,39,119,0.1)'],
    'street' => ['bg' => '#f3f4f6', 'text' => '#111827', 'card' => '#ffffff', 'border' => '#000000', 'primary' => '#ef4444', 'primary_text' => '#ffffff', 'font' => "'Impact', sans-serif", 'radius' => '0px', 'shadow' => '4px 4px 0px #000000'],
    'vintage' => ['bg' => '#fef3c7', 'text' => '#451a03', 'card' => '#fffbeb', 'border' => '#fde68a', 'primary' => '#b45309', 'primary_text' => '#ffffff', 'font' => "'Playfair Display', serif", 'radius' => '0.25rem', 'shadow' => '0 4px 6px -1px rgba(180,83,9,0.15)'],
    'midnight' => ['bg' => '#0f172a', 'text' => '#f8fafc', 'card' => '#1e293b', 'border' => '#334155', 'primary' => '#3b82f6', 'primary_text' => '#ffffff', 'font' => "'Inter', sans-serif", 'radius' => '0.75rem', 'shadow' => '0 10px 15px -3px rgba(0,0,0,0.5)'],
    'neon' => ['bg' => '#000000', 'text' => '#2dd4bf', 'card' => '#0f172a', 'border' => '#2dd4bf', 'primary' => '#a855f7', 'primary_text' => '#ffffff', 'font' => "'Space Mono', monospace", 'radius' => '0px', 'shadow' => '0 0 15px rgba(45,212,191,0.2)'],
];

$current_theme = strtolower(trim($product['theme'] ?? 'dawn'));
$t = $themes[$current_theme] ?? $themes['dawn'];

$common_css = "
    :root { --bg: {$t['bg']}; --text: {$t['text']}; --card: {$t['card']}; --border: {$t['border']}; --primary: {$t['primary']}; --primary-text: {$t['primary_text']}; --radius: {$t['radius']}; --shadow: {$t['shadow']}; --font: {$t['font']}; }
    body { background-color: var(--bg); color: var(--text); font-family: var(--font); -webkit-font-smoothing: antialiased; }
    
    .shopify-btn { background-color: var(--primary); color: var(--primary-text); border-radius: var(--radius); transition: all 0.3s ease; }
    .shopify-btn:hover { opacity: 0.85; transform: scale(0.98); box-shadow: var(--shadow); }
    
    /* 🔥 MODERN PILL BUTTON CSS 🔥 */
    .var-pill {
        border: 1px solid var(--border);
        background-color: var(--card);
        color: var(--text);
        border-radius: var(--radius);
    }
    .var-radio:checked + .var-pill {
        background-color: var(--primary);
        color: var(--primary-text);
        border-color: var(--primary);
        box-shadow: var(--shadow);
    }

    @keyframes slideUp { from { bottom: -50px; opacity: 0; } to { bottom: 20px; opacity: 1; } }
    .toast-notification { animation: slideUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> - <?= htmlspecialchars($product['store_name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;800;900&family=Playfair+Display:wght@600;800&family=Quicksand:wght@500;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    <style><?= $common_css ?></style>
</head>
<body class="flex flex-col min-h-screen">
    
    <header class="sticky top-0 z-40 transition-all" style="background-color: color-mix(in srgb, var(--bg) 90%, transparent); backdrop-filter: blur(12px); border-bottom: 1px solid var(--border);">
        <div class="max-w-7xl mx-auto px-4 md:px-8 py-4 flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <a href="store.php?name=<?= urlencode($store_slug) ?>" class="text-xl md:text-2xl opacity-60 hover:opacity-100 transition"><i class="fas fa-arrow-left"></i></a>
                <h1 class="text-xl md:text-2xl font-black tracking-tight" style="color: var(--text);"><?= htmlspecialchars($product['store_name']) ?></h1>
            </div>
            <a href="cart.php?name=<?= urlencode($store_slug) ?>" class="relative p-2 transition hover:opacity-70" style="color: var(--text);">
                <i class="fas fa-shopping-bag text-2xl"></i>
                <?php if($cart_count > 0): ?><span class="absolute top-0 right-0 w-5 h-5 flex items-center justify-center text-[10px] font-black shadow-sm" style="background-color: var(--primary); color: var(--primary-text); border-radius: var(--radius);"><?= $cart_count ?></span><?php endif; ?>
            </a>
        </div>
    </header>

    <main class="flex-grow max-w-7xl mx-auto px-4 md:px-8 py-10 md:py-16 w-full">
        <div class="mb-8 text-xs font-bold uppercase tracking-widest opacity-50">
            <a href="store.php?name=<?= urlencode($store_slug) ?>" class="hover:underline">Home</a> / <?= htmlspecialchars($product['name']) ?>
        </div>

        <div class="flex flex-col lg:flex-row gap-10 lg:gap-16">
            
            <div class="w-full lg:w-1/2">
                <div class="w-full aspect-[4/5] relative shadow-sm overflow-hidden" style="border-radius: var(--radius); background-color: var(--card); border: 1px solid var(--border);">
                    <?php if($product['image']): ?>
                        <img src="../uploads/<?= $product['image'] ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center opacity-10"><i class="fas fa-image text-6xl"></i></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="w-full lg:w-1/2 flex flex-col py-2">
                <h1 class="text-3xl md:text-5xl font-black mb-4 tracking-tight leading-tight" style="color: var(--text);"><?= htmlspecialchars($product['name']) ?></h1>
                <p class="text-2xl md:text-3xl font-black mb-8" style="color: var(--text);">₹<?= number_format($product['price'], 2) ?></p>

                <form method="POST" class="mb-10">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    
                    <?php if(!empty($variations)): ?>
                        <div class="space-y-6 mb-10 pt-6" style="border-top: 1px solid var(--border);">
                            <?php foreach($variations as $var_name => $var_options): ?>
                                <fieldset>
                                    <legend class="text-xs font-bold uppercase tracking-widest mb-3 opacity-80" style="color: var(--text);">
                                        Select <?= htmlspecialchars($var_name) ?>
                                    </legend>
                                    <div class="flex flex-wrap gap-3">
                                        <?php foreach($var_options as $idx => $opt): 
                                            $opt_id = "var_" . md5($var_name) . "_" . $idx;
                                        ?>
                                            <div class="relative">
                                                <input type="radio" id="<?= $opt_id ?>" name="variations[<?= htmlspecialchars($var_name) ?>]" value="<?= htmlspecialchars(trim($opt)) ?>" class="sr-only var-radio" required <?= $idx === 0 ? 'checked' : '' ?>>
                                                
                                                <label for="<?= $opt_id ?>" class="var-pill flex items-center justify-center px-6 py-3 text-sm font-bold transition-all cursor-pointer select-none">
                                                    <?= htmlspecialchars(trim($opt)) ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </fieldset>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if($product['stock'] > 0 || strtolower(trim($product['product_type'] ?? '')) === 'digital'): ?>
                        <button type="submit" name="add_to_cart" class="shopify-btn w-full py-5 text-sm md:text-base font-black uppercase tracking-widest shadow-md">
                            Add to Cart
                        </button>
                    <?php else: ?>
                        <button disabled class="w-full py-5 text-sm font-black uppercase tracking-widest cursor-not-allowed" style="background: var(--card); border: 1px solid var(--border); color: var(--text); opacity: 0.5; border-radius: var(--radius);">Sold Out</button>
                    <?php endif; ?>
                </form>

                <div class="prose max-w-none text-base opacity-80 leading-relaxed" style="color: var(--text);">
                    <?= nl2br(htmlspecialchars($product['description'])) ?>
                </div>
            </div>

        </div>
    </main>

    <?php if(isset($_GET['added']) && $_GET['added'] == '1'): ?>
        <div class="fixed bottom-6 left-1/2 transform -translate-x-1/2 toast-notification z-50 w-[90%] max-w-sm">
            <div class="p-4 flex items-center justify-between shadow-2xl" style="background-color: var(--text); color: var(--bg); border-radius: var(--radius);">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-check-circle text-lg" style="color: var(--bg);"></i>
                    <span class="font-bold text-sm">Added to your cart</span>
                </div>
                <a href="cart.php?name=<?= urlencode($store_slug) ?>" class="px-4 py-2 text-xs font-bold uppercase tracking-widest transition hover:opacity-80" style="background-color: var(--bg); color: var(--text); border-radius: calc(var(--radius) - 2px);">
                    View Cart
                </a>
            </div>
        </div>
        <script> setTimeout(() => { window.history.replaceState({}, document.title, window.location.pathname + "?id=<?= $product_id ?>"); document.querySelector('.toast-notification').style.display = 'none'; }, 4000); </script>
    <?php endif; ?>

</body>
</html>
