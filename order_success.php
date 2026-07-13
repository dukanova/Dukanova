<?php
require 'common/config.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
require 'common/header.php';
?>
<div class="p-4 pb-24 flex flex-col items-center justify-center min-h-[70vh] text-center">
    <div class="w-24 h-24 bg-green-900 rounded-full flex items-center justify-center mb-6 border-4 border-green-500">
        <i class="fas fa-check text-5xl text-green-400"></i>
    </div>
    <h1 class="text-3xl font-bold mb-2">Order Successful!</h1>
    <p class="text-gray-400 mb-8">Thank you for your purchase. Your order has been placed successfully and the amount has been deducted from your wallet.</p>
    
    <a href="index.php" class="bg-indigo-600 px-8 py-3 rounded-lg font-bold text-lg shadow-lg w-full max-w-xs">Continue Shopping</a>
</div>
<?php require 'common/bottom.php'; ?>
