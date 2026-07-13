    </main> <?php
$current_url = $_SERVER['REQUEST_URI'] ?? $_SERVER['PHP_SELF'];
$is_seller = (strpos($current_url, '/seller/') !== false);
$is_admin = (strpos($current_url, '/admin/') !== false);
$is_store = (strpos($current_url, '/store/') !== false);
                 
$prefix = ($is_seller || $is_admin || $is_store) ? '../' : '';
?>
    <?php if($is_seller): ?>
        <div class="fixed bottom-0 w-full bg-white border-t border-gray-200 flex justify-around py-3 z-50 shadow-[0_-4px_6px_-1px_rgba(0,0,0,0.05)]">
            <a href="dashboard.php" class="text-gray-500 hover:text-black text-center flex flex-col items-center transition"><i class="fas fa-chart-pie text-xl mb-1"></i><span class="text-[10px] font-bold">Dash</span></a>
            <a href="products.php" class="text-gray-500 hover:text-black text-center flex flex-col items-center transition"><i class="fas fa-box text-xl mb-1"></i><span class="text-[10px] font-bold">Items</span></a>
            <a href="orders.php" class="text-gray-500 hover:text-black text-center flex flex-col items-center transition"><i class="fas fa-shopping-bag text-xl mb-1"></i><span class="text-[10px] font-bold">Orders</span></a>
            <a href="store_profile.php" class="text-gray-500 hover:text-black text-center flex flex-col items-center transition"><i class="fas fa-store text-xl mb-1"></i><span class="text-[10px] font-bold">Store</span></a>
            <a href="../profile.php" class="text-red-500 hover:text-red-700 text-center flex flex-col items-center transition border-l border-gray-200 pl-4"><i class="fas fa-sign-out-alt text-xl mb-1"></i><span class="text-[10px] font-bold">Exit</span></a>
        </div>
    <?php elseif(!$is_admin && !$is_store): ?>
        <footer class="bg-white border-t border-gray-200 py-10 mt-12">
            <div class="max-w-4xl mx-auto px-4 text-center">
                <h3 class="font-black text-2xl tracking-tighter mb-6 text-black">DUKAANOVA</h3>
                <div class="flex flex-wrap justify-center gap-6 text-xs text-gray-500 font-bold mb-8 uppercase tracking-widest">
                    <a href="<?= $prefix ?>index.php" class="hover:text-black transition">Shop</a>
                    <a href="<?= $prefix ?>profile.php" class="hover:text-black transition">Account</a>
                    <a href="<?= $prefix ?>cart.php" class="hover:text-black transition">Cart</a>
                    <a href="<?= $prefix ?>wallet.php" class="hover:text-black transition">Wallet</a>
                </div>
                <div class="flex justify-center space-x-4 text-gray-300 text-2xl mb-6">
                    <i class="fab fa-cc-visa"></i>
                    <i class="fab fa-cc-mastercard"></i>
                    <i class="fab fa-cc-paypal"></i>
                    <i class="fab fa-apple-pay"></i>
                </div>
                <p class="text-[11px] text-gray-400 font-medium">&copy; <?= date('Y') ?> Dukaanova Marketplace. Crafted with precision.</p>
            </div>
        </footer>
    <?php endif; ?>
</body>
</html>
