<?php
require '../common/config.php';

// 🔥 FRICTIONLESS GUEST CART LOGIC 🔥
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = rand(100000, 999999);
}
$user_id = $_SESSION['user_id'];
$store_slug = isset($_GET['name']) ? trim($_GET['name']) : '';

// Fetch Seller Details
$stmt = $pdo->prepare("SELECT * FROM sellers WHERE store_slug = ?");
$stmt->execute([$store_slug]);
$seller = $stmt->fetch();

if (!$seller) {
    die("<div style='font-family:sans-serif; text-align:center; padding:50px; background:#f4f6f8; min-height:100vh;'><h1 style='font-size:3rem; margin-bottom:10px;'>🕵️‍♂️</h1><h2>Store Not Found</h2><p style='color:#666;'>The store you are looking for is currently unavailable.</p></div>");
}
$seller_id = $seller['id'];

// 🔥 QUICK ADD TO CART LOGIC 🔥
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['quick_add'])) {
    $product_id = (int)$_POST['product_id'];
    
    $check_stmt = $pdo->prepare("SELECT id FROM cart WHERE user_id = ? AND product_id = ? AND (variation_data IS NULL OR variation_data = 'null' OR variation_data = '')");
    $check_stmt->execute([$user_id, $product_id]);
    $existing_cart = $check_stmt->fetch();
    
    if ($existing_cart) {
        $pdo->prepare("UPDATE cart SET quantity = quantity + 1 WHERE id = ?")->execute([$existing_cart['id']]);
    } else {
        $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)")->execute([$user_id, $product_id]);
    }
    header("Location: store.php?name=" . urlencode($store_slug) . "&added=1");
    exit;
}

// Fetch Products & Cart Count
// Added JSON length check for variations to implement Shopify's "Choose Options" logic
$p_stmt = $pdo->prepare("SELECT * FROM products WHERE seller_id = ? ORDER BY id DESC");
$p_stmt->execute([$seller_id]);
$products = $p_stmt->fetchAll();

$c_stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ? AND product_id IN (SELECT id FROM products WHERE seller_id = ?)");
$c_stmt->execute([$user_id, $seller_id]);
$cart_count = $c_stmt->fetchColumn() ?: 0;

$announcement = '';
if (!empty($seller['announcement_text'])) {
    $arr = json_decode($seller['announcement_text'], true);
    if(is_array($arr) && isset($arr[0])) $announcement = $arr[0];
}

// 🔥 ULTRA-PREMIUM THEME ENGINE (Shopify Grade) 🔥
$themes = [
    'dawn' => ['bg' => '#ffffff', 'text' => '#121212', 'card' => '#f4f6f8', 'border' => '#e1e3e5', 'primary' => '#000000', 'primary_text' => '#ffffff', 'font' => "'Inter', sans-serif", 'radius' => '0.5rem', 'shadow' => '0 4px 6px -1px rgba(0,0,0,0.05)'],
    'ocean' => ['bg' => '#f0f9ff', 'text' => '#0c4a6e', 'card' => '#ffffff', 'border' => '#bae6fd', 'primary' => '#0284c7', 'primary_text' => '#ffffff', 'font' => "'Inter', sans-serif", 'radius' => '1rem', 'shadow' => '0 10px 15px -3px rgba(2,132,199,0.1)'],
    'pastel' => ['bg' => '#fdf2f8', 'text' => '#831843', 'card' => '#ffffff', 'border' => '#fbcfe8', 'primary' => '#db2777', 'primary_text' => '#ffffff', 'font' => "'Quicksand', sans-serif", 'radius' => '1.5rem', 'shadow' => '0 10px 25px -5px rgba(219,39,119,0.1)'],
    'street' => ['bg' => '#f3f4f6', 'text' => '#111827', 'card' => '#ffffff', 'border' => '#000000', 'primary' => '#ef4444', 'primary_text' => '#ffffff', 'font' => "'Impact', sans-serif", 'radius' => '0px', 'shadow' => '4px 4px 0px #000000'],
    'vintage' => ['bg' => '#fef3c7', 'text' => '#451a03', 'card' => '#fffbeb', 'border' => '#fde68a', 'primary' => '#b45309', 'primary_text' => '#ffffff', 'font' => "'Playfair Display', serif", 'radius' => '0.25rem', 'shadow' => '0 4px 6px -1px rgba(180,83,9,0.15)'],
    'midnight' => ['bg' => '#0f172a', 'text' => '#f8fafc', 'card' => '#1e293b', 'border' => '#334155', 'primary' => '#3b82f6', 'primary_text' => '#ffffff', 'font' => "'Inter', sans-serif", 'radius' => '0.75rem', 'shadow' => '0 10px 15px -3px rgba(0,0,0,0.5)'],
    'neon' => ['bg' => '#000000', 'text' => '#2dd4bf', 'card' => '#0f172a', 'border' => '#2dd4bf', 'primary' => '#a855f7', 'primary_text' => '#ffffff', 'font' => "'Space Mono', monospace", 'radius' => '0px', 'shadow' => '0 0 15px rgba(45,212,191,0.2)'],
];

$current_theme = strtolower(trim($seller['theme'] ?? 'dawn'));
$t = $themes[$current_theme] ?? $themes['dawn'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($seller['store_name']) ?> - Shop</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;800;900&family=Playfair+Display:wght@600;800&family=Quicksand:wght@500;700;800&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg: <?= $t['bg'] ?>; --text: <?= $t['text'] ?>; --card: <?= $t['card'] ?>; 
            --border: <?= $t['border'] ?>; --primary: <?= $t['primary'] ?>; 
            --primary-text: <?= $t['primary_text'] ?>; --radius: <?= $t['radius'] ?>; 
            --shadow: <?= $t['shadow'] ?>; --font: <?= $t['font'] ?>;
        }
        
        body { background-color: var(--bg); color: var(--text); font-family: var(--font); -webkit-font-smoothing: antialiased; }
        
        /* Premium UI Components */
        .shopify-btn { 
            background-color: var(--primary); color: var(--primary-text); 
            border-radius: var(--radius); transition: all 0.3s ease;
        }
        .shopify-btn:hover { opacity: 0.85; transform: scale(0.98); }
        
        .shopify-btn-outline { 
            background-color: transparent; color: var(--text); 
            border: 1px solid var(--border); border-radius: var(--radius); transition: all 0.3s ease;
        }
        .shopify-btn-outline:hover { border-color: var(--text); }
        
        .shopify-card { 
            background-color: transparent; transition: all 0.4s ease;
        }
        
        /* Image Hover Zoom effect (Like Shopify Dawn) */
        .img-wrapper { overflow: hidden; border-radius: var(--radius); background-color: var(--card); border: 1px solid var(--border); }
        .img-zoom { transition: transform 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94); }
        .shopify-card:hover .img-zoom { transform: scale(1.05); }
        
        /* Glassmorphism Header */
        .glass-header { 
            background-color: color-mix(in srgb, var(--bg) 90%, transparent); 
            backdrop-filter: blur(12px); border-bottom: 1px solid var(--border);
        }

        /* Brutalist Specific Overrides */
        <?php if($current_theme == 'street'): ?>
            .shopify-btn:hover, .img-wrapper { box-shadow: var(--shadow); transform: translate(-2px, -2px); }
        <?php endif; ?>

        @keyframes slideUp { from { bottom: -50px; opacity: 0; } to { bottom: 20px; opacity: 1; } }
        .toast-notification { animation: slideUp 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
    </style>
</head>
<body class="flex flex-col min-h-screen">

    <?php if(!empty($announcement)): ?>
    <div class="w-full text-center py-2.5 text-[11px] font-bold uppercase tracking-[0.2em]" style="background-color: var(--primary); color: var(--primary-text);">
        <p class="max-w-6xl mx-auto px-4 truncate"><?= htmlspecialchars($announcement) ?></p>
    </div>
    <?php endif; ?>

    <header class="glass-header sticky top-0 z-50 transition-all">
        <div class="max-w-7xl mx-auto px-4 md:px-8 py-4 flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <?php if(!empty($seller['logo_image'])): ?>
                    <a href="store.php?name=<?= urlencode($store_slug) ?>" class="w-10 h-10 md:w-12 md:h-12 overflow-hidden flex-shrink-0 transition-transform hover:scale-105" style="border-radius: var(--radius); border: 1px solid var(--border);">
                        <img src="../uploads/<?= htmlspecialchars($seller['logo_image']) ?>" class="w-full h-full object-cover">
                    </a>
                <?php endif; ?>
                <a href="store.php?name=<?= urlencode($store_slug) ?>" class="text-xl md:text-2xl font-black tracking-tight" style="color: var(--text);">
                    <?= htmlspecialchars($seller['store_name']) ?>
                </a>
            </div>

            <div class="flex items-center space-x-6">
                <a href="cart.php?name=<?= urlencode($store_slug) ?>" class="relative flex items-center group" style="color: var(--text);">
                    <i class="fas fa-shopping-bag text-xl md:text-2xl transition-transform group-hover:scale-110"></i>
                    <?php if($cart_count > 0): ?>
                        <span class="absolute -top-1.5 -right-2 w-4 h-4 md:w-5 md:h-5 flex items-center justify-center rounded-full text-[9px] md:text-[10px] font-bold shadow-sm" style="background-color: var(--primary); color: var(--primary-text);">
                            <?= $cart_count ?>
                        </span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </header>

    <?php if(!empty($seller['banner_image'])): ?>
    <div class="w-full h-[40vh] md:h-[60vh] relative bg-gray-900 overflow-hidden" style="border-bottom: 1px solid var(--border);">
        <img src="../uploads/<?= htmlspecialchars($seller['banner_image']) ?>" class="w-full h-full object-cover opacity-70 scale-105" style="animation: slowZoom 20s infinite alternate;">
        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>
        <div class="absolute inset-0 flex flex-col justify-end items-center md:items-start p-6 md:p-16 max-w-7xl mx-auto text-center md:text-left z-10">
            <h2 class="text-3xl md:text-5xl font-black text-white tracking-tight mb-4 drop-shadow-lg"><?= htmlspecialchars($seller['store_name']) ?></h2>
            <p class="text-white/90 text-sm md:text-lg font-medium max-w-2xl drop-shadow-md"><?= htmlspecialchars($seller['description'] ?? 'Discover our premium collection.') ?></p>
        </div>
    </div>
    <style>@keyframes slowZoom { from { transform: scale(1); } to { transform: scale(1.1); } }</style>
    <?php endif; ?>

    <main class="flex-grow max-w-7xl mx-auto px-4 md:px-8 py-12 md:py-16 w-full">
        
        <div class="flex items-end justify-between mb-8 md:mb-12">
            <h2 class="text-2xl md:text-3xl font-black tracking-tight" style="color: var(--text);">New Arrivals</h2>
            <span class="text-sm font-medium opacity-60"><?= count($products) ?> products</span>
        </div>

        <?php if(empty($products)): ?>
            <div class="py-20 text-center flex flex-col items-center justify-center" style="border: 1px dashed var(--border); border-radius: var(--radius);">
                <i class="fas fa-box-open text-4xl mb-4 opacity-30" style="color: var(--text);"></i>
                <h3 class="text-xl font-bold mb-2" style="color: var(--text);">No products found</h3>
                <p class="text-sm opacity-60" style="color: var(--text);">This store is currently setting up their inventory.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-x-4 gap-y-10 md:gap-x-6 md:gap-y-12">
                <?php foreach($products as $p): 
                    // 🔥 SHOPIFY LOGIC: Check if product has variations
                    $has_variations = !empty($p['variations']) && $p['variations'] !== 'null' && $p['variations'] !== '[]';
                ?>
                
                <div class="shopify-card group flex flex-col h-full">
                    <a href="product.php?id=<?= $p['id'] ?>" class="block relative w-full aspect-[4/5] img-wrapper mb-4">
                        <?php if($p['image']): ?>
                            <img src="../uploads/<?= $p['image'] ?>" class="w-full h-full object-cover img-zoom">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center opacity-10"><i class="fas fa-image text-5xl"></i></div>
                        <?php endif; ?>
                        
                        <div class="absolute top-3 left-3 flex flex-col gap-2">
                            <?php if($p['stock'] <= 0 && strtolower(trim($p['product_type'] ?? '')) !== 'digital'): ?>
                                <span class="bg-white text-black text-[9px] font-black uppercase tracking-widest px-2.5 py-1 shadow-md" style="border-radius: var(--radius);">Sold Out</span>
                            <?php endif; ?>
                            <?php if(strtolower(trim($p['product_type'] ?? '')) === 'digital'): ?>
                                <span class="text-[9px] font-black uppercase tracking-widest px-2.5 py-1 shadow-md" style="background-color: var(--primary); color: var(--primary-text); border-radius: var(--radius);">Digital</span>
                            <?php endif; ?>
                        </div>
                    </a>
                    
                    <div class="flex flex-col flex-grow">
                        <a href="product.php?id=<?= $p['id'] ?>" class="text-sm md:text-base font-bold leading-tight mb-2 hover:underline line-clamp-2" style="color: var(--text);">
                            <?= htmlspecialchars($p['name']) ?>
                        </a>
                        
                        <div class="mt-auto pt-2">
                            <p class="text-base md:text-lg font-black mb-4" style="color: var(--text);">₹<?= number_format($p['price'], 2) ?></p>
                            
                            <?php if($p['stock'] > 0 || strtolower(trim($p['product_type'] ?? '')) === 'digital'): ?>
                                <?php if($has_variations): ?>
                                    <a href="product.php?id=<?= $p['id'] ?>" class="shopify-btn-outline w-full py-2.5 text-xs font-bold uppercase tracking-widest flex items-center justify-center text-center">
                                        Choose Options
                                    </a>
                                <?php else: ?>
                                    <form method="POST">
                                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                        <button type="submit" name="quick_add" class="shopify-btn w-full py-2.5 text-xs font-bold uppercase tracking-widest flex items-center justify-center">
                                            Quick Add
                                        </button>
                                    </form>
                                <?php endif; ?>
                            <?php else: ?>
                                <button disabled class="w-full py-2.5 text-xs font-bold uppercase tracking-widest cursor-not-allowed opacity-50" style="background-color: var(--card); border: 1px solid var(--border); color: var(--text); border-radius: var(--radius);">
                                    Out of Stock
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <footer class="mt-auto py-10" style="background-color: var(--card); border-top: 1px solid var(--border);">
        <div class="max-w-7xl mx-auto px-4 md:px-8 flex flex-col md:flex-row justify-between items-center gap-6">
            <div class="text-center md:text-left">
                <p class="text-sm font-bold" style="color: var(--text);"><?= htmlspecialchars($seller['store_name']) ?></p>
                <p class="text-xs font-medium opacity-60 mt-1">© <?= date('Y') ?> All rights reserved.</p>
            </div>
            <div class="flex flex-wrap justify-center gap-x-6 gap-y-2">
                <?php if(!empty($seller['refund_policy'])): ?><a href="policy.php?name=<?= urlencode($store_slug) ?>&type=refund" class="text-xs font-bold opacity-70 hover:opacity-100 transition" style="color: var(--text);">Refund Policy</a><?php endif; ?>
                <?php if(!empty($seller['shipping_policy'])): ?><a href="policy.php?name=<?= urlencode($store_slug) ?>&type=shipping" class="text-xs font-bold opacity-70 hover:opacity-100 transition" style="color: var(--text);">Shipping Policy</a><?php endif; ?>
                <?php if(!empty($seller['terms_policy'])): ?><a href="policy.php?name=<?= urlencode($store_slug) ?>&type=terms" class="text-xs font-bold opacity-70 hover:opacity-100 transition" style="color: var(--text);">Terms of Service</a><?php endif; ?>
            </div>
        </div>
    </footer>

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
        <script> setTimeout(() => { window.history.replaceState({}, document.title, window.location.pathname + "?name=<?= urlencode($store_slug) ?>"); document.querySelector('.toast-notification').style.display = 'none'; }, 4000); </script>
    <?php endif; ?>

</body>
</html>