<?php
require '../common/config.php';
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }

$user_id = $_SESSION['user_id'];
$msg = '';

try {
    $pdo->exec("ALTER TABLE sellers ADD COLUMN IF NOT EXISTS shipping_fee DECIMAL(10,2) DEFAULT 0.00");
} catch(Exception $e) {}

$stmt = $pdo->prepare("SELECT id, shipping_fee FROM sellers WHERE user_id = ?");
$stmt->execute([$user_id]);
$seller = $stmt->fetch();
if (!$seller) { header("Location: store_profile.php"); exit; }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $shipping_fee = (float)$_POST['shipping_fee'];
    $stmt = $pdo->prepare("UPDATE sellers SET shipping_fee = ? WHERE user_id = ?");
    if ($stmt->execute([$shipping_fee, $user_id])) {
        $msg = "<div class='bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6 text-sm font-bold flex items-center shadow-sm'><i class='fas fa-check-circle mr-2 text-lg'></i> Shipping charges updated!</div>";
        $seller['shipping_fee'] = $shipping_fee;
    }
}
require '../common/header.php';
?>
<div class="p-4 pb-24 max-w-md mx-auto">
    <div class="flex items-center mb-6">
        <a href="dashboard.php" class="w-8 h-8 bg-white border border-gray-200 rounded-full flex items-center justify-center text-gray-600 shadow-sm mr-3 hover:bg-gray-50 transition"><i class="fas fa-arrow-left"></i></a>
        <h2 class="text-2xl font-black text-gray-900 tracking-tight">Shipping Settings</h2>
    </div>
    
    <?= $msg ?>

    <form method="POST" class="bg-white p-6 rounded-2xl border border-gray-200 shadow-sm">
        <div class="w-16 h-16 bg-indigo-50 text-indigo-500 rounded-full flex items-center justify-center mb-4"><i class="fas fa-truck text-2xl"></i></div>
        <h3 class="font-bold text-gray-900 text-lg mb-1">Flat Rate Delivery</h3>
        <p class="text-xs text-gray-500 mb-6">Set a fixed delivery charge for all orders. This will be added to the customer's total bill at checkout. Set it to '0' for Free Delivery.</p>
        
        <label class="text-xs font-bold text-gray-500 uppercase tracking-wider block mb-2">Delivery Charge (₹)</label>
        <div class="relative mb-6">
            <span class="absolute inset-y-0 left-0 flex items-center pl-4 font-bold text-gray-500">₹</span>
            <input type="number" step="0.01" name="shipping_fee" value="<?= htmlspecialchars($seller['shipping_fee'] ?? '0.00') ?>" required class="w-full pl-8 p-4 bg-gray-50 border border-gray-200 rounded-xl text-lg font-black outline-none focus:border-indigo-500 transition shadow-inner text-gray-900">
        </div>

        <button type="submit" class="w-full bg-black hover:bg-gray-800 text-white p-4 rounded-xl font-bold uppercase tracking-widest text-sm transition shadow-lg">Save Changes</button>
    </form>
</div>
<?php require '../common/bottom.php'; ?>
