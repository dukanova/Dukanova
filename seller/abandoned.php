<?php
require '../common/config.php';
if (!isset($_SESSION['user_id'])) { header("Location: ../login.php"); exit; }

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM sellers WHERE user_id = ?");
$stmt->execute([$user_id]);
$seller = $stmt->fetch();
if (!$seller) { header("Location: store_profile.php"); exit; }
$seller_id = $seller['id'];

// Mark as Recovered Logic (Optional: Seller can click a button to hide the cart from the list)
if (isset($_GET['mark_recovered'])) {
    $cart_id = (int)$_GET['mark_recovered'];
    $pdo->prepare("UPDATE abandoned_carts SET status = 'recovered' WHERE id = ? AND seller_id = ?")->execute([$cart_id, $seller_id]);
    header("Location: abandoned.php"); exit;
}

// Fetch Abandoned Carts
$stmt = $pdo->prepare("
    SELECT ac.*, u.username, u.email 
    FROM abandoned_carts ac 
    JOIN users u ON ac.customer_id = u.id 
    WHERE ac.seller_id = ? AND ac.status = 'abandoned' 
    ORDER BY ac.last_updated DESC
");
$stmt->execute([$seller_id]);
$carts = $stmt->fetchAll();

require '../common/header.php';
?>
<div class="p-4 pb-24 max-w-2xl mx-auto">
    <div class="flex items-center mb-6">
        <a href="dashboard.php" class="w-8 h-8 bg-white border border-gray-200 rounded-full flex items-center justify-center text-gray-600 shadow-sm mr-3 hover:bg-gray-50 transition"><i class="fas fa-arrow-left"></i></a>
        <div>
            <h2 class="text-2xl font-black text-gray-900 tracking-tight">Abandoned Carts</h2>
            <p class="text-xs text-gray-500 font-bold">Recover lost sales via Gmail</p>
        </div>
    </div>

    <div class="bg-gradient-to-r from-indigo-600 to-purple-600 rounded-2xl p-6 text-white mb-8 shadow-xl relative overflow-hidden">
        <div class="absolute top-0 right-0 opacity-10 text-8xl transform translate-x-4 -translate-y-4"><i class="fas fa-shopping-cart"></i></div>
        <h3 class="font-black text-xl mb-2 relative z-10 drop-shadow-sm">Turn Carts into Cash 💸</h3>
        <p class="text-sm text-indigo-100 mb-5 relative z-10 leading-relaxed max-w-md">Customers below added items to their cart but didn't pay. Click 'Send Email' to open your Gmail app with a pre-written recovery message!</p>
        <div class="bg-white text-indigo-900 inline-block px-4 py-2 rounded-lg text-xs font-black uppercase tracking-widest shadow-md">Lost Carts Found: <?= count($carts) ?></div>
    </div>

    <?php if(empty($carts)): ?>
        <div class="bg-white p-12 rounded-2xl border border-gray-200 text-center shadow-sm mt-4">
            <div class="w-20 h-20 bg-green-50 text-green-500 rounded-full flex items-center justify-center mx-auto mb-4"><i class="fas fa-check-circle text-4xl shadow-sm rounded-full"></i></div>
            <h3 class="font-black text-gray-900 text-2xl mb-2">All Clear!</h3>
            <p class="text-sm text-gray-500 font-medium">You have no abandoned carts right now. Great job!</p>
        </div>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach($carts as $c): 
                $cart_items = json_decode($c['cart_data'], true);
                $total_items = array_sum($cart_items); 
                $time_ago = round((time() - strtotime($c['last_updated'])) / 60); 
                if($time_ago > 60) {
                    $hours = floor($time_ago / 60);
                    $time_display = $hours . " hours ago";
                } else {
                    $time_display = $time_ago . " mins ago";
                }

                // ----------------------------------------------------
                // MAGIC FIX: PRE-FILLED GMAIL APP LINK (Plain Text format)
                // ----------------------------------------------------
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                // Using clean URL
                $store_link = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/" . $seller['store_slug']; 
                $store_name = $seller['store_name'];
                $cust_name = $c['username'];

                $mail_subject = "Your cart at $store_name is waiting! 🛒";
                
                $mail_body = "Hi $cust_name,\n\n";
                $mail_body .= "We noticed you left some amazing items in your cart at $store_name, but didn't complete your purchase.\n\n";
                $mail_body .= "Good news - we've saved them for you! 🎁\n\n";
                $mail_body .= "Click the link below to return to your cart and securely checkout before they sell out:\n";
                $mail_body .= "$store_link\n\n";
                $mail_body .= "If you have any questions or need help, just reply to this email.\n\n";
                $mail_body .= "Best regards,\n";
                $mail_body .= "Team $store_name";

                // rawurlencode handles spaces and line breaks for the mailto link
                $mailto_link = "mailto:" . $c['email'] . "?subject=" . rawurlencode($mail_subject) . "&body=" . rawurlencode($mail_body);
            ?>
            <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm hover:shadow-md transition">
                <div class="flex justify-between items-start mb-4 border-b border-gray-100 pb-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 rounded-full bg-gray-100 border border-gray-200 flex items-center justify-center text-gray-500 text-lg shadow-inner">
                            <i class="fas fa-user"></i>
                        </div>
                        <div>
                            <p class="font-black text-base text-gray-900"><?= htmlspecialchars($c['username']) ?></p>
                            <p class="text-xs text-gray-500 font-medium mt-0.5"><?= htmlspecialchars($c['email']) ?></p>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] bg-red-50 text-red-600 font-black px-2.5 py-1 rounded uppercase tracking-widest border border-red-100">Hot Lead</span>
                        <p class="text-[10px] text-gray-400 mt-2 font-bold"><i class="far fa-clock"></i> <?= $time_display ?></p>
                    </div>
                </div>
                
                <div class="flex items-center justify-between bg-gray-50 p-3 rounded-xl border border-gray-100">
                    <p class="text-xs text-gray-700 font-bold"><i class="fas fa-shopping-bag text-indigo-500 mr-2 text-sm"></i> <?= $total_items ?> items left in cart</p>
                    
                    <div class="flex space-x-2">
                        <a href="abandoned.php?mark_recovered=<?= $c['id'] ?>" class="bg-white border border-gray-200 text-gray-500 hover:text-green-600 px-3 py-2.5 rounded-lg text-xs font-bold transition shadow-sm" title="Mark as Recovered/Done">
                            <i class="fas fa-check"></i>
                        </a>

                        <a href="<?= $mailto_link ?>" target="_blank" class="bg-black hover:bg-gray-800 text-white px-5 py-2.5 rounded-lg text-xs font-black uppercase tracking-widest shadow-lg transition transform hover:scale-105 flex items-center">
                            Open in Email App <i class="fas fa-external-link-alt ml-2"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php require '../common/bottom.php'; ?>
