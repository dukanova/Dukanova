<?php
require '../common/config.php';
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }

$user_id = $_SESSION['user_id'];

// 🔥 AUTO-HEALING: ADD UPI_QR COLUMN 🔥
try {
    $pdo->exec("ALTER TABLE sellers ADD COLUMN IF NOT EXISTS upi_qr VARCHAR(255) DEFAULT NULL");
} catch(Exception $e) {}

$stmt = $pdo->prepare("SELECT * FROM sellers WHERE user_id = ?");
$stmt->execute([$user_id]);
$seller = $stmt->fetch();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_profile'])) {
    $store_name = trim($_POST['store_name']);
    $description = trim($_POST['description']);
    $upi_id = trim($_POST['upi_id']);
    
    // 🔥 UNIQUE SLUG GENERATOR (Prevents Store Duplication/Collision) 🔥
    $base_slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $store_name), '-'));
    $store_slug = $base_slug;

    // Check if this slug is already taken by ANOTHER seller
    $slug_check = $pdo->prepare("SELECT id FROM sellers WHERE store_slug = ? AND user_id != ?");
    $slug_check->execute([$store_slug, $user_id]);
    
    // If taken, append a random 4-digit number to make it unique (e.g. tobakart-4829)
    if ($slug_check->fetch()) {
        $store_slug = $base_slug . '-' . rand(1000, 9999);
    }

    // UPI QR UPLOAD LOGIC
    $upi_qr_name = $seller['upi_qr'] ?? NULL;
    if (isset($_FILES['upi_qr']) && $_FILES['upi_qr']['error'] == 0) {
        $upi_qr_name = 'qr_' . time() . '_' . uniqid() . '.' . pathinfo($_FILES['upi_qr']['name'], PATHINFO_EXTENSION);
        move_uploaded_file($_FILES['upi_qr']['tmp_name'], '../uploads/' . $upi_qr_name);
    }

    try {
        if ($seller) {
            $stmt = $pdo->prepare("UPDATE sellers SET store_name=?, store_slug=?, description=?, upi_id=?, upi_qr=? WHERE user_id=?");
            $stmt->execute([$store_name, $store_slug, $description, $upi_id, $upi_qr_name, $user_id]);
            $msg = "<div class='bg-green-50 text-green-700 p-4 rounded-xl border border-green-200 mb-6 font-bold flex items-center shadow-sm'><i class='fas fa-check-circle text-xl mr-3'></i> Settings updated!</div>";
        } else {
            $stmt = $pdo->prepare("INSERT INTO sellers (user_id, store_name, store_slug, description, upi_id, upi_qr) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $store_name, $store_slug, $description, $upi_id, $upi_qr_name]);
            $msg = "<div class='bg-green-50 text-green-700 p-4 rounded-xl border border-green-200 mb-6 font-bold flex items-center shadow-sm'><i class='fas fa-rocket text-xl mr-3'></i> Store created!</div>";
        }
        // Refresh seller data
        $stmt = $pdo->prepare("SELECT * FROM sellers WHERE user_id = ?"); $stmt->execute([$user_id]); $seller = $stmt->fetch();
        if(isset($_GET['setup']) && $_GET['setup'] == '1') { header("Location: dashboard.php"); exit; }
    } catch(PDOException $e) {
        $msg = "<div class='bg-red-50 text-red-600 p-4 rounded-xl border border-red-200 mb-6 font-bold flex items-center shadow-sm'><i class='fas fa-times-circle text-xl mr-3'></i> Error saving. Please try again.</div>";
    }
}

$is_new_setup = !$seller;

// GENERATE DYNAMIC STORE LINK
$store_url = '';
if (!$is_new_setup) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $base_dir = str_replace('/seller/store_profile.php', '', $_SERVER['SCRIPT_NAME']);
    $store_url = $protocol . "://" . $_SERVER['HTTP_HOST'] . $base_dir . "/store/store.php?name=" . urlencode($seller['store_slug']);
}

if(!$is_new_setup) { require '../common/header.php'; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <title><?= $is_new_setup ? 'Setup Store' : 'General Settings' ?> - Dukaanova</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&display=swap" rel="stylesheet">
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
        <?php if($is_new_setup): ?> .sidebar, header { display: none !important; } .main-content { width: 100%; justify-content: center; align-items: center; padding: 20px; } <?php endif; ?>
        @media (max-width: 768px) { <?php if(!$is_new_setup): ?> .app-container { display: block; } .main-content { width: 100%; padding-bottom: 70px; min-height: 100vh; } .sidebar { position: fixed; left: 0; transform: translateX(-100%); } .sidebar.open { transform: translateX(0); } .overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 50; } .overlay.open { display: block; } <?php endif; ?> }
    </style>
</head>
<body class="antialiased">

<div class="app-container">
    <?php if(!$is_new_setup): ?>
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
            <a href="customers.php" class="nav-item"><i class="fas fa-users"></i> Customers</a>
            
            <div class="px-5 py-3 mt-4 mb-1 text-[10px] font-bold text-gray-500 uppercase tracking-widest">Storefront</div>
            <a href="themes.php" class="nav-item"><i class="fas fa-palette"></i> Theme Store</a>
            <a href="branding.php" class="nav-item"><i class="fas fa-paint-roller"></i> Branding</a>
            <a href="coupons.php" class="nav-item"><i class="fas fa-ticket-alt"></i> Coupons</a>
            <div class="px-5 py-3 mt-4 mb-1 text-[10px] font-bold text-gray-500 uppercase tracking-widest">Settings</div>
            <a href="policies.php" class="nav-item"><i class="fas fa-balance-scale"></i> Legal Policies</a>
            <a href="store_profile.php" class="nav-item active"><i class="fas fa-cog"></i> General Settings</a>
        </nav>
    </aside>
    <?php endif; ?>

    <main class="main-content">
        <?php if(!$is_new_setup): ?>
        <header class="bg-white border-b border-[#e1e3e5] px-4 md:px-8 py-4 flex items-center justify-between sticky top-0 z-30 shadow-sm">
            <div class="flex items-center"><button onclick="toggleSidebar()" class="md:hidden mr-4 text-gray-600 hover:text-black text-xl"><i class="fas fa-bars"></i></button><h1 class="text-xl font-black text-[#202223] tracking-tight">General Settings</h1></div>
            <div class="flex items-center space-x-3"><a href="<?= $store_url ?>" target="_blank" class="w-8 h-8 bg-gray-100 text-gray-600 rounded-full flex items-center justify-center hover:bg-gray-200 transition"><i class="fas fa-eye text-sm"></i></a></div>
        </header>
        <?php endif; ?>

        <div class="p-4 md:p-8 max-w-4xl mx-auto w-full pb-10">
            
            <?php if($is_new_setup): ?>
                <div class="text-center mb-8">
                    <div class="w-16 h-16 bg-black text-white rounded-2xl flex items-center justify-center text-3xl font-black mx-auto mb-4 shadow-xl"><i class="fas fa-store"></i></div>
                    <h1 class="text-3xl font-black text-[#202223] tracking-tight">Set up your store</h1>
                    <p class="text-sm text-gray-500 font-medium mt-2">Just a few details to get your business online.</p>
                </div>
            <?php else: ?>
                <div class="ad-banner-320">
                    <span class="text-[9px] font-bold uppercase text-gray-400 mb-1">Sponsored</span>
                    <script> atOptions = { 'key' : '9d431c6145c1285c7ca61a32a79dbdfe', 'format' : 'iframe', 'height' : 50, 'width' : 320, 'params' : {} }; </script>
                    <script src="https://www.highperformanceformat.com/9d431c6145c1285c7ca61a32a79dbdfe/invoke.js"></script>
                </div>

                <div class="bg-indigo-50 border border-indigo-200 p-6 rounded-2xl shadow-sm mb-6 flex flex-col md:flex-row items-center justify-between">
                    <div class="mb-4 md:mb-0 w-full md:w-auto text-center md:text-left">
                        <h3 class="font-black text-indigo-900 text-lg mb-1"><i class="fas fa-link mr-2"></i> Your Store Link</h3>
                        <p class="text-xs text-indigo-700 font-medium">Share this link on Instagram/WhatsApp to get orders.</p>
                    </div>
                    <div class="flex w-full md:w-auto shadow-sm rounded-lg overflow-hidden">
                        <input type="text" readonly value="<?= $store_url ?>" id="storeLinkInput" class="bg-white border border-indigo-200 border-r-0 px-3 py-2.5 text-xs font-mono text-indigo-900 w-full md:w-64 outline-none">
                        <button type="button" onclick="copyStoreLink()" id="copyBtn" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 font-black text-xs uppercase tracking-widest transition shrink-0">
                            Copy
                        </button>
                    </div>
                </div>
            <?php endif; ?>

            <?= $msg ?>

            <form method="POST" enctype="multipart/form-data" action="<?= $is_new_setup ? '?setup=1' : '' ?>" class="space-y-6">
                <div class="bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-[#e1e3e5]">
                    <h3 class="font-black text-lg mb-4 text-[#202223] border-b border-[#e1e3e5] pb-2">Store Details</h3>
                    <div class="space-y-4">
                        <div><label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1.5">Store Name</label><input type="text" name="store_name" required value="<?= htmlspecialchars($seller['store_name'] ?? '') ?>" class="shopify-input"></div>
                        <div><label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1.5">Short Description</label><textarea name="description" rows="2" class="shopify-input"><?= htmlspecialchars($seller['description'] ?? '') ?></textarea></div>
                    </div>
                </div>

                <div class="bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-[#e1e3e5] relative overflow-hidden">
                    <div class="absolute top-0 right-0 bg-green-500 text-white text-[10px] font-black uppercase tracking-widest px-3 py-1 rounded-bl-lg shadow-sm">Critical</div>
                    <h3 class="font-black text-lg mb-4 text-[#202223] border-b border-[#e1e3e5] pb-2 flex items-center"><i class="fas fa-wallet text-indigo-500 mr-2"></i> Payment Settings</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1.5">Your UPI ID</label>
                            <input type="text" name="upi_id" required value="<?= htmlspecialchars($seller['upi_id'] ?? '') ?>" placeholder="e.g. 9876543210@ybl" class="shopify-input font-mono text-indigo-700 bg-indigo-50 border-indigo-200">
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1.5">Upload Custom QR Code (Optional)</label>
                            <div class="flex items-center space-x-4">
                                <?php if(!empty($seller['upi_qr'])): ?>
                                    <div class="w-16 h-16 bg-gray-50 rounded-lg border border-gray-200 overflow-hidden shrink-0">
                                        <img src="../uploads/<?= $seller['upi_qr'] ?>" class="w-full h-full object-cover">
                                    </div>
                                <?php endif; ?>
                                <input type="file" name="upi_qr" accept="image/*" class="shopify-input py-2.5 px-3 bg-white cursor-pointer text-xs">
                            </div>
                            <p class="text-[10px] text-gray-400 mt-1.5 font-bold">If uploaded, customers will see your exact PayTM/PhonePe QR.</p>
                        </div>
                    </div>
                </div>

                <?php if(!$is_new_setup): ?>
                    <div class="ad-banner-320 mb-6">
                        <span class="text-[9px] font-bold uppercase text-gray-400 mb-1">Sponsored</span>
                        <script> atOptions = { 'key' : 'c84ae23d36c8befc3df5f872bb6555ce', 'format' : 'iframe', 'height' : 50, 'width' : 320, 'params' : {} }; </script>
                        <script src="https://www.highperformanceformat.com/c84ae23d36c8befc3df5f872bb6555ce/invoke.js"></script>
                    </div>
                <?php endif; ?>

                <button type="submit" name="save_profile" class="w-full bg-[#ffffff] hover:bg-[#333333] text-white py-4 rounded-xl text-sm font-black uppercase tracking-widest transition shadow-lg">
                    <?= $is_new_setup ? 'Launch Store 🚀' : 'Save General Settings <i class="fas fa-save ml-2"></i>' ?>
                </button>
            </form>
        </div>
    </main>
</div>

<script>
    function toggleSidebar() { 
        document.getElementById('appSidebar').classList.toggle('open'); 
        document.getElementById('sidebarOverlay').classList.toggle('open'); 
    }

    function copyStoreLink() {
        var copyText = document.getElementById("storeLinkInput");
        var btn = document.getElementById("copyBtn");
        
        copyText.select();
        copyText.setSelectionRange(0, 99999);
        
        try {
            document.execCommand("copy");
            btn.innerHTML = "<i class='fas fa-check'></i> Copied";
            btn.classList.remove("bg-indigo-600", "hover:bg-indigo-700");
            btn.classList.add("bg-green-600", "hover:bg-green-700");
            
            setTimeout(function() {
                btn.innerHTML = "Copy";
                btn.classList.remove("bg-green-600", "hover:bg-green-700");
                btn.classList.add("bg-indigo-600", "hover:bg-indigo-700");
            }, 2000);
        } catch (err) {
            alert("Oops! Your browser doesn't support direct copying.");
        }
    }
</script>

<?php if(!$is_new_setup) { require '../common/bottom.php'; } ?>
</body>
</html>
