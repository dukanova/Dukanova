<?php
require '../common/config.php';
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM sellers WHERE user_id = ?");
$stmt->execute([$user_id]);
$seller = $stmt->fetch();
if (!$seller) { header("Location: store_profile.php"); exit; }
$seller_id = $seller['id'];

if (isset($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $pdo->query("DELETE FROM products WHERE id = $del_id AND seller_id = $seller_id");
    header("Location: products.php"); exit;
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE seller_id = ? ORDER BY id DESC");
$stmt->execute([$seller_id]);
$products = $stmt->fetchAll();

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
            <a href="products.php" class="nav-item active"><i class="fas fa-tags"></i> Products</a>
            <a href="customers.php" class="nav-item"><i class="fa-solid fa-users"></i> Customers</a>
            <div class="px-5 py-3 mt-4 mb-1 text-[10px] font-bold text-gray-500 uppercase tracking-widest">Storefront</div>
            <a href="themes.php" class="nav-item"><i class="fas fa-palette"></i> Theme Store</a>
            <a href="branding.php" class="nav-item"><i class="fas fa-paint-roller"></i> Branding</a>
            <a href="coupons.php" class="nav-item"><i class="fas fa-ticket-alt"></i> Coupons</a>
            <div class="px-5 py-3 mt-4 mb-1 text-[10px] font-bold text-gray-500 uppercase tracking-widest">Settings</div>
            <a href="policies.php" class="nav-item"><i class="fas fa-balance-scale"></i> Legal Policies</a>
            <a href="store_profile.php" class="nav-item"><i class="fas fa-cog"></i> General Settings</a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="bg-white border-b border-[#e1e3e5] px-4 md:px-8 py-4 flex items-center justify-between sticky top-0 z-30 shadow-sm">
            <div class="flex items-center"><button onclick="toggleSidebar()" class="md:hidden mr-4 text-gray-600 hover:text-black text-xl"><i class="fas fa-bars"></i></button><h1 class="text-xl font-black text-[#202223] tracking-tight">Products</h1></div>
            <div class="flex items-center space-x-3"><a href="../store/store.php?name=<?= $seller['store_slug'] ?>" target="_blank" class="w-8 h-8 bg-gray-100 text-gray-600 rounded-full flex items-center justify-center hover:bg-gray-200 transition"><i class="fas fa-eye text-sm"></i></a></div>
        </header>

        <div class="p-4 md:p-8 max-w-5xl mx-auto w-full pb-10">
            
            <div class="flex justify-between items-center mb-6">
                <p class="text-sm font-bold text-gray-500"><?= count($products) ?> items</p>
                <a href="add_product.php" class="bg-white hover:bg-gray-800 text-white px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-widest transition shadow-md"><i class="fas fa-plus mr-2"></i> Add Product</a>
            </div>

            <div class="ad-banner-320 mb-8">
                <span class="text-[9px] font-bold uppercase text-gray-400 mb-1">Sponsored</span>
                <script> atOptions = { 'key' : '0eeab6e57a7d80de7ddfffef93701468', 'format' : 'iframe', 'height' : 50, 'width' : 320, 'params' : {} }; </script> 
                <script src="https://www.highperformanceformat.com/0eeab6e57a7d80de7ddfffef93701468/invoke.js"></script>
            </div>

            <?php if(empty($products)): ?>
                <div class="bg-white p-12 rounded-2xl shadow-sm border border-[#e1e3e5] text-center">
                    <div class="w-20 h-20 bg-gray-50 border border-[#e1e3e5] rounded-full flex items-center justify-center mx-auto mb-4 text-gray-300"><i class="fas fa-box-open text-3xl"></i></div>
                    <h3 class="text-xl font-black text-[#202223] mb-2">Your catalog is empty</h3>
                    <p class="text-sm text-gray-500 font-medium mb-6">Add products to start selling to your customers.</p>
                    <a href="add_product.php" class="bg-black text-white px-6 py-3 rounded-xl text-sm font-black uppercase tracking-widest inline-block">Add First Product</a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach($products as $p): ?>
                    <div class="bg-white p-4 rounded-xl border border-[#e1e3e5] shadow-sm flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="w-16 h-16 bg-gray-100 rounded-lg overflow-hidden shrink-0 border border-gray-200">
                                <?php if($p['image']): ?><img src="../uploads/<?= $p['image'] ?>" class="w-full h-full object-cover"><?php else: ?><i class="fas fa-image text-gray-400 text-2xl flex items-center justify-center h-full"></i><?php endif; ?>
                            </div>
                            <div>
                                <h4 class="font-bold text-[#202223] text-sm"><?= htmlspecialchars($p['name']) ?></h4>
                                <p class="text-xs text-gray-500 font-bold uppercase mt-1">₹<?= number_format($p['price'], 2) ?> • <?= $p['stock'] ?> in stock</p>
                            </div>
                        </div>
                        <a href="javascript:void(0)" onclick="openDeleteModal(<?= $p['id'] ?>)" class="w-8 h-8 flex items-center justify-center bg-red-50 text-red-500 hover:bg-red-500 hover:text-white rounded-lg transition"><i class="fas fa-trash text-xs"></i></a>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="ad-banner-320 mt-8">
                <span class="text-[9px] font-bold uppercase text-gray-400 mb-1">Sponsored</span>
                <script> atOptions = { 'key' : '112d765d1f534434ab043c564c721dab', 'format' : 'iframe', 'height' : 50, 'width' : 320, 'params' : {} }; </script>
                <script src="https://www.highperformanceformat.com/112d765d1f534434ab043c564c721dab/invoke.js"></script>
            </div>
        </div>
    </main>
</div>

<div id="deleteModal" class="fixed inset-0 bg-black/60 z-[100] hidden flex-col items-center justify-center p-4 backdrop-blur-sm transition-opacity duration-300">
    <div class="bg-white rounded-3xl p-6 md:p-8 max-w-sm w-full shadow-2xl text-center transform scale-95 transition-transform duration-300" id="deleteModalContent">
        <div class="w-16 h-16 bg-red-50 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4 border-4 border-white shadow-sm -mt-12">
            <i class="fas fa-trash-alt text-2xl"></i>
        </div>
        <h3 class="text-xl font-black text-[#202223] mb-2 tracking-tight">Delete Product?</h3>
        <p class="text-sm text-gray-500 font-medium mb-6 leading-relaxed">
            Are you sure you want to delete this product? This action cannot be undone and it will be removed from your store immediately.
        </p>
        <div class="flex flex-col space-y-3">
            <a href="#" id="confirmDeleteBtn" class="w-full bg-red-600 hover:bg-red-700 text-white py-3.5 rounded-xl text-sm font-black uppercase tracking-widest transition shadow-md">
                Yes, Delete It
            </a>
            <button type="button" onclick="closeDeleteModal()" class="w-full bg-gray-100 hover:bg-gray-200 text-gray-600 py-3.5 rounded-xl text-sm font-bold uppercase tracking-widest transition">
                Cancel
            </button>
        </div>
    </div>
</div>

<script>
    function toggleSidebar() { 
        document.getElementById('appSidebar').classList.toggle('open'); 
        document.getElementById('sidebarOverlay').classList.toggle('open'); 
    }

    // 🔥 MODAL ANIMATION JAVASCRIPT 🔥
    function openDeleteModal(productId) {
        document.getElementById('confirmDeleteBtn').href = '?delete=' + productId;
        const modal = document.getElementById('deleteModal');
        const content = document.getElementById('deleteModalContent');
        
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        
        // Small delay to trigger animation
        setTimeout(() => {
            content.classList.remove('scale-95');
            content.classList.add('scale-100');
        }, 10);
    }

    function closeDeleteModal() {
        const modal = document.getElementById('deleteModal');
        const content = document.getElementById('deleteModalContent');
        
        content.classList.remove('scale-100');
        content.classList.add('scale-95');
        
        // Wait for animation to finish before hiding
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }, 200);
    }
</script>
<?php require '../common/bottom.php'; ?>
