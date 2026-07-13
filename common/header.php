<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Dukaanova Marketplace</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Premium Shopify CSS Overrides */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;900&display=swap');
        body { font-family: 'Inter', sans-serif; background-color: #f9fafb !important; color: #111827 !important; display: flex; flex-direction: column; min-height: 100vh; }
        
        /* Auto-convert old dark cards to white */
        .bg-gray-900 { background-color: #f9fafb !important; } 
        .bg-gray-800 { background-color: #ffffff !important; box-shadow: 0 1px 3px rgba(0,0,0,0.05) !important; border: 1px solid #e5e7eb !important; } 
        
        /* Force text colors to match white theme */
        .text-white { color: #111827 !important; } 
        .text-gray-400 { color: #6b7280 !important; } 
        .text-indigo-400, .text-green-400 { color: #000000 !important; font-weight: 900 !important; } 
        
        /* Input fields */
        input, select, textarea { background-color: #ffffff !important; color: #111827 !important; border: 1px solid #d1d5db !important; border-radius: 4px !important; }
        
        /* THE MAGIC BUTTON FIX: Auto-convert all green/blue buttons to Shopify Black */
        button.bg-indigo-600, a.bg-indigo-600, button.bg-green-600, a.bg-green-600 { 
            background-color: #121212 !important; 
            color: #ffffff !important; 
            border-radius: 4px !important; 
            text-transform: uppercase; 
            letter-spacing: 1px; 
            font-size: 0.875rem; 
            font-weight: 900;
            transition: background-color 0.2s;
        }
        button.bg-indigo-600:hover, a.bg-indigo-600:hover, button.bg-green-600:hover, a.bg-green-600:hover { 
            background-color: #333333 !important; 
        }
        
        .main-content { flex-grow: 1; }
    </style>
</head>
<body class="antialiased">
    <?php
    $current_url = $_SERVER['REQUEST_URI'] ?? $_SERVER['PHP_SELF'];
    $is_seller = (strpos($current_url, '/seller/') !== false);
    $is_admin = (strpos($current_url, '/admin/') !== false);
    $is_store = (strpos($current_url, '/store/') !== false);
    $prefix = ($is_seller || $is_admin || $is_store) ? '../' : '';

    // TOP HEADER (Sirf Customers ke liye dikhega)
    if (!$is_seller && !$is_admin && !$is_store):
    ?>
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50 px-4 py-4 flex items-center justify-between">
        <a href="<?= $prefix ?>index.php" class="text-xl font-black tracking-tighter text-black uppercase">
            Dukaanova
        </a>
        <div class="flex items-center space-x-5">
            <a href="<?= $prefix ?>wallet.php" class="text-gray-800 hover:text-black transition"><i class="fas fa-wallet text-xl"></i></a>
            <a href="<?= $prefix ?>profile.php" class="text-gray-800 hover:text-black transition"><i class="far fa-user text-xl"></i></a>
            <a href="<?= $prefix ?>cart.php" class="text-gray-800 hover:text-black relative transition">
                <i class="fas fa-shopping-bag text-xl"></i>
                <?php 
                $header_cart_count = 0;
                if(isset($_SESSION['cart'])) { foreach($_SESSION['cart'] as $q) $header_cart_count += $q; }
                if($header_cart_count > 0): 
                ?>
                    <span class="absolute -top-2 -right-2 bg-black text-white text-[10px] font-bold h-4 w-4 rounded-full flex items-center justify-center"><?= $header_cart_count ?></span>
                <?php endif; ?>
            </a>
        </div>
    </header>
    <?php endif; ?>
    
    <main class="main-content">
