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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_branding'])) {
    $announcement_raw = trim($_POST['announcement_text']);
    $announcement_json = !empty($announcement_raw) ? json_encode([$announcement_raw]) : NULL;

    $logo_name = $seller['logo_image'] ?? '';
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $logo_name = 'logo_' . time() . '_' . uniqid() . '.' . pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        move_uploaded_file($_FILES['logo']['tmp_name'], '../uploads/' . $logo_name);
    }

    $banner_name = $seller['banner_image'] ?? '';
    if (isset($_FILES['banner']) && $_FILES['banner']['error'] == 0) {
        $banner_name = 'banner_' . time() . '_' . uniqid() . '.' . pathinfo($_FILES['banner']['name'], PATHINFO_EXTENSION);
        move_uploaded_file($_FILES['banner']['tmp_name'], '../uploads/' . $banner_name);
    }

    $stmt = $pdo->prepare("UPDATE sellers SET logo_image=?, banner_image=?, announcement_text=? WHERE id=?");
    if ($stmt->execute([$logo_name, $banner_name, $announcement_json, $seller_id])) {
        $msg = "<div class='bg-green-50 text-green-700 p-4 rounded-xl border border-green-200 mb-6 font-bold flex items-center shadow-sm'><i class='fas fa-paint-brush text-xl mr-3'></i> Branding updated successfully!</div>";
        $seller['logo_image'] = $logo_name; $seller['banner_image'] = $banner_name; $seller['announcement_text'] = $announcement_json;
    }
}

$current_announcement = '';
if (!empty($seller['announcement_text'])) { $arr = json_decode($seller['announcement_text'], true); if(is_array($arr) && isset($arr[0])) $current_announcement = $arr[0]; }

require '../common/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <title>Store Branding - Dukaanova</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&display=swap" rel="stylesheet">
    <style> 
        /* 🔥 MAGIC SCROLL FIX APPLIED HERE TOO 🔥 */
        html, body { background-color: #f4f6f8; margin: 0; padding: 0; overflow-x: hidden; -webkit-overflow-scrolling: touch; font-family: 'Inter', sans-serif; }
        
        .app-container { display: flex; min-height: 100vh; width: 100%; }
        
        .sidebar { background-color: #1a1a1a; color: #a1a1aa; width: 260px; flex-shrink: 0; transition: transform 0.3s ease; z-index: 60; display: flex; flex-direction: column; position: sticky; top: 0; height: 100vh; overflow-y: auto; }
        .nav-item { display: flex; align-items: center; padding: 12px 20px; color: #a1a1aa; text-decoration: none; font-size: 14px; font-weight: 600; transition: all 0.2s; border-left: 3px solid transparent; }
        .nav-item:hover, .nav-item.active { background-color: #27272a; color: #ffffff; border-left-color: #10b981; }
        .nav-item i { width: 24px; font-size: 16px; }
        
        .main-content { flex-grow: 1; display: flex; flex-direction: column; min-height: 100vh; background: #f4f6f8; width: calc(100% - 260px); }
        
        .shopify-input { background-color: #f9fafb; border: 1px solid #e1e3e5; border-radius: 8px; padding: 12px; width: 100%; font-size: 14px; font-weight: 600; outline: none; }
        .shopify-input:focus { border-color: #000; box-shadow: 0 0 0 1px #000; }

        /* 🔥 AD CONTAINERS 🔥 */
        .ad-top { width: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 70px; overflow: hidden; margin-bottom: 20px; }
        .ad-native { width: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; overflow: hidden; margin: 24px 0; background: white; border-radius: 12px; padding: 10px; border: 1px solid #e1e3e5; }

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
</head>
<body class="antialiased">

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
            <a href="dashboard.php" class="nav-item"><i class="fas fa-home"></i> Home</a>
            <a href="orders.php" class="nav-item"><i class="fas fa-inbox"></i> Orders</a>
            <a href="products.php" class="nav-item"><i class="fas fa-tags"></i> Products</a>
            <a href="customers.php" class="nav-item"><i class="fa-solid fa-users"></i> Customers</a>
            <div class="px-5 py-3 mt-4 mb-1 text-[10px] font-bold text-gray-500 uppercase tracking-widest">Storefront</div>
            <a href="themes.php" class="nav-item"><i class="fas fa-palette"></i> Theme Store</a>
            <a href="branding.php" class="nav-item active"><i class="fas fa-paint-roller"></i> Branding</a>
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
            <div class="flex items-center"><button onclick="toggleSidebar()" class="md:hidden mr-4 text-gray-600 hover:text-black text-xl"><i class="fas fa-bars"></i></button><h1 class="text-xl font-black text-[#202223] tracking-tight">Store Branding</h1></div>
            <a href="../store/store.php?name=<?= $seller['store_slug'] ?>" target="_blank" class="bg-white border border-[#e1e3e5] text-[#202223] px-4 py-2 rounded-lg text-xs font-bold shadow-sm hover:bg-gray-50 transition"><i class="fas fa-eye mr-2"></i> View Live Store</a>
        </header>

        <div class="p-4 md:p-8 max-w-4xl mx-auto w-full pb-10">
            
            <div class="ad-top">
                <span class="text-[9px] font-bold uppercase text-gray-400 mb-1">Advertisement</span>
                <script>
                  atOptions = { 'key' : 'cb24b5c155630706146a8df001ea0cab', 'format' : 'iframe', 'height' : 50, 'width' : 320, 'params' : {} };
                </script>
                <script src="https://www.highperformanceformat.com/cb24b5c155630706146a8df001ea0cab/invoke.js"></script>
            </div>

            <?= $msg ?>

            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                
                <div class="bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-[#e1e3e5]">
                    <h3 class="font-black text-lg mb-4 text-[#202223] border-b border-[#e1e3e5] pb-2">Visual Assets</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1.5">Store Logo</label>
                            <?php if(!empty($seller['logo_image'])): ?><div class="mb-3 w-16 h-16 rounded-xl overflow-hidden border border-gray-200"><img src="../uploads/<?= $seller['logo_image'] ?>" class="w-full h-full object-cover"></div><?php endif; ?>
                            <div class="border-2 border-dashed border-[#c9cccf] rounded-xl p-4 text-center bg-[#f9fafb] hover:bg-gray-50 transition cursor-pointer relative"><input type="file" name="logo" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"><i class="fas fa-camera text-xl text-gray-400 mb-1"></i><p class="text-xs font-bold text-[#202223]">Upload Logo</p></div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1.5">Cover Banner</label>
                            <?php if(!empty($seller['banner_image'])): ?><div class="mb-3 w-full h-16 rounded-xl overflow-hidden border border-gray-200"><img src="../uploads/<?= $seller['banner_image'] ?>" class="w-full h-full object-cover"></div><?php endif; ?>
                            <div class="border-2 border-dashed border-[#c9cccf] rounded-xl p-4 text-center bg-[#f9fafb] hover:bg-gray-50 transition cursor-pointer relative"><input type="file" name="banner" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"><i class="fas fa-panorama text-xl text-gray-400 mb-1"></i><p class="text-xs font-bold text-[#202223]">Upload Banner</p></div>
                        </div>
                    </div>
                </div>
<script>
  atOptions = {
    'key' : '1fdfc7843883917113520f34d9ebd11d',
    'format' : 'iframe',
    'height' : 250,
    'width' : 300,
    'params' : {}
  };
</script>
<script src="https://www.highperformanceformat.com/1fdfc7843883917113520f34d9ebd11d/invoke.js"></script>
                <div class="bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-[#e1e3e5]">
                    <h3 class="font-black text-lg mb-4 text-[#202223] border-b border-[#e1e3e5] pb-2">Announcement Bar</h3>
                    <div>
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1.5">Scrolling Text</label>
                        <input type="text" name="announcement_text" value="<?= htmlspecialchars($current_announcement) ?>" placeholder="e.g. 🔥 FLAT 50% OFF TODAY! 🔥" class="shopify-input">
                        <p class="text-[10px] text-gray-400 mt-1 font-bold">This scrolling text appears at the top of your website.</p>
                    </div>
                </div>

                <button type="submit" name="save_branding" class="w-full bg-[#ffffff] hover:bg-[#333333] text-white py-4 rounded-xl text-sm font-black uppercase tracking-widest transition shadow-lg">Save Branding <i class="fas fa-save ml-2"></i></button>
            </form>
        </div>
    </main>
</div>

<script>
    function toggleSidebar() { document.getElementById('appSidebar').classList.toggle('open'); document.getElementById('sidebarOverlay').classList.toggle('open'); }
</script>

<?php require '../common/bottom.php'; ?>
