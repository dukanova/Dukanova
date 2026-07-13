<?php
require '../common/config.php';
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM sellers WHERE user_id = ?");
$stmt->execute([$user_id]);
$seller = $stmt->fetch();
if (!$seller) { header("Location: store_profile.php"); exit; }
$seller_id = $seller['id'];

// Create table if not exists
try { $pdo->exec("CREATE TABLE IF NOT EXISTS coupons (id INT AUTO_INCREMENT PRIMARY KEY, seller_id INT NOT NULL, code VARCHAR(50) NOT NULL, discount_type VARCHAR(20) NOT NULL, discount_value DECIMAL(10,2) NOT NULL, min_amount DECIMAL(10,2) DEFAULT 0, is_active TINYINT(1) DEFAULT 1)"); } catch(Exception $e) {}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_coupon'])) {
    $code = strtoupper(trim($_POST['code']));
    $type = $_POST['discount_type'];
    $value = (float)$_POST['discount_value'];
    $min = (float)$_POST['min_amount'];
    
    $stmt = $pdo->prepare("INSERT INTO coupons (seller_id, code, discount_type, discount_value, min_amount) VALUES (?, ?, ?, ?, ?)");
    if($stmt->execute([$seller_id, $code, $type, $value, $min])) {
        $msg = "<div class='bg-green-50 text-green-700 p-4 rounded-xl border border-green-200 mb-6 font-bold flex items-center'><i class='fas fa-check-circle text-xl mr-3'></i> Coupon added!</div>";
    }
}
if(isset($_GET['delete'])) {
    $pdo->query("DELETE FROM coupons WHERE id=".(int)$_GET['delete']." AND seller_id=$seller_id");
    header("Location: coupons.php"); exit;
}

$coupons = $pdo->query("SELECT * FROM coupons WHERE seller_id=$seller_id ORDER BY id DESC")->fetchAll();
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
    .shopify-input { background-color: #f9fafb; border: 1px solid #e1e3e5; border-radius: 8px; padding: 12px; width: 100%; font-size: 14px; font-weight: 600; outline: none; }
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
            <a href="coupons.php" class="nav-item active"><i class="fas fa-ticket-alt"></i> Coupons</a>
            <div class="px-5 py-3 mt-4 mb-1 text-[10px] font-bold text-gray-500 uppercase tracking-widest">Settings</div>
            <a href="policies.php" class="nav-item"><i class="fas fa-balance-scale"></i> Legal Policies</a>
            <a href="store_profile.php" class="nav-item"><i class="fas fa-cog"></i> General Settings</a>
        </nav>
    </aside>
    <main class="main-content">
        <header class="bg-white border-b border-[#e1e3e5] px-4 md:px-8 py-4 flex items-center justify-between sticky top-0 z-30 shadow-sm">
            <div class="flex items-center"><button onclick="toggleSidebar()" class="md:hidden mr-4 text-gray-600 hover:text-black text-xl"><i class="fas fa-bars"></i></button><h1 class="text-xl font-black text-[#202223] tracking-tight">Coupons</h1></div>
            <div class="flex items-center space-x-3"><a href="../store/store.php?name=<?= $seller['store_slug'] ?>" target="_blank" class="w-8 h-8 bg-gray-100 text-gray-600 rounded-full flex items-center justify-center hover:bg-gray-200 transition"><i class="fas fa-eye text-sm"></i></a></div>
        </header>

        <div class="p-4 md:p-8 max-w-4xl mx-auto w-full pb-10">
            
            <div class="ad-banner-320">
                <span class="text-[9px] font-bold uppercase text-gray-400 mb-1">Sponsored</span>
                <script> atOptions = { 'key' : 'cb24b5c155630706146a8df001ea0cab', 'format' : 'iframe', 'height' : 50, 'width' : 320, 'params' : {} }; </script>
                <script src="https://www.highperformanceformat.com/cb24b5c155630706146a8df001ea0cab/invoke.js"></script>
            </div>

            <?= $msg ?>
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-[#e1e3e5] mb-8">
                <h3 class="font-black text-lg mb-4 text-[#202223] border-b border-[#e1e3e5] pb-2">Create Discount Code</h3>
                <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div><label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1.5">Code (e.g. SAVE20)</label><input type="text" name="code" required class="shopify-input uppercase"></div>
                    <div><label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1.5">Type</label><select name="discount_type" class="shopify-input"><option value="percentage">Percentage (%)</option><option value="fixed">Fixed Amount (₹)</option></select></div>
                    <div><label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1.5">Value</label><input type="number" name="discount_value" required class="shopify-input"></div>
                    <div><label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1.5">Min Order Amt (₹)</label><input type="number" name="min_amount" value="0" class="shopify-input"></div>
                    <div class="md:col-span-2"><button type="submit" name="add_coupon" class="w-full bg-white text-black py-3 rounded-xl font-bold uppercase tracking-widest">Create Coupon</button></div>
                </form>
            </div>

            <div class="space-y-4">
                <?php foreach($coupons as $c): ?>
                <div class="bg-white p-4 rounded-xl border border-[#e1e3e5] shadow-sm flex items-center justify-between">
                    <div>
                        <h4 class="font-black text-indigo-600 text-lg uppercase"><?= htmlspecialchars($c['code']) ?></h4>
                        <p class="text-xs text-gray-500 font-bold">Discount: <?= $c['discount_value'] ?><?= $c['discount_type']=='percentage'?'%':'₹' ?> | Min: ₹<?= $c['min_amount'] ?></p>
                    </div>
                    <a href="?delete=<?= $c['id'] ?>" class="text-red-500 hover:bg-red-50 p-2 rounded"><i class="fas fa-trash"></i></a>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="ad-banner-320 mt-8">
                <span class="text-[9px] font-bold uppercase text-gray-400 mb-1">Sponsored</span>
                <script> atOptions = { 'key' : '9d431c6145c1285c7ca61a32a79dbdfe', 'format' : 'iframe', 'height' : 50, 'width' : 320, 'params' : {} }; </script>
                <script src="https://www.highperformanceformat.com/9d431c6145c1285c7ca61a32a79dbdfe/invoke.js"></script>
            </div>
        </div>
    </main>
</div>
<script>function toggleSidebar() { document.getElementById('appSidebar').classList.toggle('open'); document.getElementById('sidebarOverlay').classList.toggle('open'); }</script>
<?php require '../common/bottom.php'; ?>
