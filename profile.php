<?php
require 'common/config.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$user_id = $_SESSION['user_id'];
$msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    verify_csrf(); 
    if ($_POST['action'] == 'logout') {
        session_destroy();
        header("Location: login.php");
        exit;
    } elseif ($_POST['action'] == 'add_address') {
        $label = $_POST['label'];
        $address = $_POST['address'];
        $phone = $_POST['phone'];
        $pdo->prepare("INSERT INTO customer_addresses (user_id, label, address, phone) VALUES (?, ?, ?, ?)")
            ->execute([$user_id, $label, $address, $phone]);
        $msg = "<div class='bg-gray-50 border border-gray-200 px-4 py-3 text-sm font-bold mb-6 flex items-center'><i class='fas fa-check mr-2'></i> Address saved.</div>";
    }
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM customer_addresses WHERE user_id = ?");
$stmt->execute([$user_id]);
$addresses = $stmt->fetchAll();

require 'common/header.php';
?>
<div class="p-4 pb-24 max-w-md mx-auto">
    <div class="flex items-center justify-between mb-8 border-b border-gray-200 pb-4">
        <h2 class="text-3xl font-black tracking-tight text-gray-900">My Account</h2>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="action" value="logout">
            <button type="submit" class="text-xs font-bold text-gray-500 hover:text-black uppercase tracking-widest transition">Log out</button>
        </form>
    </div>
    
    <?= $msg ?>

    <div class="mb-8">
        <p class="text-lg font-bold text-gray-900">Welcome, <?= htmlspecialchars($user['username']) ?></p>
        <p class="text-sm text-gray-500 mb-4"><?= htmlspecialchars($user['email']) ?></p>
        
        
    <h3 class="font-bold text-gray-900 uppercase tracking-wide text-sm mb-4 border-b border-gray-200 pb-2">Order History</h3>
    <?php if(empty($orders)): ?>
        <p class="text-gray-500 text-sm mb-8">You haven't placed any orders yet.</p>
    <?php else: ?>
        <div class="space-y-4 mb-8">
            <?php foreach($orders as $o): ?>
                <div class="flex justify-between items-center py-3 border-b border-gray-100">
                    <div>
                        <p class="font-bold text-sm text-gray-900">Order #<?= $o['id'] ?></p>
                        <p class="text-[11px] text-gray-500 uppercase tracking-wider mt-1"><?= date('M d, Y', strtotime($o['created_at'])) ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-bold text-gray-900">₹<?= number_format($o['total_price'], 2) ?></p>
                        <p class="text-[10px] text-gray-500 uppercase tracking-widest mt-1"><?= $o['status'] ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <h3 class="font-bold text-gray-900 uppercase tracking-wide text-sm mb-4 border-b border-gray-200 pb-2">Saved Addresses</h3>
    <div class="space-y-4 mb-8">
        <?php foreach($addresses as $addr): ?>
            <div class="border border-gray-200 p-4 rounded-sm">
                <p class="text-xs font-bold uppercase tracking-widest text-gray-900 mb-2"><?= htmlspecialchars($addr['label']) ?></p>
                <p class="text-sm text-gray-600 leading-relaxed"><?= htmlspecialchars($addr['address']) ?></p>
                <p class="text-xs text-gray-500 mt-2">Tel: <?= htmlspecialchars($addr['phone']) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
        
    <h3 class="font-bold text-gray-900 uppercase tracking-wide text-sm mb-4">Add New Address</h3>
    <form method="POST" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="action" value="add_address">
        <input type="text" name="label" placeholder="Address Label (e.g., Home)" required class="w-full p-3 border border-gray-300 rounded-sm text-sm outline-none focus:border-black transition">
        <input type="text" name="phone" placeholder="Phone Number" required class="w-full p-3 border border-gray-300 rounded-sm text-sm outline-none focus:border-black transition">
        <textarea name="address" placeholder="Full Postal Address" required class="w-full p-3 border border-gray-300 rounded-sm text-sm h-24 outline-none focus:border-black transition"></textarea>
        <button type="submit" class="w-full bg-black text-white py-4 text-xs font-bold uppercase tracking-widest hover:bg-gray-800 transition rounded-sm">Save Address</button>
    </form>

</div>
<?php require 'common/bottom.php'; ?>
