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
    $pdo->exec("ALTER TABLE order_items ADD COLUMN IF NOT EXISTS variation_data TEXT DEFAULT NULL");
} catch(Exception $e) {}

$msg = '';

// Handle Order Status Update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $order_status = $_POST['order_status'];
    $payment_status = $_POST['payment_status'];
    
    $update_stmt = $pdo->prepare("UPDATE orders SET order_status = ?, payment_status = ? WHERE id = ? AND seller_id = ?");
    if ($update_stmt->execute([$order_status, $payment_status, $order_id, $seller_id])) {
        $msg = "<div class='bg-white text-black p-4 rounded-xl border border-white mb-6 font-bold flex items-center shadow-sm'><i class='fas fa-check-circle text-xl mr-3'></i> Order #{$order_id} updated successfully!</div>";
    }
}

// Fetch all orders for this seller
$stmt = $pdo->prepare("SELECT * FROM orders WHERE seller_id = ? ORDER BY id DESC");
$stmt->execute([$seller_id]);
$orders = $stmt->fetchAll();

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
    .shopify-select { background-color: #f9fafb; border: 1px solid #e1e3e5; border-radius: 6px; padding: 8px 12px; font-size: 12px; font-weight: 600; outline: none; }
    .shopify-select:focus { border-color: #000000; box-shadow: 0 0 0 1px #000000; }
    .ad-banner-320 { w-full flex flex-col items-center justify-center overflow-hidden my-4 }
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
            <a href="orders.php" class="nav-item active"><i class="fas fa-inbox"></i> Orders</a>
            <a href="products.php" class="nav-item"><i class="fas fa-tags"></i> Products</a>
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
            <div class="flex items-center"><button onclick="toggleSidebar()" class="md:hidden mr-4 text-gray-600 hover:text-black text-xl"><i class="fas fa-bars"></i></button><h1 class="text-xl font-black text-[#202223] tracking-tight">Orders Management</h1></div>
            <div class="flex items-center space-x-3"><a href="../store/store.php?name=<?= $seller['store_slug'] ?>" target="_blank" class="w-8 h-8 bg-gray-100 text-gray-600 rounded-full flex items-center justify-center hover:bg-gray-200 transition"><i class="fas fa-eye text-sm"></i></a></div>
        </header>

        <div class="p-4 md:p-8 max-w-6xl mx-auto w-full pb-10">
            
            <div class="w-full flex flex-col items-center justify-center overflow-hidden mb-6 h-[70px]">
                <span class="text-[9px] font-bold uppercase text-gray-400 mb-1">Sponsored</span>
                <script> atOptions = { 'key' : '9d431c6145c1285c7ca61a32a79dbdfe', 'format' : 'iframe', 'height' : 50, 'width' : 320, 'params' : {} }; </script>
                <script src="https://www.highperformanceformat.com/9d431c6145c1285c7ca61a32a79dbdfe/invoke.js"></script>
            </div>

            <?= $msg ?>

            <div class="flex justify-between items-end mb-6">
                <div>
                    <h2 class="text-2xl font-black text-[#202223] tracking-tight">All Orders</h2>
                    <p class="text-sm text-gray-500 font-medium mt-1">Manage, fulfill, and update customer orders.</p>
                </div>
            </div>

            <?php if(empty($orders)): ?>
                <div class="bg-white p-12 rounded-2xl shadow-sm border border-[#e1e3e5] text-center">
                    <div class="w-20 h-20 bg-gray-50 border border-[#e1e3e5] rounded-full flex items-center justify-center mx-auto mb-4 text-gray-300"><i class="fas fa-box-open text-3xl"></i></div>
                    <h3 class="text-xl font-black text-[#202223] mb-2">No orders yet</h3>
                    <p class="text-sm text-gray-500 font-medium mb-6">Share your store link to start getting orders.</p>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach($orders as $order): 
                        // Fetch order items
                        $i_stmt = $pdo->prepare("SELECT oi.*, p.name, p.image, p.product_type FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
                        $i_stmt->execute([$order['id']]);
                        $items = $i_stmt->fetchAll();
                        
                        $status_colors = [
                            'Pending' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                            'Processing' => 'bg-blue-100 text-blue-800 border-blue-200',
                            'Shipped' => 'bg-indigo-100 text-indigo-800 border-indigo-200',
                            'Delivered' => 'bg-green-100 text-green-800 border-green-200',
                            'Cancelled' => 'bg-red-100 text-red-800 border-red-200'
                        ];
                        $pay_colors = [
                            'Pending' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                            'Paid' => 'bg-green-100 text-green-800 border-green-200',
                            'Failed' => 'bg-red-100 text-red-800 border-red-200',
                            'Refunded' => 'bg-gray-100 text-gray-800 border-gray-200'
                        ];
                        $s_col = $status_colors[$order['order_status']] ?? 'bg-gray-100 text-gray-800 border-gray-200';
                        $p_col = $pay_colors[$order['payment_status']] ?? 'bg-gray-100 text-gray-800 border-gray-200';

                        // 🔥 DYNAMIC ATTRACTIVE WHATSAPP MESSAGE GENERATION 🔥
                        $wa_phone = preg_replace('/[^0-9]/', '', $order['customer_phone']);
                        if(strlen($wa_phone) == 10) { $wa_phone = '91' . $wa_phone; } // Defaulting to India if 10 digits
                        
                        $c_name = !empty($order['customer_name']) ? trim($order['customer_name']) : 'Customer';
                        $s_name = trim($seller['store_name']);
                        $o_id = str_pad($order['id'], 5, '0', STR_PAD_LEFT);
                        $o_total = number_format($order['total_amount'], 2);
                        
                        $wa_msg = "Hello *$c_name*, 🛍️\n\n";
                        $wa_msg .= "Thank you for your order from *$s_name*!\n\n";
                        $wa_msg .= "*Order Summary:*\n";
                        $wa_msg .= "🔹 Order ID: #$o_id\n";
                        $wa_msg .= "🔹 Total Amount: ₹$o_total\n";
                        $wa_msg .= "🔹 Status: " . $order['order_status'] . "\n\n";
                        $wa_msg .= "We will notify you once it's shipped. Thanks for shopping with us! ✨";
                        
                        $wa_link = "https://wa.me/" . $wa_phone . "?text=" . urlencode($wa_msg);
                    ?>
                    <div class="bg-white rounded-2xl shadow-sm border border-[#e1e3e5] overflow-hidden">
                        
                        <div class="bg-gray-50 px-6 py-4 border-b border-[#e1e3e5] flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                            <div>
                                <h3 class="text-lg font-black text-[#202223]">Order #<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?></h3>
                                <p class="text-xs text-gray-500 font-bold mt-1"><?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></p>
                            </div>
                            <div class="text-left md:text-right">
                                <p class="text-xl font-black text-[#202223]">₹<?= number_format($order['total_amount'], 2) ?></p>
                                <?php if($order['discount_amount'] > 0): ?>
                                    <p class="text-[10px] text-green-600 font-bold uppercase tracking-widest mt-0.5">Discount: ₹<?= number_format($order['discount_amount'], 2) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-8">
                            
                            <div>
                                <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-3">Customer Details</h4>
                                <p class="font-bold text-sm text-[#202223] mb-1"><i class="fas fa-user text-gray-400 w-5"></i> <?= htmlspecialchars($order['customer_name'] ?? 'Guest') ?></p>
                                <p class="font-bold text-sm text-[#202223] mb-1"><i class="fas fa-phone text-gray-400 w-5"></i> <?= htmlspecialchars($order['customer_phone']) ?></p>
                                <p class="font-bold text-sm text-[#202223] mb-4"><i class="fas fa-map-marker-alt text-gray-400 w-5"></i> <?= nl2br(htmlspecialchars($order['shipping_address'])) ?></p>

                                <a href="<?= $wa_link ?>" target="_blank" class="mb-6 inline-flex items-center justify-center bg-[#25D366] hover:bg-[#128C7E] text-white px-4 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition shadow-sm w-full md:w-auto">
                                    <i class="fab fa-whatsapp text-lg mr-2"></i> Send Invoice via WhatsApp
                                </a>

                                <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-3 mt-6 border-t border-gray-100 pt-4">Payment Information</h4>
                                <div class="flex items-center space-x-2 mb-2">
                                    <span class="text-sm font-bold text-[#202223]"><i class="fas fa-credit-card text-gray-400 w-5"></i> Method: <?= htmlspecialchars($order['payment_method']) ?></span>
                                </div>
                                
                                <?php if(!empty($order['utr_number'])): ?>
                                    <div class="bg-indigo-50 border border-indigo-200 p-3 rounded-lg mt-2 inline-block">
                                        <p class="text-[10px] font-black text-indigo-500 uppercase tracking-widest mb-0.5">Customer UTR / Reference No.</p>
                                        <p class="text-sm font-mono font-black text-indigo-900 tracking-wider"><?= htmlspecialchars($order['utr_number']) ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div>
                                <h4 class="text-xs font-black text-gray-400 uppercase tracking-widest mb-3">Order Items</h4>
                                <div class="space-y-4">
                                    <?php foreach($items as $item): ?>
                                    <div class="flex items-center space-x-4">
                                        <div class="w-12 h-12 rounded-lg border border-gray-200 overflow-hidden shrink-0">
                                            <?php if($item['image']): ?><img src="../uploads/<?= $item['image'] ?>" class="w-full h-full object-cover"><?php else: ?><div class="w-full h-full bg-gray-100 flex items-center justify-center"><i class="fas fa-box text-gray-300"></i></div><?php endif; ?>
                                        </div>
                                        <div class="flex-grow">
                                            <p class="text-sm font-bold text-[#202223] leading-tight mb-1"><?= htmlspecialchars($item['name']) ?></p>
                                            
                                            <?php if(!empty($item['variation_data'])): ?>
                                                <div class="flex flex-wrap gap-1 mb-1">
                                                    <?php 
                                                    $vars = json_decode($item['variation_data'], true);
                                                    if(is_array($vars)){
                                                        foreach($vars as $k => $v){
                                                            echo "<span class='text-[9px] bg-gray-200 text-gray-800 px-1.5 py-0.5 rounded font-black uppercase tracking-widest'>".htmlspecialchars($k).": ".htmlspecialchars($v)."</span>";
                                                        }
                                                    }
                                                    ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <p class="text-xs text-gray-500 font-bold">Qty: <?= $item['quantity'] ?> × ₹<?= number_format($item['price'], 2) ?></p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-sm font-black text-[#202223]">₹<?= number_format($item['seller_amount'], 2) ?></p>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                        </div>

                        <div class="bg-gray-50 px-6 py-4 border-t border-[#e1e3e5]">
                            <form method="POST" class="flex flex-col md:flex-row items-end md:items-center justify-between gap-4">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                
                                <div class="flex flex-col md:flex-row items-center gap-4 w-full md:w-auto">
                                    <div class="w-full md:w-auto">
                                        <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1">Order Status</label>
                                        <select name="order_status" class="shopify-select w-full md:w-40 border border-gray-300">
                                            <option value="Pending" <?= $order['order_status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="Processing" <?= $order['order_status'] == 'Processing' ? 'selected' : '' ?>>Processing</option>
                                            <option value="Shipped" <?= $order['order_status'] == 'Shipped' ? 'selected' : '' ?>>Shipped</option>
                                            <option value="Delivered" <?= $order['order_status'] == 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                                            <option value="Cancelled" <?= $order['order_status'] == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                        </select>
                                    </div>
                                    <div class="w-full md:w-auto">
                                        <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-1">Payment Status</label>
                                        <select name="payment_status" class="shopify-select w-full md:w-40 border border-gray-300">
                                            <option value="Pending" <?= $order['payment_status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="Paid" <?= $order['payment_status'] == 'Paid' ? 'selected' : '' ?>>Paid</option>
                                            <option value="Failed" <?= $order['payment_status'] == 'Failed' ? 'selected' : '' ?>>Failed</option>
                                            <option value="Refunded" <?= $order['payment_status'] == 'Refunded' ? 'selected' : '' ?>>Refunded</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <button type="submit" name="update_status" class="w-full md:w-auto bg-black hover:bg-gray-800 text-white px-6 py-2.5 rounded-lg text-xs font-black uppercase tracking-widest transition shadow-sm">
                                    Update Order
                                </button>
                            </form>
                        </div>

                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="w-full flex flex-col items-center justify-center overflow-hidden mt-8 h-[70px]">
                <span class="text-[9px] font-bold uppercase text-gray-400 mb-1">Sponsored</span>
                <script> atOptions = { 'key' : 'c84ae23d36c8befc3df5f872bb6555ce', 'format' : 'iframe', 'height' : 50, 'width' : 320, 'params' : {} }; </script>
                <script src="https://www.highperformanceformat.com/c84ae23d36c8befc3df5f872bb6555ce/invoke.js"></script>
            </div>

        </div>
    </main>
</div>

<script>function toggleSidebar() { document.getElementById('appSidebar').classList.toggle('open'); document.getElementById('sidebarOverlay').classList.toggle('open'); }</script>
<?php require '../common/bottom.php'; ?>
