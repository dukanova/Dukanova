<?php
require 'common/config.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$user_id = $_SESSION['user_id'];

// MAGIC FIX: Agar notifications table nahi bani hai, toh yahan automatically ban jayegi!
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, title VARCHAR(100), message TEXT, is_read BOOLEAN DEFAULT FALSE, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX(user_id))");
} catch (Exception $e) {
    // Ignore if already exists or permission issue
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_read'])) {
    verify_csrf();
    // Mark all as read
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$user_id]);
    header("Location: notifications.php");
    exit;
}

// Fetch Notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();

// Get unread count
$unread = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$unread->execute([$user_id]);
$unread_count = $unread->fetchColumn();

require 'common/header.php';
?>
<div class="p-4 pb-24 min-h-screen bg-gray-900">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-white flex items-center">
            <i class="fas fa-bell text-indigo-500 mr-2"></i> Notifications
            <?php if($unread_count > 0): ?>
                <span class="ml-2 bg-red-500 text-white text-xs px-2 py-1 rounded-full"><?= $unread_count ?></span>
            <?php endif; ?>
        </h2>
        
        <?php if($unread_count > 0): ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="mark_read" value="1">
            <button type="submit" class="text-xs text-indigo-400 hover:text-white font-bold transition">Mark all read</button>
        </form>
        <?php endif; ?>
    </div>

    <?php if(empty($notifications)): ?>
        <div class="flex flex-col items-center justify-center mt-20 text-gray-500">
            <i class="fas fa-bell-slash text-6xl mb-4 opacity-50"></i>
            <p>You have no new notifications.</p>
        </div>
    <?php else: ?>
        <div class="space-y-3">
            <?php foreach($notifications as $n): ?>
            <div class="p-4 rounded-lg border <?= $n['is_read'] ? 'bg-gray-800 border-gray-700 opacity-75' : 'bg-gray-800 border-indigo-500 shadow-lg shadow-indigo-900/20' ?>">
                <div class="flex justify-between items-start mb-1">
                    <h3 class="font-bold text-sm <?= $n['is_read'] ? 'text-gray-300' : 'text-indigo-400' ?>">
                        <?= htmlspecialchars($n['title']) ?>
                    </h3>
                    <span class="text-[10px] text-gray-500"><?= date('d M, h:i A', strtotime($n['created_at'])) ?></span>
                </div>
                <p class="text-xs <?= $n['is_read'] ? 'text-gray-500' : 'text-gray-300' ?> leading-relaxed">
                    <?= htmlspecialchars($n['message']) ?>
                </p>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php require 'common/bottom.php'; ?>
