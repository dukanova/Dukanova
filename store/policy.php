<?php
require '../common/config.php';
$store_slug = $_GET['name'] ?? '';
$policy_type = $_GET['type'] ?? 'refund';

$stmt = $pdo->prepare("SELECT * FROM sellers WHERE store_slug = ?");
$stmt->execute([$store_slug]);
$seller = $stmt->fetch();
if (!$seller) { die("Store not found!"); }

$cart_count = 0;
if (isset($_SESSION['cart'])) { foreach ($_SESSION['cart'] as $qty) { $cart_count += $qty; } }

$policy_title = "Store Policy";
$policy_content = "";

if ($policy_type === 'refund') {
    $policy_title = "Refund & Return Policy";
    $policy_content = $seller['refund_policy'] ?? '';
} elseif ($policy_type === 'shipping') {
    $policy_title = "Shipping Policy";
    $policy_content = $seller['shipping_policy'] ?? '';
} elseif ($policy_type === 'terms') {
    $policy_title = "Terms of Service";
    $policy_content = $seller['terms_policy'] ?? '';
}

if(empty(trim($policy_content))) {
    $policy_content = "This store hasn't updated their " . strtolower($policy_title) . " yet. Please contact the store owner directly for more information.";
}

require '../common/theme_engine.php';
$layout_style = $layout_style ?? 'standard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $policy_title ?> - <?= htmlspecialchars($seller['store_name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="<?= $font_import ?>" rel="stylesheet">
    <style> 
        body { font-family: <?= $font_family ?>; }
        #mobileMenuOverlay { transition: opacity 0.3s ease; }
        #mobileMenuDrawer { transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
    </style>
</head>
<body class="<?= $bg_color ?> <?= $text_color ?> antialiased min-h-screen flex flex-col">

    <?php ob_start(); ?>
        <a href="store.php?name=<?= urlencode($store_slug) ?>" class="hover:opacity-70 transition">Home</a>
        <a href="store.php?name=<?= urlencode($store_slug) ?>#catalog" class="hover:opacity-70 transition">Catalog</a>
        <?php if(!empty($seller['refund_policy']) || !empty($seller['shipping_policy'])): ?>
            <div class="relative group cursor-pointer z-50">
                <span class="hover:opacity-70 transition flex items-center">Policies <i class="fas fa-chevron-down ml-1 text-[10px]"></i></span>
                <div class="absolute top-full left-0 mt-2 w-48 <?= $card_bg ?> border <?= $border_class ?> shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all flex flex-col">
                    <?php if(!empty($seller['refund_policy'])): ?><a href="policy.php?name=<?= urlencode($store_slug) ?>&type=refund" class="px-4 py-3 border-b <?= $border_class ?> hover:bg-black hover:bg-opacity-5">Refund Policy</a><?php endif; ?>
                    <?php if(!empty($seller['shipping_policy'])): ?><a href="policy.php?name=<?= urlencode($store_slug) ?>&type=shipping" class="px-4 py-3 border-b <?= $border_class ?> hover:bg-black hover:bg-opacity-5">Shipping Policy</a><?php endif; ?>
                    <?php if(!empty($seller['terms_policy'])): ?><a href="policy.php?name=<?= urlencode($store_slug) ?>&type=terms" class="px-4 py-3 hover:bg-black hover:bg-opacity-5">Terms of Service</a><?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    <?php $nav_links = ob_get_clean(); ?>

    <?php if($layout_style === 'luxury'): ?>
        <header class="<?= $bg_color ?> border-b <?= $border_class ?> py-6 sticky top-0 z-40">
            <div class="max-w-6xl mx-auto px-4 flex flex-col items-center relative">
                <button onclick="toggleMobileMenu()" class="md:hidden absolute left-4 top-1/2 -translate-y-1/2 p-2 <?= $text_color ?> hover:opacity-70 transition"><i class="fas fa-bars text-2xl"></i></button>
                <a href="cart.php?name=<?= urlencode($store_slug) ?>" class="absolute right-4 top-1/2 -translate-y-1/2 p-2 <?= $text_color ?> hover:opacity-70 transition">
                    <i class="fas fa-shopping-bag text-2xl"></i>
                    <?php if($cart_count > 0): ?><span class="absolute top-0 right-0 bg-red-600 text-white text-[10px] font-bold h-4 w-4 rounded-full flex items-center justify-center"><?= $cart_count ?></span><?php endif; ?>
                </a>
                <?php if(!empty($seller['logo_image'])): ?><img src="../uploads/<?= htmlspecialchars($seller['logo_image']) ?>" class="h-16 w-16 rounded-full object-cover border <?= $border_class ?> mb-3"><?php endif; ?>
                <h1 class="text-2xl md:text-4xl font-black tracking-widest uppercase <?= $text_color ?> mb-4"><?= htmlspecialchars($seller['store_name']) ?></h1>
                <nav class="hidden md:flex space-x-8 text-xs font-bold uppercase tracking-widest <?= $text_color ?> border-t <?= $border_class ?> pt-4"><?= $nav_links ?></nav>
            </div>
        </header>
    <?php elseif($layout_style === 'brutalist'): ?>
        <header class="<?= $bg_color ?> border-b <?= $border_class ?> sticky top-0 z-40">
            <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <button onclick="toggleMobileMenu()" class="md:hidden <?= $text_color ?> hover:opacity-70 transition"><i class="fas fa-bars text-2xl"></i></button>
                    <?php if(!empty($seller['logo_image'])): ?><img src="../uploads/<?= htmlspecialchars($seller['logo_image']) ?>" class="h-12 w-12 object-cover border-2 <?= $border_class ?>"><?php endif; ?>
                    <h1 class="text-2xl md:text-3xl font-black tracking-tight uppercase <?= $text_color ?> hidden sm:block"><?= htmlspecialchars($seller['store_name']) ?></h1>
                </div>
                <nav class="hidden md:flex space-x-6 text-sm font-black uppercase tracking-widest <?= $text_color ?>"><?= $nav_links ?></nav>
                <a href="cart.php?name=<?= urlencode($store_slug) ?>" class="px-4 py-2 <?= $card_bg ?> border <?= $border_class ?> font-black uppercase text-xs <?= $text_color ?> hover:bg-black hover:text-white transition">CART [<?= $cart_count ?>]</a>
            </div>
        </header>
    <?php else: ?>
        <header class="<?= $card_bg ?> border-b <?= $border_class ?> sticky top-0 z-40 shadow-sm">
            <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <button onclick="toggleMobileMenu()" class="md:hidden <?= $text_color ?> hover:opacity-70 transition"><i class="fas fa-bars text-xl"></i></button>
                    <?php if(!empty($seller['logo_image'])): ?><img src="../uploads/<?= htmlspecialchars($seller['logo_image']) ?>" class="h-10 w-10 rounded-full object-cover border border-gray-200"><?php endif; ?>
                    <h1 class="text-xl md:text-2xl font-black tracking-tight <?= $text_color ?>"><?= htmlspecialchars($seller['store_name']) ?></h1>
                </div>
                <nav class="hidden md:flex space-x-8 text-sm font-bold uppercase tracking-wider <?= $text_muted ?>"><?= $nav_links ?></nav>
                <a href="cart.php?name=<?= urlencode($store_slug) ?>" class="relative p-2 <?= $text_color ?> hover:opacity-70 transition">
                    <i class="fas fa-shopping-bag text-2xl"></i>
                    <?php if($cart_count > 0): ?><span class="absolute top-0 right-0 bg-red-500 text-white text-[10px] font-bold h-5 w-5 rounded-full flex items-center justify-center border-2 border-white transform translate-x-1 -translate-y-1"><?= $cart_count ?></span><?php endif; ?>
                </a>
            </div>
        </header>
    <?php endif; ?>

    <main class="flex-grow max-w-4xl mx-auto px-4 py-12 md:py-20 w-full">
        <div class="<?= $card_bg ?> p-8 md:p-12 rounded-2xl border <?= $border_class ?> shadow-sm">
            <h1 class="<?= $layout_style == 'luxury' ? 'text-3xl md:text-5xl font-bold text-center mb-10' : ($layout_style == 'brutalist' ? 'text-3xl md:text-5xl font-black uppercase mb-10' : 'text-2xl md:text-4xl font-black mb-10') ?> <?= $text_color ?>">
                <?= $policy_title ?>
            </h1>
            
            <div class="text-sm md:text-base <?= $text_color ?> leading-relaxed md:leading-loose whitespace-pre-wrap opacity-80 font-medium">
                <?= htmlspecialchars($policy_content) ?>
            </div>
        </div>
    </main>

    <footer class="<?= $card_bg ?> border-t <?= $border_class ?> py-12 mt-auto">
        <div class="max-w-6xl mx-auto px-4 flex flex-col items-center">
            <h3 class="<?= $layout_style == 'luxury' ? 'font-bold uppercase tracking-widest text-xl' : 'font-black text-lg' ?> <?= $text_color ?> mb-4"><?= htmlspecialchars($seller['store_name']) ?></h3>
            <div class="flex flex-wrap justify-center gap-4 mb-8">
                <?php if(!empty($seller['refund_policy'])): ?><a href="policy.php?name=<?= urlencode($store_slug) ?>&type=refund" class="text-xs font-bold uppercase tracking-wider <?= $text_muted ?> hover:<?= $text_color ?> transition">Refund Policy</a><?php endif; ?>
                <?php if(!empty($seller['shipping_policy'])): ?><span class="<?= $text_muted ?> opacity-50 hidden sm:inline">•</span><a href="policy.php?name=<?= urlencode($store_slug) ?>&type=shipping" class="text-xs font-bold uppercase tracking-wider <?= $text_muted ?> hover:<?= $text_color ?> transition">Shipping Policy</a><?php endif; ?>
                <?php if(!empty($seller['terms_policy'])): ?><span class="<?= $text_muted ?> opacity-50 hidden sm:inline">•</span><a href="policy.php?name=<?= urlencode($store_slug) ?>&type=terms" class="text-xs font-bold uppercase tracking-wider <?= $text_muted ?> hover:<?= $text_color ?> transition">Terms of Service</a><?php endif; ?>
            </div>
            <p class="text-[10px] <?= $text_muted ?> uppercase tracking-widest font-bold">Powered by Dukaanova</p>
        </div>
    </footer>

    <div id="mobileMenuOverlay" class="fixed inset-0 bg-black bg-opacity-60 z-[100] opacity-0 pointer-events-none" onclick="toggleMobileMenu()">
        <div id="mobileMenuDrawer" class="absolute top-0 left-0 w-3/4 max-w-sm h-full <?= $card_bg ?> shadow-2xl transform -translate-x-full border-r <?= $border_class ?> flex flex-col" onclick="event.stopPropagation()">
            <div class="p-6 border-b <?= $border_class ?> flex justify-between items-center">
                <h2 class="font-black text-xl tracking-tight uppercase <?= $text_color ?>"><?= htmlspecialchars($seller['store_name']) ?></h2>
                <button onclick="toggleMobileMenu()" class="<?= $text_muted ?> hover:<?= $text_color ?> text-xl"><i class="fas fa-times"></i></button>
            </div>
            <div class="flex-grow overflow-y-auto p-6 flex flex-col space-y-2">
                <a href="store.php?name=<?= urlencode($store_slug) ?>" class="py-3 text-lg font-bold <?= $text_color ?> border-b <?= $border_class ?>">Home</a>
                <a href="store.php?name=<?= urlencode($store_slug) ?>#catalog" class="py-3 text-lg font-bold <?= $text_color ?> border-b <?= $border_class ?>">Catalog</a>
                <?php if(!empty($seller['refund_policy']) || !empty($seller['shipping_policy'])): ?>
                    <div class="py-3 text-sm font-bold uppercase tracking-widest <?= $text_muted ?> mt-4">Policies</div>
                    <?php if(!empty($seller['refund_policy'])): ?><a href="policy.php?name=<?= urlencode($store_slug) ?>&type=refund" class="py-2 text-base font-medium <?= $text_color ?>">Refund Policy</a><?php endif; ?>
                    <?php if(!empty($seller['shipping_policy'])): ?><a href="policy.php?name=<?= urlencode($store_slug) ?>&type=shipping" class="py-2 text-base font-medium <?= $text_color ?>">Shipping Policy</a><?php endif; ?>
                    <?php if(!empty($seller['terms_policy'])): ?><a href="policy.php?name=<?= urlencode($store_slug) ?>&type=terms" class="py-2 text-base font-medium <?= $text_color ?>">Terms of Service</a><?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="p-6 border-t <?= $border_class ?>">
                <a href="cart.php?name=<?= urlencode($store_slug) ?>" class="w-full <?= $btn_class ?> py-4 flex items-center justify-center text-sm font-black uppercase tracking-widest">
                    View Cart <span class="ml-2 bg-white text-black px-2 py-0.5 rounded text-xs"><?= $cart_count ?></span>
                </a>
            </div>
        </div>
    </div>

    <script>
        function toggleMobileMenu() {
            const overlay = document.getElementById('mobileMenuOverlay');
            const drawer = document.getElementById('mobileMenuDrawer');
            if (overlay.classList.contains('opacity-0')) {
                overlay.classList.remove('opacity-0', 'pointer-events-none');
                drawer.classList.remove('-translate-x-full');
            } else {
                overlay.classList.add('opacity-0', 'pointer-events-none');
                drawer.classList.add('-translate-x-full');
            }
        }
    </script>
</body>
</html>
