<?php
require 'common/config.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$user_id = $_SESSION['user_id'];
$msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verify_csrf();
    $amount = (float)$_POST['amount'];
    if ($amount > 0) {
        $pdo->beginTransaction();
        try {
            $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?")->execute([$amount, $user_id]);
            $pdo->prepare("INSERT INTO transactions (user_id, amount, type, description) VALUES (?, ?, 'Credit', 'Added via dummy gateway')")->execute([$user_id, $amount]);
            $pdo->commit();
            $msg = "<div class='bg-gray-50 border border-gray-200 text-black px-4 py-3 text-sm font-bold mb-6 flex items-center'><i class='fas fa-check mr-2'></i> ₹".number_format($amount, 2)." added to wallet.</div>";
        } catch(Exception $e) {
            $pdo->rollBack();
            $msg = "<div class='bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm font-bold mb-6'>Error adding funds.</div>";
        }
    }
}

$stmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$balance = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$user_id]);
$transactions = $stmt->fetchAll();

require 'common/header.php';
?>
<div class="p-4 pb-24 max-w-md mx-auto">
    <h2 class="text-3xl font-black tracking-tight text-gray-900 mb-8 border-b border-gray-200 pb-4">Store Wallet</h2>
    <?= $msg ?>
    
    <div class="bg-gray-50 border border-gray-200 p-8 text-center mb-8 rounded-sm">
        <p class="text-xs uppercase tracking-widest text-gray-500 mb-2 font-bold">Available Balance</p>
        <h1 class="text-5xl font-black text-gray-900">₹<?= number_format($balance, 2) ?></h1>
    </div>

    <div class="mb-10">
        <h3 class="font-bold text-gray-900 uppercase tracking-wide text-sm mb-4">Add Funds (Demo)</h3>
        <form method="POST" class="flex">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="number" name="amount" placeholder="Amount (₹)" required class="flex-1 p-3 border border-gray-300 rounded-none rounded-l-sm outline-none focus:border-black text-sm">
            <button type="submit" class="bg-black text-white px-6 font-bold uppercase tracking-widest text-xs hover:bg-gray-800 transition rounded-r-sm">Add</button>
        </form>
    </div>

    <h3 class="font-bold text-gray-900 uppercase tracking-wide text-sm mb-4 border-b border-gray-200 pb-2">Recent Activity</h3>
    <?php if(empty($transactions)): ?>
        <p class="text-gray-500 text-sm">No recent transactions.</p>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach($transactions as $t): ?>
            <div class="flex justify-between items-center py-2 border-b border-gray-100 last:border-0">
                <div>
                    <p class="font-bold text-sm text-gray-900"><?= htmlspecialchars($t['description']) ?></p>
                    <p class="text-[11px] text-gray-500 mt-1 uppercase tracking-wider"><?= date('d M Y', strtotime($t['created_at'])) ?></p>
                </div>
                <p class="font-bold text-sm <?= $t['type'] == 'Credit' ? 'text-black' : 'text-gray-500' ?>">
                    <?= $t['type'] == 'Credit' ? '+' : '-' ?>₹<?= number_format($t['amount'], 2) ?>
                </p>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php require 'common/bottom.php'; ?>
