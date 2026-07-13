<?php
require '../common/config.php';
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM sellers WHERE user_id = ?");
$stmt->execute([$user_id]);
$seller = $stmt->fetch();
if (!$seller) { header("Location: store_profile.php"); exit; }
$seller_id = $seller['id'];

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$base_dir = str_replace('/seller/dashboard.php', '', $_SERVER['SCRIPT_NAME']);
$clean_store_link = $protocol . "://" . $_SERVER['HTTP_HOST'] . $base_dir . "/" . $seller['store_slug'];

$total_products = $pdo->query("SELECT COUNT(*) FROM products WHERE seller_id = $seller_id")->fetchColumn();
$total_orders = $pdo->query("SELECT COUNT(*) FROM order_items WHERE seller_id = $seller_id")->fetchColumn();
$net_revenue = $pdo->query("SELECT SUM(seller_amount) FROM order_items WHERE seller_id = $seller_id")->fetchColumn() ?: 0;

$is_banned = ($seller['account_status'] === 'blocked');

$stmt = $pdo->prepare("SELECT o.*, p.name as product_name, o.created_at, u.username as customer_name FROM order_items oi JOIN products p ON oi.product_id = p.id JOIN orders o ON oi.order_id = o.id JOIN users u ON o.user_id = u.id WHERE oi.seller_id = ? ORDER BY o.created_at DESC LIMIT 5");
$stmt->execute([$seller_id]);
$recent_orders = $stmt->fetchAll();

require '../common/header.php';
?>
<script> document.querySelector('meta[name="viewport"]')?.setAttribute('content', 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0'); </script>
<style>
    /* 🔥 MAGIC SCROLL FIX ADDED TO DASHBOARD 🔥 */
    html, body { background-color: #f4f6f8; margin: 0; padding: 0; overflow-x: hidden; -webkit-overflow-scrolling: touch; font-family: 'Inter', sans-serif; }
    
    .app-container { display: flex; min-height: 100vh; width: 100%; }
    
    .sidebar { background-color: #1a1a1a; color: #a1a1aa; width: 260px; flex-shrink: 0; transition: transform 0.3s ease; z-index: 60; display: flex; flex-direction: column; position: sticky; top: 0; height: 100vh; overflow-y: auto; }
    .nav-item { display: flex; align-items: center; padding: 12px 20px; color: #a1a1aa; text-decoration: none; font-size: 14px; font-weight: 600; transition: all 0.2s; border-left: 3px solid transparent; }
    .nav-item:hover, .nav-item.active { background-color: #27272a; color: #ffffff; border-left-color: #10b981; }
    .nav-item i { width: 24px; font-size: 16px; }
    
    .main-content { flex-grow: 1; display: flex; flex-direction: column; min-height: 100vh; background: #f4f6f8; width: calc(100% - 260px); }
    
    .shopify-card { background: #ffffff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border: 1px solid #e1e3e5; }
    
    /* 🔥 COMPACT AD CONTAINERS 🔥 */
    .ad-top { width: 100%; display: flex; justify-content: center; height: 50px; overflow: hidden; margin-bottom: 20px; }
    .ad-native { width: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; overflow: hidden; margin-top: 24px; background: white; border-radius: 12px; padding: 10px; border: 1px solid #e1e3e5; }

    @media (min-width: 769px) { nav.fixed.bottom-0 { display: none !important; } }
    @media (max-width: 768px) { 
        .app-container { display: block; } 
        .main-content { width: 100%; padding-bottom: 70px; min-height: 100vh; } 
        .sidebar { position: fixed; left: 0; transform: translateX(-100%); } 
        .sidebar.open { transform: translateX(0); } 
        .overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 50; } 
        .overlay.open { display: block; } 
    }
</style>

<?php if($is_banned): ?>
    <div class="h-screen w-screen bg-[#0a0a0a] flex items-center justify-center p-6 fixed inset-0 z-[100]">
        <div class="bg-[#171717] border border-red-900 p-8 rounded-3xl max-w-lg w-full text-center shadow-2xl">
            <i class="fas fa-ban text-7xl text-red-600 mb-6"></i>
            <h1 class="text-3xl font-black text-white mb-2 tracking-tight">Account Suspended</h1>
            <p class="text-gray-400 font-medium leading-relaxed mb-6">Your store has been suspended by the platform administrator for violating terms of service.</p>
        </div>
    </div>
<?php else: ?>

<div class="app-container">
    <div class="overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <aside class="sidebar" id="appSidebar">
        <div class="p-6 flex items-center space-x-3 border-b border-gray-800 mb-4">
            <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center font-black text-xl text-black overflow-hidden shrink-0 shadow-inner">
                <?php if(!empty($seller['logo_image'])): ?><img src="../uploads/<?= htmlspecialchars($seller['logo_image']) ?>" class="w-full h-full object-cover"><?php else: ?><?= strtoupper(substr($seller['store_name'], 0, 1)) ?><?php endif; ?>
            </div>
            <div class="overflow-hidden">
                <h2 class="text-white font-bold text-sm truncate w-full"><?= htmlspecialchars($seller['store_name']) ?></h2>
                <span class="text-[10px] bg-green-900 text-green-400 px-2 py-0.5 rounded-full uppercase tracking-widest font-bold">Free Plan</span>
            </div>
        </div>
        
        <nav class="flex-grow space-y-1 pb-4">
            <a href="dashboard.php" class="nav-item active"><i class="fas fa-home"></i> Home</a>
            <a href="orders.php" class="nav-item"><i class="fas fa-inbox"></i> Orders <span class="ml-auto bg-gray-700 text-white text-[10px] px-2 py-0.5 rounded-full"><?= $total_orders ?></span></a>
            <a href="products.php" class="nav-item"><i class="fas fa-tags"></i> Products</a>
            <a href="customers.php" class="nav-item"><i class="fa-solid fa-users"></i> Customers</a>
            <div class="px-5 py-3 mt-4 mb-1 text-[10px] font-bold text-gray-500 uppercase tracking-widest">Storefront</div>
            <a href="themes.php" class="nav-item"><i class="fas fa-palette"></i> Theme Store</a>
            <a href="branding.php" class="nav-item"><i class="fas fa-paint-roller"></i> Branding</a>
             <a href="coupons.php" class="nav-item"><?php
echo '<i class="fa-solid fa-ticket"></i>';
?></i> Coupons</a>
            
            <div class="px-5 py-3 mt-4 mb-1 text-[10px] font-bold text-gray-500 uppercase tracking-widest">Settings</div>
            <a href="policies.php" class="nav-item"><i class="fas fa-balance-scale"></i> Legal Policies</a>
            <a href="store_profile.php" class="nav-item"><i class="fas fa-cog"></i> General Settings</a>
        </nav>
    </aside>

    <main class="main-content relative">
        <header class="bg-white border-b border-[#e1e3e5] px-4 md:px-8 py-4 flex items-center justify-between sticky top-0 z-30 shadow-sm">
            <div class="flex items-center"><button onclick="toggleSidebar()" class="md:hidden mr-4 text-gray-600 hover:text-black text-xl"><i class="fas fa-bars"></i></button><h1 class="text-xl font-black text-[#202223] tracking-tight">Dashboard</h1></div>
            <div class="flex items-center space-x-3"><a href="../store/store.php?name=<?= $seller['store_slug'] ?>" target="_blank" class="w-8 h-8 bg-gray-100 text-gray-600 rounded-full flex items-center justify-center hover:bg-gray-200 transition"><i class="fas fa-eye text-sm"></i></a></div>
        </header>

        <div class="p-4 md:p-8 max-w-5xl w-full mx-auto pb-10">
            
            <div class="ad-top">
                <script>
                  atOptions = { 'key' : 'cb24b5c155630706146a8df001ea0cab', 'format' : 'iframe', 'height' : 50, 'width' : 320, 'params' : {} };
                </script>
                <script src="https://www.highperformanceformat.com/cb24b5c155630706146a8df001ea0cab/invoke.js"></script>
            </div>

            <div class="bg-white p-6 text-white mb-8 shadow-lg relative overflow-hidden">
                <div class="absolute right-0 top-0 opacity-10 transform translate-x-10 -translate-y-10"><i class="fas fa-rocket text-9xl"></i></div>
                <h2 class="text-2xl font-black mb-1 relative z-10">Welcome back, <?= htmlspecialchars($seller['store_name']) ?>! 🚀</h2>
                <p class="text-sm text-black-300 mb-4 relative z-10 max-w-xl">Enjoy your 100% Free Store. We don't charge any commission on your sales!</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6 mb-8">
                <div class="shopify-card p-6 hover:shadow-md transition"><p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-1 flex items-center"><i class="fas fa-wallet mr-2"></i> Sales</p><h3 class="text-3xl font-black text-[#202223]">₹<?= number_format($net_revenue, 2) ?></h3></div>
                <div class="shopify-card p-6 hover:shadow-md transition"><p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-1 flex items-center"><i class="fas fa-box-open mr-2"></i> Orders</p><h3 class="text-3xl font-black text-[#202223]"><?= $total_orders ?></h3></div>
                <div class="shopify-card p-6 bg-green-50 border-green-200 hover:shadow-md transition"><p class="text-xs font-bold text-green-700 uppercase tracking-widest mb-1 flex items-center"><i class="fas fa-hand-holding-usd mr-2"></i> Platform Fee</p><h3 class="text-3xl font-black text-green-600">0%</h3></div>
            </div>

            <div class="flex justify-between items-end mb-4"><h2 class="text-lg font-black text-[#202223]">Recent Orders</h2><?php if(!empty($recent_orders)): ?><a href="orders.php" class="text-sm text-indigo-600 font-bold hover:underline">View All</a><?php endif; ?></div>
            
            <div class="shopify-card overflow-hidden">
                <?php if(empty($recent_orders)): ?>
                    <div class="p-12 text-center bg-gray-50"><div class="w-16 h-16 bg-white border border-[#e1e3e5] rounded-full flex items-center justify-center mx-auto mb-4 text-gray-300 shadow-sm"><i class="fas fa-receipt text-2xl"></i></div><h3 class="text-lg font-bold text-gray-900 mb-1">No orders yet</h3></div>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse min-w-[500px]">
                            <thead>
                                <tr class="bg-gray-50 text-[10px] uppercase tracking-widest text-gray-500 border-b border-[#e1e3e5]"><th class="p-4 font-bold">Order ID</th><th class="p-4 font-bold">Customer</th><th class="p-4 font-bold text-right">Amount</th><th class="p-4 font-bold text-center">Status</th></tr>
                            </thead>
                            <tbody class="divide-y divide-[#e1e3e5]">
                                <?php foreach($recent_orders as $o): ?>
                                <tr class="hover:bg-gray-50 transition cursor-pointer" onclick="window.location.href='orders.php'">
                                    <td class="p-4 font-mono text-sm font-bold text-gray-900">#<?= str_pad($o['order_id'], 5, "0", STR_PAD_LEFT) ?></td><td class="p-4 text-sm font-bold text-[#202223]"><?= htmlspecialchars($o['customer_name']) ?></td><td class="p-4 text-sm font-black text-[#202223] text-right">₹<?= number_format($o['seller_amount'], 2) ?></td><td class="p-4 text-center"><span class="text-[10px] bg-yellow-100 text-yellow-800 px-2.5 py-1 rounded-md font-bold uppercase tracking-wide"><?= $o['order_status'] ?? 'Pending' ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="ad-native">
                <span class="text-[9px] font-bold uppercase text-gray-400 mb-1 block w-full text-center">Sponsored Links</span>
                <script async="async" data-cfasync="false" src="https://pl28928584.effectivegatecpm.com/bce8b52ffd4151a9a24b8128dfd3379f/invoke.js"></script>
                <div id="container-bce8b52ffd4151a9a24b8128dfd3379f"></div>
            </div>

        </div>
    </main>
</div>

<script>function toggleSidebar() { document.getElementById('appSidebar').classList.toggle('open'); document.getElementById('sidebarOverlay').classList.toggle('open'); }</script>

<script src="https://pl28928530.effectivegatecpm.com/18/a9/15/18a915938284fd102de126e7b0b53e7c.js"></script>

<script async src="https://js.onclckmn.com/static/onclicka.js" data-admpid="432027"></script>

<?php endif; require '../common/bottom.php'; ?>
