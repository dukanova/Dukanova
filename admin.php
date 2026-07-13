<?php
require 'common/config.php';
session_start();

// ADMIN LOGIN (Hardcoded PIN for now: 1234)
if (isset($_POST['admin_login'])) {
    if ($_POST['pin'] === '1234') { $_SESSION['admin_logged_in'] = true; } 
    else { $error = "Wrong PIN!"; }
}
if (isset($_GET['logout'])) { session_destroy(); header("Location: admin.php"); exit; }

if (!isset($_SESSION['admin_logged_in'])) {
    echo "<!DOCTYPE html><html><head><script src='https://cdn.tailwindcss.com'></script><link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'></head><body class='bg-gray-900 flex items-center justify-center h-screen'><div class='bg-white p-8 rounded-xl shadow-2xl w-96 text-center'><i class='fas fa-user-shield text-5xl text-indigo-600 mb-4'></i><h2 class='text-2xl font-black mb-4'>Admin Access</h2>".(isset($error)?"<p class='text-red-500 mb-4 font-bold'>$error</p>":"")."<form method='POST'><input type='password' name='pin' placeholder='Enter PIN (1234)' class='w-full p-3 border rounded-lg mb-4 text-center font-bold tracking-widest outline-none focus:border-indigo-500'><button type='submit' name='admin_login' class='w-full bg-black text-white font-bold py-3 rounded-lg hover:bg-gray-800'>Unlock</button></form></div></body></html>";
    exit;
}

// DB SETUP FOR STRIKES
try {
    $pdo->exec("ALTER TABLE sellers ADD COLUMN IF NOT EXISTS strikes INT DEFAULT 0");
    $pdo->exec("ALTER TABLE sellers ADD COLUMN IF NOT EXISTS account_status VARCHAR(20) DEFAULT 'active'");
} catch(Exception $e) {}

// HANDLE ACTIONS
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    if ($action == 'add_strike') {
        $stmt = $pdo->prepare("UPDATE sellers SET strikes = strikes + 1 WHERE id = ?");
        $stmt->execute([$id]);
        // Auto block if hits 3
        $pdo->query("UPDATE sellers SET account_status = 'blocked' WHERE id = $id AND strikes >= 3");
    } elseif ($action == 'reset') {
        $stmt = $pdo->prepare("UPDATE sellers SET strikes = 0, account_status = 'active' WHERE id = ?");
        $stmt->execute([$id]);
    }
    header("Location: admin.php"); exit;
}

// FETCH SELLERS
$sellers = $pdo->query("SELECT s.*, u.email FROM sellers s JOIN users u ON s.user_id = u.id ORDER BY s.id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>God Mode - Dukaanova Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-[#f4f6f8] font-sans antialiased p-8">
    <div class="max-w-6xl mx-auto">
        <div class="flex justify-between items-center mb-8 bg-black text-white p-6 rounded-2xl shadow-lg">
            <div>
                <h1 class="text-3xl font-black tracking-tight"><i class="fas fa-chess-king text-yellow-400 mr-2"></i> Master Admin</h1>
                <p class="text-gray-400 text-sm font-medium mt-1">Manage Sellers & Strict 3-Strike Rule</p>
            </div>
            <a href="admin.php?logout=1" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded-lg font-bold text-sm shadow"><i class="fas fa-sign-out-alt mr-2"></i> Exit God Mode</a>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-gray-50 border-b border-gray-200 text-xs uppercase tracking-widest text-gray-500">
                    <tr><th class="p-4">Store Name</th><th class="p-4">Owner Email</th><th class="p-4 text-center">Strikes (Premium Due)</th><th class="p-4 text-center">Status</th><th class="p-4 text-right">Actions</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach($sellers as $s): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="p-4 font-bold text-gray-900"><?= htmlspecialchars($s['store_name']) ?> <br><span class="text-[10px] text-gray-400 font-mono">/<?= $s['store_slug'] ?></span></td>
                        <td class="p-4 text-sm text-gray-600"><?= htmlspecialchars($s['email']) ?></td>
                        <td class="p-4 text-center">
                            <div class="flex items-center justify-center space-x-1">
                                <div class="w-3 h-3 rounded-full <?= $s['strikes'] >= 1 ? 'bg-red-500' : 'bg-gray-200' ?>"></div>
                                <div class="w-3 h-3 rounded-full <?= $s['strikes'] >= 2 ? 'bg-red-500' : 'bg-gray-200' ?>"></div>
                                <div class="w-3 h-3 rounded-full <?= $s['strikes'] >= 3 ? 'bg-red-500' : 'bg-gray-200' ?>"></div>
                            </div>
                            <p class="text-[10px] font-bold mt-1 text-gray-500"><?= $s['strikes'] ?> / 3</p>
                        </td>
                        <td class="p-4 text-center">
                            <?php if($s['account_status'] == 'blocked'): ?>
                                <span class="bg-red-100 text-red-700 text-[10px] font-black px-2 py-1 rounded uppercase">Banned</span>
                            <?php else: ?>
                                <span class="bg-green-100 text-green-700 text-[10px] font-black px-2 py-1 rounded uppercase">Active</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-4 text-right space-x-2">
                            <?php if($s['strikes'] < 3): ?>
                                <a href="admin.php?action=add_strike&id=<?= $s['id'] ?>" onclick="return confirm('Issue ₹1500 Premium Bill (Strike) to this seller?')" class="bg-yellow-500 hover:bg-yellow-600 text-white text-xs font-bold px-3 py-1.5 rounded shadow"><i class="fas fa-bolt mr-1"></i> Give Strike</a>
                            <?php endif; ?>
                            <a href="admin.php?action=reset&id=<?= $s['id'] ?>" onclick="return confirm('Reset strikes and unblock?')" class="bg-gray-200 hover:bg-gray-300 text-gray-800 text-xs font-bold px-3 py-1.5 rounded"><i class="fas fa-undo mr-1"></i> Reset</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
