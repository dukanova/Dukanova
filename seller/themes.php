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

// Handle Theme Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['apply_theme'])) {
    $new_theme = strtolower(trim($_POST['theme_name']));
    $stmt = $pdo->prepare("UPDATE sellers SET theme = ? WHERE id = ?");
    if ($stmt->execute([$new_theme, $seller_id])) {
        $seller['theme'] = $new_theme; // Update local variable instantly
        $msg = "<div class='bg-green-50 text-green-700 p-4 rounded-xl border border-green-200 mb-6 font-bold flex items-center shadow-sm'><i class='fas fa-check-circle text-xl mr-3'></i> Theme updated successfully! Check your store.</div>";
    }
}

$current_theme = strtolower(trim($seller['theme'] ?? 'dawn'));

// 🔥 DEFINING THEMES WITH CSS GRADIENTS (NO IMAGES NEEDED!) 🔥
$available_themes = [
    'dawn' => ['name' => 'Dawn Classic', 'desc' => 'Clean, minimalist white design for a premium feel.', 'icon' => 'fa-sun', 'grad' => 'from-gray-100 to-gray-300', 'icon_color' => 'text-gray-600'],
    'ocean' => ['name' => 'Ocean Wave', 'desc' => 'Cool blue tones for a calm and trustworthy vibe.', 'icon' => 'fa-water', 'grad' => 'from-blue-400 to-cyan-300', 'icon_color' => 'text-white'],
    'sunset' => ['name' => 'Golden Sunset', 'desc' => 'Warm orange and yellow hues for a vibrant look.', 'icon' => 'fa-cloud-sun', 'grad' => 'from-orange-400 to-yellow-400', 'icon_color' => 'text-white'],
    'pastel' => ['name' => 'Soft Pastel', 'desc' => 'Elegant pink and soft colors. Perfect for boutiques.', 'icon' => 'fa-candy-cane', 'grad' => 'from-pink-300 to-rose-200', 'icon_color' => 'text-white'],
    'vintage' => ['name' => 'Retro Vintage', 'desc' => 'Classic brown and beige aesthetic. Centered layout.', 'icon' => 'fa-camera-retro', 'grad' => 'from-yellow-700 to-yellow-900', 'icon_color' => 'text-white'],
    'midnight' => ['name' => 'Midnight Dark', 'desc' => 'Deep blue and black for a sleek night mode.', 'icon' => 'fa-moon', 'grad' => 'from-gray-900 to-blue-900', 'icon_color' => 'text-blue-300'],
    'cyber' => ['name' => 'Cyber Hacker', 'desc' => 'Tech-heavy neon green on black. Left Sidebar layout.', 'icon' => 'fa-terminal', 'grad' => 'from-black to-green-900', 'icon_color' => 'text-green-500'],
    'street' => ['name' => 'Brutalist Street', 'desc' => 'Aggressive borders, big fonts & hard shadows.', 'icon' => 'fa-city', 'grad' => 'from-gray-800 to-black', 'icon_color' => 'text-gray-400'],
    'neon' => ['name' => 'Neon Synth', 'desc' => 'Cyberpunk purple and teal lights. Left Sidebar layout.', 'icon' => 'fa-bolt', 'grad' => 'from-purple-900 to-indigo-900', 'icon_color' => 'text-fuchsia-400']
];

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
            <a href="products.php" class="nav-item"><i class="fas fa-tags"></i> Products</a>
            <a href="customers.php" class="nav-item"><i class="fa-solid fa-users"></i> Customers</a>
            <div class="px-5 py-3 mt-4 mb-1 text-[10px] font-bold text-gray-500 uppercase tracking-widest">Storefront</div>
            <a href="themes.php" class="nav-item active"><i class="fas fa-palette"></i> Theme Store</a>
            <a href="branding.php" class="nav-item"><i class="fas fa-paint-roller"></i> Branding</a>
            <a href="coupons.php" class="nav-item"><i class="fas fa-ticket-alt"></i> Coupons</a>
            <div class="px-5 py-3 mt-4 mb-1 text-[10px] font-bold text-gray-500 uppercase tracking-widest">Settings</div>
            <a href="policies.php" class="nav-item"><i class="fas fa-balance-scale"></i> Legal Policies</a>
            <a href="store_profile.php" class="nav-item"><i class="fas fa-cog"></i> General Settings</a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="bg-white border-b border-[#e1e3e5] px-4 md:px-8 py-4 flex items-center justify-between sticky top-0 z-30 shadow-sm">
            <div class="flex items-center"><button onclick="toggleSidebar()" class="md:hidden mr-4 text-gray-600 hover:text-black text-xl"><i class="fas fa-bars"></i></button><h1 class="text-xl font-black text-[#202223] tracking-tight">Theme Store</h1></div>
            <div class="flex items-center space-x-3"><a href="../store/store.php?name=<?= $seller['store_slug'] ?>" target="_blank" class="w-8 h-8 bg-gray-100 text-gray-600 rounded-full flex items-center justify-center hover:bg-gray-200 transition"><i class="fas fa-eye text-sm"></i></a></div>
        </header>

        <div class="p-4 md:p-8 max-w-5xl mx-auto w-full pb-10">
            
            <div class="ad-banner-320">
                <span class="text-[9px] font-bold uppercase text-gray-400 mb-1">Sponsored</span>
                <script> atOptions = { 'key' : 'cb24b5c155630706146a8df001ea0cab', 'format' : 'iframe', 'height' : 50, 'width' : 320, 'params' : {} }; </script>
                <script src="https://www.highperformanceformat.com/cb24b5c155630706146a8df001ea0cab/invoke.js"></script>
            </div>

            <?= $msg ?>

            <div class="mb-8">
                <h2 class="text-2xl font-black text-[#202223] tracking-tight">Customize Your Storefront</h2>
                <p class="text-sm text-gray-500 font-medium mt-1">Select a theme layout that matches your brand identity. All themes are 100% free.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach($available_themes as $key => $theme): ?>
                <div class="bg-white rounded-2xl shadow-sm border <?= $current_theme === $key ? 'border-green-500 ring-2 ring-green-500/20' : 'border-[#e1e3e5]' ?> overflow-hidden flex flex-col transition hover:shadow-md">
                    
                    <div class="h-40 w-full bg-gradient-to-br <?= $theme['grad'] ?> flex flex-col items-center justify-center relative">
                        <i class="fas <?= $theme['icon'] ?> text-5xl <?= $theme['icon_color'] ?> opacity-80 mb-2"></i>
                        <span class="text-xs font-black uppercase tracking-widest <?= $theme['icon_color'] ?> opacity-90"><?= $theme['name'] ?></span>
                        
                        <?php if($current_theme === $key): ?>
                            <div class="absolute top-3 right-3 bg-green-500 text-white text-[9px] font-black uppercase tracking-widest px-2 py-1 rounded shadow-sm flex items-center">
                                <i class="fas fa-check-circle mr-1"></i> Active
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="p-6 flex flex-col flex-grow justify-between">
                        <div>
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="text-lg font-black text-[#202223]"><?= $theme['name'] ?></h3>
                                <span class="bg-green-50 text-green-700 border border-green-200 text-[9px] font-black uppercase tracking-widest px-2 py-1 rounded">Unlocked</span>
                            </div>
                            <p class="text-xs text-gray-500 font-medium leading-relaxed mb-6"><?= $theme['desc'] ?></p>
                        </div>
                        
                        <?php if($current_theme === $key): ?>
                            <button disabled class="w-full bg-green-50 text-green-700 border border-green-200 py-3 rounded-xl text-xs font-black uppercase tracking-widest flex items-center justify-center cursor-not-allowed">
                                <i class="fas fa-check-circle mr-2"></i> Active Theme
                            </button>
                        <?php else: ?>
                            <form method="POST">
                                <input type="hidden" name="theme_name" value="<?= $key ?>">
                                <button type="submit" name="apply_theme" class="w-full bg-white hover:bg-gray-800 text-white py-3 rounded-xl text-xs font-black uppercase tracking-widest transition shadow-sm flex items-center justify-center">
                                    Apply Theme
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="ad-banner-320 mt-10">
                <span class="text-[9px] font-bold uppercase text-gray-400 mb-1">Sponsored</span>
                <script> atOptions = { 'key' : '112d765d1f534434ab043c564c721dab', 'format' : 'iframe', 'height' : 50, 'width' : 320, 'params' : {} }; </script>
                <script src="https://www.highperformanceformat.com/112d765d1f534434ab043c564c721dab/invoke.js"></script>
            </div>
        </div>
    </main>
</div>

<script>function toggleSidebar() { document.getElementById('appSidebar').classList.toggle('open'); document.getElementById('sidebarOverlay').classList.toggle('open'); }</script>
<?php require '../common/bottom.php'; ?>
