<?php
require 'common/config.php';

$search_query = trim($_GET['q'] ?? '');
$suggested_products = [];

if (!empty($search_query)) {
    $like_query = "%" . $search_query . "%";
    
    // Yahan limit badha di hai taaki carousel mein items aache se dikhein
    $stmt = $pdo->prepare("SELECT * FROM sellers WHERE store_name IS NOT NULL AND (store_name LIKE ? OR description LIKE ?) ORDER BY created_at DESC LIMIT 8");
    $stmt->execute([$like_query, $like_query]);
    $sellers = $stmt->fetchAll();

    $stmt = $pdo->prepare("
        SELECT p.*, s.store_name, s.store_slug 
        FROM products p 
        JOIN sellers s ON p.seller_id = s.id 
        WHERE p.stock > 0 AND (p.name LIKE ? OR p.description LIKE ? OR s.store_name LIKE ? OR p.product_type LIKE ?)
        ORDER BY p.created_at DESC LIMIT 20
    ");
    $stmt->execute([$like_query, $like_query, $like_query, $like_query]);
    $products = $stmt->fetchAll();

    if (empty($products)) {
        $suggested_products = $pdo->query("
            SELECT p.*, s.store_name, s.store_slug 
            FROM products p 
            JOIN sellers s ON p.seller_id = s.id 
            WHERE p.stock > 0 
            ORDER BY RAND() LIMIT 8
        ")->fetchAll();
    }

} else {
    // Default Homepage View (Sellers limit is 8 for the carousel)
    $sellers = $pdo->query("SELECT * FROM sellers WHERE store_name IS NOT NULL ORDER BY RAND() LIMIT 8")->fetchAll();
    $products = $pdo->query("
        SELECT p.*, s.store_name, s.store_slug 
        FROM products p 
        JOIN sellers s ON p.seller_id = s.id 
        WHERE p.stock > 0 
        ORDER BY p.created_at DESC LIMIT 12
    ")->fetchAll();
}

require 'common/header.php';
?>
<style>
    /* Scrollbar hide karne ke liye */
    .hide-scrollbar::-webkit-scrollbar { display: none; }
    .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    .glass-effect { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(10px); }
</style>

<div class="bg-gray-50 min-h-screen pb-24 font-['Inter']">
    
    <div class="relative w-full h-[400px] md:h-[500px] bg-gray-900 overflow-hidden flex flex-col justify-center items-center px-4 rounded-b-[2.5rem] shadow-2xl">
        <img src="https://images.unsplash.com/photo-1472851294608-062f824d29cc?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80" class="absolute inset-0 w-full h-full object-cover opacity-40">
        <div class="absolute inset-0 bg-gradient-to-t from-gray-900 via-transparent to-transparent"></div>
        
        <div class="relative z-10 w-full max-w-2xl text-center mt-8">
            <span class="bg-indigo-600 text-white text-[10px] font-black uppercase tracking-widest px-3 py-1 rounded-full mb-4 inline-block shadow-lg">Welcome to Dukaanova</span>
            <h1 class="text-4xl md:text-6xl font-black text-white tracking-tight mb-4 leading-tight drop-shadow-md">
                Discover the Best <br><span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-pink-400">Independent Brands</span>
            </h1>
            
            <form method="GET" action="index.php" class="mt-8 relative max-w-lg mx-auto transform hover:scale-105 transition duration-300">
                <input type="text" name="q" value="<?= htmlspecialchars($search_query) ?>" placeholder="Search for stores, clothes, digital art..." required class="w-full pl-14 pr-24 py-4 rounded-2xl text-gray-900 text-sm font-bold shadow-2xl outline-none border-2 border-transparent focus:border-indigo-500 transition">
                <div class="absolute left-5 top-1/2 transform -translate-y-1/2 w-6 h-6 bg-indigo-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-search text-indigo-600 text-xs"></i>
                </div>
                <button type="submit" class="absolute right-2 top-1/2 transform -translate-y-1/2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition shadow-md">Search</button>
            </form>
        </div>
    </div>

    <?php if(!empty($search_query)): ?>
        <div class="max-w-7xl mx-auto px-4 mt-8">
            <div class="bg-indigo-50 border border-indigo-100 rounded-2xl p-4 flex items-center justify-between shadow-sm">
                <div>
                    <p class="text-xs text-indigo-600 font-bold uppercase tracking-widest">Search Results For</p>
                    <h2 class="text-2xl font-black text-gray-900">"<?= htmlspecialchars($search_query) ?>"</h2>
                </div>
                <a href="index.php" class="text-xs bg-white border border-gray-200 text-gray-700 px-4 py-2 rounded-lg font-bold hover:bg-gray-50 shadow-sm transition">Clear Filter</a>
            </div>
        </div>
    <?php else: ?>
        <div class="max-w-7xl mx-auto px-4 -mt-8 relative z-20">
            <div class="glass-effect rounded-2xl p-4 shadow-lg border border-gray-100 flex overflow-x-auto space-x-6 hide-scrollbar justify-between md:justify-center md:space-x-12">
                <a href="index.php?q=fashion" class="flex flex-col items-center group flex-shrink-0">
                    <div class="w-14 h-14 rounded-full bg-gray-100 group-hover:bg-indigo-100 text-gray-600 group-hover:text-indigo-600 flex items-center justify-center text-xl transition mb-2 shadow-sm"><i class="fas fa-tshirt"></i></div>
                    <span class="text-[10px] font-bold text-gray-700 uppercase tracking-wider">Fashion</span>
                </a>
                <a href="index.php?q=tech" class="flex flex-col items-center group flex-shrink-0">
                    <div class="w-14 h-14 rounded-full bg-gray-100 group-hover:bg-indigo-100 text-gray-600 group-hover:text-indigo-600 flex items-center justify-center text-xl transition mb-2 shadow-sm"><i class="fas fa-laptop"></i></div>
                    <span class="text-[10px] font-bold text-gray-700 uppercase tracking-wider">Tech</span>
                </a>
                <a href="index.php?q=digital" class="flex flex-col items-center group flex-shrink-0">
                    <div class="w-14 h-14 rounded-full bg-gray-100 group-hover:bg-indigo-100 text-gray-600 group-hover:text-indigo-600 flex items-center justify-center text-xl transition mb-2 shadow-sm"><i class="fas fa-cloud-download-alt"></i></div>
                    <span class="text-[10px] font-bold text-gray-700 uppercase tracking-wider">Digital</span>
                </a>
                <a href="index.php?q=home" class="flex flex-col items-center group flex-shrink-0">
                    <div class="w-14 h-14 rounded-full bg-gray-100 group-hover:bg-indigo-100 text-gray-600 group-hover:text-indigo-600 flex items-center justify-center text-xl transition mb-2 shadow-sm"><i class="fas fa-couch"></i></div>
                    <span class="text-[10px] font-bold text-gray-700 uppercase tracking-wider">Home</span>
                </a>
            </div>
        </div>
    <?php endif; ?>

    <?php if(!empty($sellers)): ?>
    <div class="max-w-7xl mx-auto px-4 mt-12 overflow-hidden">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-black text-gray-900 tracking-tight">
                <?= !empty($search_query) ? 'Stores found' : 'Trending Stores <i class="fas fa-fire text-orange-500 ml-1"></i>' ?>
            </h2>
        </div>
        
        <div class="flex overflow-x-auto space-x-4 md:space-x-6 pb-4 hide-scrollbar snap-x">
            <?php foreach($sellers as $s): ?>
            <a href="store/store.php?name=<?= urlencode($s['store_slug']) ?>" class="block w-64 md:w-72 flex-shrink-0 snap-start bg-white rounded-2xl shadow-sm hover:shadow-xl border border-gray-100 overflow-hidden transform hover:-translate-y-1 transition duration-300">
                <div class="h-28 bg-gray-200 relative">
                    <?php if(!empty($s['banner_image'])): ?>
                        <img src="uploads/<?= htmlspecialchars($s['banner_image']) ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <div class="w-full h-full bg-gradient-to-r from-gray-300 to-gray-400"></div>
                    <?php endif; ?>
                    <div class="absolute inset-0 bg-black bg-opacity-20"></div>
                </div>
                <div class="px-5 pb-5 relative -mt-8 flex flex-col items-center">
                    <div class="w-16 h-16 bg-white rounded-full border-4 border-white shadow-md overflow-hidden flex items-center justify-center mb-3">
                        <?php if(!empty($s['logo_image'])): ?>
                            <img src="uploads/<?= htmlspecialchars($s['logo_image']) ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <span class="font-black text-gray-400 text-xl"><?= strtoupper(substr($s['store_name'], 0, 1)) ?></span>
                        <?php endif; ?>
                    </div>
                    <h3 class="font-black text-gray-900 text-lg text-center truncate w-full"><?= htmlspecialchars($s['store_name']) ?></h3>
                    <p class="text-xs text-gray-500 text-center truncate w-full mt-1"><?= htmlspecialchars($s['description'] ?? 'Discover our amazing products.') ?></p>
                    <div class="mt-4 bg-gray-50 text-gray-800 text-[10px] font-bold uppercase tracking-widest px-4 py-2 rounded-lg w-full text-center border border-gray-100 hover:bg-gray-100 transition">Visit Store</div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="max-w-7xl mx-auto px-4 mt-10 mb-12">
        
        <?php if(!empty($search_query) && empty($products)): ?>
            <div class="bg-white p-10 md:p-16 text-center rounded-3xl border border-gray-200 shadow-sm mb-12">
                <div class="w-24 h-24 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-6 text-gray-300 text-4xl border-4 border-white shadow-md">
                    <i class="fas fa-search-minus"></i>
                </div>
                <h3 class="font-black text-gray-900 text-2xl mb-2">We couldn't find anything for "<?= htmlspecialchars($search_query) ?>"</h3>
                <p class="text-sm text-gray-500 max-w-md mx-auto">Try checking your spelling, using different keywords, or exploring our categories below.</p>
            </div>

            <?php if(!empty($suggested_products)): ?>
                <div class="flex items-center justify-between mb-6 border-t border-gray-200 pt-10">
                    <h2 class="text-2xl font-black text-gray-900 tracking-tight">You Might Also Like 🔥</h2>
                </div>
                <?php $products = $suggested_products; ?>
            <?php endif; ?>

        <?php else: ?>
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-black text-gray-900 tracking-tight">
                    <?= !empty($search_query) ? 'Products found' : 'Just Dropped ⚡' ?>
                </h2>
                <?php if(empty($search_query)): ?>
                    <a href="#" class="text-xs font-bold text-indigo-600 uppercase tracking-widest hover:underline">See More</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
            <?php foreach($products as $p): ?>
            <a href="store/product.php?name=<?= urlencode($p['store_slug']) ?>&id=<?= $p['id'] ?>" class="group bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-xl overflow-hidden flex flex-col relative transition duration-300">
                
                <div class="absolute top-2 left-2 z-10 flex flex-col space-y-1">
                    <?php if($p['product_type'] === 'digital'): ?>
                        <span class="bg-indigo-600 text-white text-[9px] font-bold px-2 py-1 rounded-sm shadow uppercase tracking-widest"><i class="fas fa-cloud-download-alt mr-1"></i> Digital</span>
                    <?php endif; ?>
                    <span class="bg-red text-white text-[9px] font-bold px-2 py-1 rounded-sm shadow uppercase tracking-widest">New</span>
                </div>

                <div class="w-full aspect-[4/5] bg-gray-100 relative overflow-hidden">
                    <?php if($p['image']): ?>
                        <img src="uploads/<?= htmlspecialchars($p['image']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                    <?php else: ?>
                        <div class="flex items-center justify-center w-full h-full text-gray-300"><i class="fas fa-image text-4xl"></i></div>
                    <?php endif; ?>
                </div>
                
                <div class="p-4 flex flex-col flex-grow">
                    <div class="flex items-center space-x-2 mb-2">
                        <i class="fas fa-store text-gray-400 text-[10px]"></i>
                        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest truncate hover:text-indigo-600 transition"><?= htmlspecialchars($p['store_name']) ?></p>
                    </div>
                    <h3 class="text-sm font-bold text-gray-900 leading-tight mb-3 line-clamp-2 group-hover:text-indigo-600 transition"><?= htmlspecialchars($p['name']) ?></h3>
                    
                    <div class="mt-auto flex items-center justify-between border-t border-gray-50 pt-3">
                        <p class="text-base font-black text-black">₹<?= number_format($p['price'], 2) ?></p>
                        <div class="w-8 h-8 rounded-full bg-gray-50 flex items-center justify-center text-gray-600 group-hover:bg-black group-hover:text-white transition shadow-sm border border-gray-100">
                            <i class="fas fa-arrow-right text-[10px]"></i>
                        </div>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <footer class="bg-white border-t border-gray-200 pt-12 pb-8 mt-12">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <h3 class="font-black text-2xl tracking-tighter mb-4 text-black uppercase">Dukaanova</h3>
            <p class="text-sm text-gray-500 max-w-md mx-auto mb-6">Empowering independent creators, brands, and sellers to build their dream digital stores.</p>
            <div class="flex justify-center space-x-4 text-gray-400 text-2xl mb-6">
                <i class="fab fa-cc-visa hover:text-gray-900 transition cursor-pointer"></i>
                <i class="fab fa-cc-mastercard hover:text-gray-900 transition cursor-pointer"></i>
                <i class="fab fa-apple-pay hover:text-gray-900 transition cursor-pointer"></i>
            </div>
            <p class="text-[11px] text-gray-400 font-bold uppercase tracking-widest">&copy; <?= date('Y') ?> Dukaanova Marketplace.</p>
        </div>
    </footer>
</div>
