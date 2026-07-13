<?php
require '../common/config.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$store_slug = isset($_GET['name']) ? $_GET['name'] : '';
$user_id = $_SESSION['user_id'];

// Get Order and Seller Info together
$stmt = $pdo->prepare("SELECT o.*, s.store_name, s.store_slug, s.logo_image, s.theme FROM orders o JOIN sellers s ON o.seller_id = s.id WHERE o.id = ? AND o.user_id = ?");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch();

if (!$order) { header("Location: store.php?name=" . $store_slug); exit; }

// Use seller's exact slug for buttons to prevent "Store Not Found" error
$store_slug = $order['store_slug']; 

// Order Items
$i_stmt = $pdo->prepare("SELECT oi.*, p.name as product_name, p.image, p.product_type, p.digital_file, p.id as product_id FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$i_stmt->execute([$order_id]);
$items = $i_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Successful - <?= htmlspecialchars($order['store_name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; background-color: #f9fafb; } </style>
</head>
<body class="flex flex-col items-center justify-center min-h-screen p-4">

    <div class="mb-6 text-center">
        <?php if(!empty($order['logo_image'])): ?>
            <img src="../uploads/<?= htmlspecialchars($order['logo_image']) ?>" class="w-16 h-16 object-cover rounded-xl border border-gray-200 mx-auto shadow-sm mb-3">
        <?php endif; ?>
        <h2 class="text-xl font-black text-gray-800 tracking-tight"><?= htmlspecialchars($order['store_name']) ?></h2>
    </div>

    <div class="bg-white max-w-lg w-full rounded-3xl shadow-xl overflow-hidden border border-gray-200">
        
        <div class="bg-black p-8 text-center text-white relative">
            <div class="w-20 h-20 bg-green-500 text-white rounded-full flex items-center justify-center text-4xl mx-auto mb-4 shadow-lg border-4 border-green-300">
                <i class="fas fa-check"></i>
            </div>
            <h1 class="text-3xl font-black tracking-tight mb-1">Order Confirmed!</h1>
            <p class="text-gray-300 font-medium text-sm">A confirmation has been sent to your WhatsApp/Email.</p>
        </div>

        <div class="p-8">
            <div class="flex justify-between items-center mb-6 border-b border-gray-100 pb-4">
                <div>
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Order Number</p>
                    <p class="font-black text-gray-800 text-lg">#<?= str_pad($order['id'], 5, "0", STR_PAD_LEFT) ?></p>
                </div>
                <div class="text-right">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Amount Paid</p>
                    <p class="font-black text-gray-900 text-xl">₹<?= number_format($order['total_amount'], 2) ?></p>
                </div>
            </div>

            <h3 class="text-sm font-black text-gray-800 mb-4 uppercase tracking-widest">Your Items</h3>
            
            <div class="space-y-4 mb-8">
                <?php foreach($items as $item): ?>
                <div class="flex items-center space-x-4 bg-gray-50 p-4 rounded-xl border border-gray-100">
                    <div class="w-14 h-14 bg-white rounded-lg border border-gray-200 overflow-hidden shrink-0">
                        <?php if($item['image']): ?><img src="../uploads/<?= $item['image'] ?>" class="w-full h-full object-cover"><?php else: ?><i class="fas fa-box text-gray-400 flex items-center justify-center h-full"></i><?php endif; ?>
                    </div>
                    <div class="flex-grow">
                        <p class="text-sm font-bold text-gray-900"><?= htmlspecialchars($item['product_name']) ?></p>
                        <p class="text-xs text-gray-500 font-medium">Qty: <?= $item['quantity'] ?></p>
                    </div>
                    <div>
                        <?php if($item['product_type'] === 'digital' && !empty($item['digital_file'])): ?>
                            <a href="download.php?file_id=<?= $item['product_id'] ?>&order_id=<?= $order_id ?>" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-widest shadow-md transition flex items-center">
                                <i class="fas fa-download mr-2"></i> Download
                            </a>
                        <?php else: ?>
                            <span class="bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest flex items-center">
                                <i class="fas fa-truck mr-1"></i> Shipping
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="flex flex-col space-y-3">
                <a href="profile.php?name=<?= $store_slug ?>" class="w-full text-center bg-black hover:bg-gray-800 text-white py-4 rounded-xl text-sm font-black uppercase tracking-widest transition shadow-lg">
                    Go to My Orders <i class="fas fa-arrow-right ml-2"></i>
                </a>
                <a href="store.php?name=<?= $store_slug ?>" class="w-full text-center bg-gray-100 hover:bg-gray-200 text-gray-800 py-3.5 rounded-xl text-sm font-bold uppercase tracking-widest transition">
                    Continue Shopping
                </a>
            </div>
        </div>
    </div>

</body>
</html>
