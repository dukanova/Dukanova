<?php
require '../common/config.php';
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM sellers WHERE user_id = ?");
$stmt->execute([$user_id]);
$seller = $stmt->fetch();
if (!$seller) { header("Location: store_profile.php"); exit; }
$seller_id = $seller['id'];

// 🔥 AUTO-HEALING: FIXING THE MISSING COLUMN ERROR 🔥
try {
    $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS customer_name VARCHAR(255) DEFAULT 'Guest Customer'");
} catch(Exception $e) {}

// 🔥 SMART CRM LOGIC: Fetch Unique Customers from Orders 🔥
$c_stmt = $pdo->prepare("
    SELECT 
        customer_name, 
        customer_phone, 
        COUNT(id) as total_orders, 
        SUM(total_amount) as total_spent, 
        MAX(created_at) as last_order_date 
    FROM orders 
    WHERE seller_id = ? 
    GROUP BY customer_phone 
    ORDER BY total_spent DESC
");
$c_stmt->execute([$seller_id]);
$customers = $c_stmt->fetchAll();

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
            <a href="customers.php" class="nav-item active"><i class="fas fa-users"></i> Customers</a>
            
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
            <div class="flex items-center"><button onclick="toggleSidebar()" class="md:hidden mr-4 text-gray-600 hover:text-black text-xl"><i class="fas fa-bars"></i></button><h1 class="text-xl font-black text-[#202223] tracking-tight">Customers</h1></div>
            <div class="flex items-center space-x-3"><a href="../store/store.php?name=<?= $seller['store_slug'] ?>" target="_blank" class="w-8 h-8 bg-gray-100 text-gray-600 rounded-full flex items-center justify-center hover:bg-gray-200 transition"><i class="fas fa-eye text-sm"></i></a></div>
        </header>

        <div class="p-4 md:p-8 max-w-6xl mx-auto w-full pb-10">
            
            <div class="flex justify-between items-end mb-6">
                <div>
                    <h2 class="text-2xl font-black text-[#202223] tracking-tight">Customer Directory</h2>
                    <p class="text-sm text-gray-500 font-medium mt-1">Manage and remarket to the people who buy from your store.</p>
                </div>
                <div class="bg-indigo-50 text-indigo-700 px-4 py-2 rounded-lg font-black text-xs uppercase tracking-widest shadow-sm">
                    Total: <?= count($customers) ?>
                </div>
            </div>

            <?php if(empty($customers)): ?>
                <div class="bg-white p-12 rounded-2xl shadow-sm border border-[#e1e3e5] text-center">
                    <div class="w-20 h-20 bg-gray-50 border border-[#e1e3e5] rounded-full flex items-center justify-center mx-auto mb-4 text-gray-300"><i class="fas fa-user-slash text-3xl"></i></div>
                    <h3 class="text-xl font-black text-[#202223] mb-2">No customers yet</h3>
                    <p class="text-sm text-gray-500 font-medium mb-6">Once you start receiving orders, your customers will appear here.</p>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-2xl shadow-sm border border-[#e1e3e5] overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 border-b border-[#e1e3e5]">
                                    <th class="p-4 text-xs font-bold text-gray-500 uppercase tracking-widest">Customer Name</th>
                                    <th class="p-4 text-xs font-bold text-gray-500 uppercase tracking-widest">Orders</th>
                                    <th class="p-4 text-xs font-bold text-gray-500 uppercase tracking-widest">Total Spent</th>
                                    <th class="p-4 text-xs font-bold text-gray-500 uppercase tracking-widest">Last Order</th>
                                    <th class="p-4 text-xs font-bold text-gray-500 uppercase tracking-widest text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#e1e3e5]">
                                <?php foreach($customers as $c): 
                                    $wa_phone = preg_replace('/[^0-9]/', '', $c['customer_phone']);
                                    if(strlen($wa_phone) == 10) { $wa_phone = '91' . $wa_phone; }
                                ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="p-4">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-10 h-10 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-black text-sm uppercase shrink-0">
                                                <?= substr(htmlspecialchars($c['customer_name'] ?? 'U'), 0, 2) ?>
                                            </div>
                                            <div>
                                                <p class="font-bold text-[#202223] text-sm"><?= htmlspecialchars($c['customer_name'] ?? 'Guest Customer') ?></p>
                                                <p class="text-xs text-gray-500 font-medium mt-0.5"><?= htmlspecialchars($c['customer_phone']) ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-4"><span class="bg-gray-100 text-gray-800 px-2.5 py-1 rounded-md text-xs font-black"><?= $c['total_orders'] ?></span></td>
                                    <td class="p-4 font-black text-[#202223]">₹<?= number_format($c['total_spent'], 2) ?></td>
                                    <td class="p-4 text-xs text-gray-500 font-bold"><?= date('M d, Y', strtotime($c['last_order_date'])) ?></td>
                                    <td class="p-4 text-right">
                                        <a href="https://wa.me/<?= $wa_phone ?>?text=Hello%20<?= urlencode($c['customer_name'] ?? 'Customer') ?>,%20thank%20you%20for%20shopping%20with%20<?= urlencode($seller['store_name']) ?>!%20Check%20out%20our%20new%20collection." target="_blank" class="inline-flex items-center justify-center bg-[#25D366] hover:bg-[#128C7E] text-white px-3 py-2 rounded-lg text-[10px] font-black uppercase tracking-widest transition shadow-sm">
                                            <i class="fab fa-whatsapp text-sm mr-1.5"></i> Chat
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </main>
</div>

<script>function toggleSidebar() { document.getElementById('appSidebar').classList.toggle('open'); document.getElementById('sidebarOverlay').classList.toggle('open'); }</script>
<?php require '../common/bottom.php'; ?>
