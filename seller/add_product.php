<?php
require '../common/config.php';
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM sellers WHERE user_id = ?");
$stmt->execute([$user_id]);
$seller = $stmt->fetch();
if (!$seller) { header("Location: store_profile.php"); exit; }
$seller_id = $seller['id'];

// 🔥 AUTO-HEALING FOR VARIATIONS COLUMN 🔥
try {
    $pdo->exec("ALTER TABLE products ADD COLUMN IF NOT EXISTS variations TEXT DEFAULT NULL");
} catch(Exception $e) {}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    $product_type = $_POST['product_type'];
    
    // 🔥 NEW: SMART ARRAY-BASED VARIATION PARSER 🔥
    $var_array = [];
    if (isset($_POST['opt_names']) && isset($_POST['opt_values'])) {
        for ($i = 0; $i < count($_POST['opt_names']); $i++) {
            $k = trim($_POST['opt_names'][$i]);
            $v = trim($_POST['opt_values'][$i]);
            
            if (!empty($k) && !empty($v)) {
                // Split by comma, remove extra spaces, and filter empty ones
                $val_array = array_filter(array_map('trim', explode(',', $v)));
                if (count($val_array) > 0) {
                    $var_array[$k] = $val_array;
                }
            }
        }
    }
    $variations_json = !empty($var_array) ? json_encode($var_array) : NULL;

    $image_name = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image_name = 'prod_' . time() . '_' . $_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], '../uploads/' . $image_name);
    }

    $digital_file_name = '';
    if ($product_type == 'digital' && isset($_FILES['digital_file']) && $_FILES['digital_file']['error'] == 0) {
        $digital_file_name = 'file_' . time() . '_' . $_FILES['digital_file']['name'];
        move_uploaded_file($_FILES['digital_file']['tmp_name'], '../uploads/' . $digital_file_name);
    }

    $stmt = $pdo->prepare("INSERT INTO products (seller_id, name, description, price, stock, image, product_type, digital_file, variations) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$seller_id, $name, $description, $price, $stock, $image_name, $product_type, $digital_file_name, $variations_json])) {
        $msg = "<div class='bg-green-50 text-green-700 p-4 rounded-xl border border-green-200 mb-6 font-bold flex items-center shadow-sm'><i class='fas fa-check-circle text-xl mr-3'></i> Product Added Successfully!</div>";
    } else {
        $msg = "<div class='bg-red-50 text-red-600 p-4 rounded-xl border border-red-200 mb-6 font-bold flex items-center shadow-sm'><i class='fas fa-times-circle text-xl mr-3'></i> Failed to add product.</div>";
    }
}

require '../common/header.php';
?>
<style>
    html, body { background-color: #f4f6f8; margin: 0; padding: 0; overflow-x: hidden; font-family: 'Inter', sans-serif; }
    .app-container { display: flex; min-height: 100vh; width: 100%; }
    .sidebar { background-color: #1a1a1a; color: #a1a1aa; width: 260px; flex-shrink: 0; transition: transform 0.3s ease; z-index: 60; display: flex; flex-direction: column; position: sticky; top: 0; height: 100vh; overflow-y: auto; }
    .nav-item { display: flex; align-items: center; padding: 12px 20px; color: #a1a1aa; text-decoration: none; font-size: 14px; font-weight: 600; transition: all 0.2s; border-left: 3px solid transparent; }
    .nav-item:hover, .nav-item.active { background-color: #27272a; color: #ffffff; border-left-color: #10b981; }
    .nav-item i { width: 24px; font-size: 16px; }
    .main-content { flex-grow: 1; display: flex; flex-direction: column; min-height: 100vh; background: #f4f6f8; width: calc(100% - 260px); }
    .shopify-input { background-color: #ffffff; border: 1px solid #e1e3e5; border-radius: 8px; padding: 12px; width: 100%; font-size: 14px; font-weight: 600; outline: none; transition: 0.2s; }
    .shopify-input:focus { border-color: #000000; box-shadow: 0 0 0 1px #000000; }
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
            <a href="customers.php" class="nav-item"><i class="fas fa-users"></i> Customers</a>
            
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
            <div class="flex items-center"><button onclick="toggleSidebar()" class="md:hidden mr-4 text-gray-600 hover:text-black text-xl"><i class="fas fa-bars"></i></button><h1 class="text-xl font-black text-[#202223] tracking-tight">Add Product</h1></div>
            <a href="products.php" class="text-gray-500 hover:text-black text-sm font-bold"><i class="fas fa-arrow-left mr-1"></i> Back</a>
        </header>

        <div class="p-4 md:p-8 max-w-4xl mx-auto w-full pb-10">
            <?= $msg ?>

            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <div class="bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-[#e1e3e5]">
                    <h3 class="font-black text-lg mb-4 text-[#202223] border-b border-[#e1e3e5] pb-2">Basic Details</h3>
                    <div class="space-y-4">
                        <div><label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1.5">Product Name</label><input type="text" name="name" required class="shopify-input" placeholder="e.g. Premium Cotton T-Shirt"></div>
                        <div><label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1.5">Description</label><textarea name="description" required rows="4" class="shopify-input" placeholder="Write a catchy description..."></textarea></div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div><label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1.5">Price (₹)</label><input type="number" step="0.01" name="price" required class="shopify-input" placeholder="999.00"></div>
                            <div><label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1.5">Stock Quantity</label><input type="number" name="stock" required class="shopify-input" placeholder="100"></div>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-[#e1e3e5]">
                    <h3 class="font-black text-lg mb-4 text-[#202223] border-b border-[#e1e3e5] pb-2">Product Type & Media</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1.5">Product Type</label>
                            <select name="product_type" id="product_type" class="shopify-input" onchange="toggleDigitalFile()">
                                <option value="physical">Physical Product (Requires Shipping)</option>
                                <option value="digital">Digital Product (Downloadable PDF/App/Etc.)</option>
                            </select>
                        </div>
                        <div><label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1.5">Product Image</label><input type="file" name="image" accept="image/*" class="shopify-input py-2.5"></div>
                        <div id="digital_file_upload" style="display: none;" class="p-4 bg-indigo-50 border border-indigo-200 rounded-xl">
                            <label class="block text-xs font-black text-indigo-700 uppercase tracking-widest mb-1.5"><i class="fas fa-cloud-upload-alt mr-1"></i> Upload Digital File</label>
                            <input type="file" name="digital_file" class="shopify-input py-2.5 border-indigo-300">
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 md:p-8 rounded-2xl shadow-sm border border-[#e1e3e5]">
                    <div class="flex justify-between items-center border-b border-[#e1e3e5] pb-2 mb-4">
                        <h3 class="font-black text-lg text-[#202223] flex items-center">
                            <i class="fas fa-sliders-h text-indigo-500 mr-2"></i> Options / Variations
                        </h3>
                        <button type="button" onclick="addVariationRow()" class="text-sm font-bold text-indigo-600 hover:text-indigo-800 transition">
                            + Add Option
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 font-medium mb-6">Add options like Size, Color, or Material for customers to select.</p>
                    
                    <div id="variations_container" class="space-y-4">
                        </div>
                </div>

                <button type="submit" name="add_product" class="w-full bg-black hover:bg-gray-800 text-white py-4 rounded-xl text-sm font-black uppercase tracking-widest transition shadow-lg">
                    Save Product <i class="fas fa-save ml-2"></i>
                </button>
            </form>
        </div>
    </main>
</div>

<script>
    function toggleSidebar() { document.getElementById('appSidebar').classList.toggle('open'); document.getElementById('sidebarOverlay').classList.toggle('open'); }
    function toggleDigitalFile() { document.getElementById('digital_file_upload').style.display = document.getElementById('product_type').value === 'digital' ? 'block' : 'none'; }
    
    // 🔥 DYNAMIC VARIATION UI SCRIPT 🔥
    const varContainer = document.getElementById('variations_container');
    
    function addVariationRow() {
        const row = document.createElement('div');
        row.className = "flex flex-col md:flex-row gap-4 bg-gray-50 p-4 rounded-xl border border-gray-200 relative group";
        row.innerHTML = `
            <div class="w-full md:w-1/3">
                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1.5">Option Name</label>
                <input type="text" name="opt_names[]" class="shopify-input text-sm" placeholder="e.g. Size, Color" required>
            </div>
            <div class="w-full md:w-2/3">
                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1.5">Option Values (Comma Separated)</label>
                <div class="flex items-center space-x-2">
                    <input type="text" name="opt_values[]" class="shopify-input text-sm font-mono flex-grow" placeholder="e.g. S, M, L, XL" required>
                    <button type="button" onclick="this.closest('.group').remove()" class="bg-red-50 text-red-500 hover:bg-red-500 hover:text-white w-10 h-10 rounded-lg flex items-center justify-center shrink-0 transition">
                        <i class="fas fa-trash text-sm"></i>
                    </button>
                </div>
            </div>
        `;
        varContainer.appendChild(row);
    }
    
    window.onload = () => {
        toggleDigitalFile();
        // Start with one empty variation row by default
        addVariationRow();
    };
</script>
<?php require '../common/bottom.php'; ?>
