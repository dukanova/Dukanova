<?php
require '../common/config.php';
if (!isset($_SESSION['user_id'])) { die("Unauthorized Access!"); }

if (isset($_GET['file_id']) && isset($_GET['order_id'])) {
    $product_id = (int)$_GET['file_id'];
    $order_id = (int)$_GET['order_id'];
    $user_id = $_SESSION['user_id'];

    // Security Check: Kya is user ne sach mein ye order place kiya hai?
    $check_stmt = $pdo->prepare("SELECT oi.id FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.id = ? AND o.user_id = ? AND oi.product_id = ?");
    $check_stmt->execute([$order_id, $user_id, $product_id]);
    
    if ($check_stmt->rowCount() > 0) {
        // File ka naam nikalo
        $file_stmt = $pdo->prepare("SELECT digital_file, name FROM products WHERE id = ?");
        $file_stmt->execute([$product_id]);
        $product = $file_stmt->fetch();

        if ($product && !empty($product['digital_file'])) {
            $filepath = '../uploads/' . $product['digital_file'];
            
            if (file_exists($filepath)) {
                // Force Download start karo
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($product['digital_file']) . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($filepath));
                readfile($filepath);
                exit;
            } else {
                die("Error: File not found on server.");
            }
        }
    }
}
die("Invalid Download Link or Payment Not Verified.");
?>
