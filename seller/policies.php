<?php
require '../common/config.php';
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM sellers WHERE user_id = ?");
$stmt->execute([$user_id]);
$seller = $stmt->fetch();
if (!$seller) { header("Location: store_profile.php"); exit; }
$seller_id = $seller['id'];

$msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_policies'])) {
    $refund = trim($_POST['refund_policy']);
    $shipping = trim($_POST['shipping_policy']);
    $terms = trim($_POST['terms_policy']);

    $stmt = $pdo->prepare("UPDATE sellers SET refund_policy=?, shipping_policy=?, terms_policy=? WHERE id=?");
    if ($stmt->execute([$refund, $shipping, $terms, $seller_id])) {
        $msg = "<div class='bg-green-50 text-green-700 p-4 rounded-xl border border-green-200 mb-6 font-bold flex items-center shadow-sm'><i class='fas fa-balance-scale text-xl mr-3'></i> Legal policies saved successfully!</div>";
        $seller['refund_policy'] = $refund; $seller['shipping_policy'] = $shipping; $seller['terms_policy'] = $terms;
    }
}
require '../common/header.php';
?>
<style>
    html, body { background-color: #f4f6f8; margin: 0; padding: 0; overflow-x: hidden; -webkit-overflow-scrolling: touch; font-family: 'Inter', sans-serif; }
    .app-container { display: flex; min-height: 100vh; width: 100%; }
    .sidebar { background-color: #1a1a1a; color: #a1a1aa; width: 260px; flex-shrink: 0; transition: transform 0.3s ease; z-index: 60; display: flex; flex-direction: column; position: sticky; top: 0; height: 100vh; overflow-y: auto; }
    .nav-item { display: flex; align-items: center; padding: 12px 20px; color: #a1a1aa; text-decoration: none; font-size: 14px; font-weight: 600; transition: all 0.2s; border-left: 3px solid transparent; }
    .nav-item:hover, .nav-item.active { background-color: #27272a; color: #ffffff; border-left-color: #10b981; }
    .nav-item i { width: 24px; font-size: 16px; }
    .main-content { flex-grow: 1; display: flex; flex-direction: column; min-height: 100vh; background: #f4f6f8; width: calc(100% - 260px); }
    .shopify-input { background-color: #f9fafb; border: 1px solid #e1e3e5; border-radius: 8px; padding: 12px; width: 100%; font-size: 14px; font-weight: 500; outline: none; }
    .ad-banner-320 { width: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 70px; overflow: hidden; margin: 16px 0; }
    @media (max-width: 768px) { .app-container { display: block; } .main-content { width: 100%; padding-bottom: 70px; min-height: 100vh; } .sidebar { position: fixed; left: 0; transform: translateX(-100%); } .sidebar.open { transform: translateX(0); } .overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 50; } .overlay.open { display: block; } }
</style>

<div class="app-container">
    <div class="overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
    <aside class="sidebar" id="appSidebar">
        <div class="p-6 flex items-center space-x-3 border-b border-gray-800 mb-4">
            <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center font-black text-xl text-black overflow-hidden shrink-0 shadow-inner">
                <?php if(!empty($seller['logo_image'])): ?><img src="../uploads/<?= htmlspecialchars($seller['logo_image']) ?>" class="w-full h-full object-cover"><?php else: ?><?= strtoupper(substr($seller['store_name'], 0, 1)) ?><?php endif; ?>
            </div>
            <div class="overflow-hidden"><h2 class="text-white font-bold text-sm truncate w-full"><?= htmlspecialchars($seller['store_name']) ?></h2><span class="text-[10px] bg-green-900 text-green-400 px-2 py-0.5 rounded-full uppercase tracking-widest font-bold">Free Plan</span></div>
        </div>
        <nav class="flex-grow space-y-1 pb-4">
            <a href="dashboard.php" class="nav-item"><i class="fas fa-home"></i> Home</a>
            <a href="orders.php" class="nav-item"><i class="fas fa-inbox"></i> Orders</a>
            <a href="products.php" class="nav-item"><i class="fas fa-tags"></i> Products</a>
            <a href="customers.php" class="nav-item"><i class="fa-solid fa-users"></i> Customers</a>
            <div class="px-5 py-3 mt-4 mb-1 text-[10px] font-bold text-gray-500 uppercase tracking-widest">Storefront</div>
            <a href="themes.php" class="nav-item"><i class="fas fa-palette"></i> Theme Store</a>
            <a href="branding.php" class="nav-item"><i class="fas fa-paint-roller"></i> Branding</a>
            <a href="coupons.php" class="nav-item"><i class="fas fa-ticket-alt"></i> Coupons</a>
            <div class="px-5 py-3 mt-4 mb-1 text-[10px] font-bold text-gray-500 uppercase tracking-widest">Settings</div>
            <a href="policies.php" class="nav-item active"><i class="fas fa-balance-scale"></i> Legal Policies</a>
            <a href="store_profile.php" class="nav-item"><i class="fas fa-cog"></i> General Settings</a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="bg-white border-b border-[#e1e3e5] px-4 md:px-8 py-4 flex items-center justify-between sticky top-0 z-30 shadow-sm">
            <div class="flex items-center"><button onclick="toggleSidebar()" class="md:hidden mr-4 text-gray-600 hover:text-black text-xl"><i class="fas fa-bars"></i></button><h1 class="text-xl font-black text-[#202223] tracking-tight">Legal Policies</h1></div>
            <div class="flex items-center space-x-3"><a href="../store/store.php?name=<?= $seller['store_slug'] ?>" target="_blank" class="w-8 h-8 bg-gray-100 text-gray-600 rounded-full flex items-center justify-center hover:bg-gray-200 transition"><i class="fas fa-eye text-sm"></i></a></div>
        </header>

        <div class="p-4 md:p-8 max-w-4xl mx-auto w-full pb-10">
            
            <div class="ad-banner-320">
                <span class="text-[9px] font-bold uppercase text-gray-400 mb-1">Sponsored</span>
                <script> atOptions = { 'key' : '112d765d1f534434ab043c564c721dab', 'format' : 'iframe', 'height' : 50, 'width' : 320, 'params' : {} }; </script>
                <script src="https://www.highperformanceformat.com/112d765d1f534434ab043c564c721dab/invoke.js"></script>
            </div>

            <?= $msg ?>

            <form method="POST" class="space-y-6">
                <div class="bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-[#e1e3e5]">
                    <p class="text-sm text-gray-500 mb-6 font-medium leading-relaxed">These policies appear as links in your storefront footer to build trust.</p>
                    <div class="space-y-6">
                        <div><label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1.5">Refund & Return Policy</label><textarea name="refund_policy" rows="4" class="shopify-input"><?= htmlspecialchars($seller['refund_policy'] ?? '') ?></textarea></div>
                        <div><label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1.5">Shipping Policy</label><textarea name="shipping_policy" rows="4" class="shopify-input"><?= htmlspecialchars($seller['shipping_policy'] ?? '') ?></textarea></div>
                        <div><label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1.5">Terms of Service</label><textarea name="terms_policy" rows="4" class="shopify-input"><?= htmlspecialchars($seller['terms_policy'] ?? '') ?></textarea></div>
                    </div>
                </div>
                
                <div class="ad-banner-320 mb-6">
                    <span class="text-[9px] font-bold uppercase text-gray-400 mb-1">Sponsored</span>
                    <script> atOptions = { 'key' : 'c84ae23d36c8befc3df5f872bb6555ce', 'format' : 'iframe', 'height' : 50, 'width' : 320, 'params' : {} }; </script>
                    <script src="https://www.highperformanceformat.com/c84ae23d36c8befc3df5f872bb6555ce/invoke.js"></script>
                </div>

                <button type="submit" name="save_policies" class="w-full bg-[#ffffff] hover:bg-[#333333] text-white py-4 rounded-xl text-sm font-black uppercase tracking-widest transition shadow-lg">Publish Policies <i class="fas fa-check ml-2"></i></button>
            </form>
        </div>
    </main>
</div>
<script>function toggleSidebar() { document.getElementById('appSidebar').classList.toggle('open'); document.getElementById('sidebarOverlay').classList.toggle('open'); }</script>
<?php require '../common/bottom.php'; ?>
