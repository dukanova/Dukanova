<?php
require '../common/config.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$user_id = $_SESSION['user_id'];
$store_slug = isset($_GET['name']) ? trim($_GET['name']) : '';
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

// Fetch Order & Seller details
$stmt = $pdo->prepare("SELECT o.*, s.store_name, s.theme, s.upi_id, s.logo_image FROM orders o JOIN sellers s ON o.seller_id = s.id WHERE o.id = ? AND o.user_id = ?");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if (!$order) { header("Location: store.php?name=" . urlencode($store_slug)); exit; }

// Handle Payment Confirmation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_payment'])) {
    $pdo->prepare("UPDATE orders SET payment_status = 'Paid' WHERE id = ?")->execute([$order_id]);
    header("Location: success.php?name=" . urlencode($store_slug) . "&order_id=" . $order_id);
    exit;
}

// 🔥 GENERATE DYNAMIC UPI QR CODE 🔥
$upi_id = !empty($order['upi_id']) ? $order['upi_id'] : 'merchant@upi';
$store_name_encoded = urlencode($order['store_name']);
$amount = number_format($order['total_amount'], 2, '.', '');
$upi_intent_url = "upi://pay?pa={$upi_id}&pn={$store_name_encoded}&am={$amount}&cu=INR&tn=Order-{$order_id}";
// Using a reliable free API for QR generation
$qr_code_url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($upi_intent_url);

// 🔥 DYNAMIC THEME ENGINE 🔥
$themes = [
    'dawn' => ['bg' => '#f4f6f8', 'text' => '#202223', 'card' => '#ffffff', 'border' => '#e1e3e5', 'primary' => '#000000', 'primary_text' => '#ffffff', 'font' => 'Inter, sans-serif'],
    'ocean' => ['bg' => '#e0f2fe', 'text' => '#1e3a8a', 'card' => '#ffffff', 'border' => '#bae6fd', 'primary' => '#0284c7', 'primary_text' => '#ffffff', 'font' => 'Inter, sans-serif'],
    'sunset' => ['bg' => '#ffedd5', 'text' => '#7c2d12', 'card' => '#ffffff', 'border' => '#fed7aa', 'primary' => '#ea580c', 'primary_text' => '#ffffff', 'font' => 'Inter, sans-serif'],
    'pastel' => ['bg' => '#fdf2f8', 'text' => '#831843', 'card' => '#ffffff', 'border' => '#fbcfe8', 'primary' => '#db2777', 'primary_text' => '#ffffff', 'font' => 'Quicksand, sans-serif'],
    'vintage' => ['bg' => '#fef3c7', 'text' => '#451a03', 'card' => '#fffbeb', 'border' => '#fde68a', 'primary' => '#b45309', 'primary_text' => '#ffffff', 'font' => 'Merriweather, serif'],
    'midnight' => ['bg' => '#111827', 'text' => '#f3f4f6', 'card' => '#1f2937', 'border' => '#374151', 'primary' => '#ffffff', 'primary_text' => '#000000', 'font' => 'Inter, sans-serif'],
    'cyber' => ['bg' => '#000000', 'text' => '#22c55e', 'card' => '#052e16', 'border' => '#166534', 'primary' => '#22c55e', 'primary_text' => '#000000', 'font' => 'Courier New, monospace'],
    'street' => ['bg' => '#e5e7eb', 'text' => '#111827', 'card' => '#ffffff', 'border' => '#000000', 'primary' => '#ef4444', 'primary_text' => '#ffffff', 'font' => 'Impact, sans-serif'],
    'neon' => ['bg' => '#0f172a', 'text' => '#2dd4bf', 'card' => '#1e293b', 'border' => '#2dd4bf', 'primary' => '#a855f7', 'primary_text' => '#ffffff', 'font' => 'Inter, sans-serif']
];
$current_theme = $order['theme'] ?? 'dawn';
$t = $themes[$current_theme] ?? $themes['dawn'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Payment - <?= htmlspecialchars($order['store_name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --bg-color: <?= $t['bg'] ?>; --text-color: <?= $t['text'] ?>; --card-color: <?= $t['card'] ?>; --border-color: <?= $t['border'] ?>; --primary-color: <?= $t['primary'] ?>; --primary-text: <?= $t['primary_text'] ?>; --font-family: <?= $t['font'] ?>; }
        body { font-family: var(--font-family); background-color: var(--bg-color); color: var(--text-color); }
        .theme-card { background-color: var(--card-color); border: 1px solid var(--border-color); }
        .theme-btn { background-color: var(--primary-color); color: var(--primary-text); }
        .theme-btn:hover { opacity: 0.9; }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">

    <div class="theme-card max-w-md w-full rounded-3xl shadow-2xl overflow-hidden p-6 md:p-8 text-center border-t-8" style="border-top-color: var(--primary-color);">
        
        <?php if(!empty($order['logo_image'])): ?>
            <img src="../uploads/<?= htmlspecialchars($order['logo_image']) ?>" class="w-16 h-16 object-cover rounded-xl border border-gray-200 mx-auto mb-4 shadow-sm" style="border-color: var(--border-color);">
        <?php else: ?>
            <div class="w-16 h-16 rounded-xl mx-auto mb-4 flex items-center justify-center text-2xl font-black shadow-sm" style="background: var(--bg-color); border: 1px solid var(--border-color);">
                <i class="fas fa-store"></i>
            </div>
        <?php endif; ?>
        
        <h2 class="text-xl font-black tracking-tight mb-1"><?= htmlspecialchars($order['store_name']) ?></h2>
        <p class="text-xs font-bold uppercase tracking-widest opacity-60 mb-6">Order #<?= str_pad($order['id'], 5, "0", STR_PAD_LEFT) ?></p>

        <div class="bg-gray-50 border border-gray-200 rounded-2xl p-6 mb-6 inline-block w-full">
            <p class="text-sm font-bold text-gray-500 uppercase tracking-widest mb-1">Amount to Pay</p>
            <p class="text-4xl font-black text-gray-900 mb-6">₹<?= $amount ?></p>
            
            <div class="bg-white p-3 rounded-xl border border-gray-200 shadow-sm inline-block mx-auto">
                <img src="<?= $qr_code_url ?>" alt="UPI QR Code" class="w-48 h-48">
            </div>
            
            <p class="text-xs text-gray-500 font-medium mt-4">Scan with any UPI App (GPay, PhonePe, Paytm)</p>
        </div>

        <a href="<?= $upi_intent_url ?>" class="block w-full bg-[#1da851] text-white py-3.5 rounded-xl text-sm font-black uppercase tracking-widest transition shadow-lg mb-4 flex items-center justify-center md:hidden">
            <i class="fas fa-mobile-alt text-lg mr-2"></i> Pay Now via App
        </a>

        <form method="POST">
            <button type="submit" name="confirm_payment" class="theme-btn w-full py-4 rounded-xl text-sm font-black uppercase tracking-widest transition shadow-lg flex items-center justify-center">
                I have completed the payment <i class="fas fa-check-circle ml-2 text-lg"></i>
            </button>
        </form>

        <p class="text-[10px] font-bold opacity-50 mt-6"><i class="fas fa-lock mr-1"></i> Payments are processed directly via your bank.</p>
    </div>

</body>
</html>
