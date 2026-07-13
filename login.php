<?php
require 'common/config.php';

$store_slug = $_GET['name'] ?? '';

// ========================================================
// MAGIC FIX: NO MORE RESTRICTIONS! (Universal Access)
// ========================================================
// Agar user pehle se logged in hai (chahe customer ho ya seller)
if (isset($_SESSION['user_id'])) {
    if (!empty($store_slug)) {
        // Agar store ke link se aaya hai, toh seedha checkout bhejo
        header("Location: store/checkout.php?name=" . urlencode($store_slug));
        exit;
    } else {
        // Agar normal login.php khola hai, toh seedha Seller Dashboard me entry!
        header("Location: seller/dashboard.php");
        exit;
    }
}

$msg = '';

// HANDLE LOGIN LOGIC
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Find the user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // Password check (Supporting both hashed and plain text for dev safety)
        if (password_verify($password, $user['password']) || $password === $user['password']) {
            $_SESSION['user_id'] = $user['id'];
            
            // Redirect based on where they came from
            if (!empty($store_slug)) {
                header("Location: store/checkout.php?name=" . urlencode($store_slug));
                exit;
            } else {
                header("Location: seller/dashboard.php");
                exit;
            }
        } else {
            $msg = "<div class='bg-red-50 text-red-600 p-3 rounded-xl text-sm font-bold mb-4 border border-red-100 flex items-center'><i class='fas fa-times-circle mr-2'></i> Incorrect password.</div>";
        }
    } else {
        $msg = "<div class='bg-red-50 text-red-600 p-3 rounded-xl text-sm font-bold mb-4 border border-red-100 flex items-center'><i class='fas fa-user-slash mr-2'></i> Account not found!</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <title>Sign In - Dukaanova</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; background-color: #f4f6f8; } </style>
</head>
<body class="antialiased min-h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full">
        <div class="text-center mb-8">
            <div class="w-12 h-12 bg-black text-white rounded-xl flex items-center justify-center text-2xl font-black mx-auto mb-4 shadow-lg">D</div>
            <h1 class="text-2xl font-black text-[#202223] tracking-tight">Sign in to Dukaanova</h1>
            <p class="text-sm text-gray-500 font-medium mt-1">One account for Buying & Selling</p>
        </div>

        <div class="bg-white p-8 rounded-2xl shadow-sm border border-[#e1e3e5]">
            <?= $msg ?>
            
            <form method="POST" class="space-y-5">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1.5">Email Address</label>
                    <input type="email" name="email" required placeholder="name@example.com" class="w-full p-3.5 bg-[#f9fafb] border border-[#e1e3e5] rounded-xl text-sm outline-none focus:border-black focus:ring-1 focus:ring-black transition font-medium text-[#202223]">
                </div>
                
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest">Password</label>
                    </div>
                    <input type="password" name="password" required placeholder="••••••••" class="w-full p-3.5 bg-[#f9fafb] border border-[#e1e3e5] rounded-xl text-sm outline-none focus:border-black focus:ring-1 focus:ring-black transition font-medium text-[#202223]">
                </div>

                <button type="submit" name="login" class="w-full bg-black hover:bg-gray-800 text-white py-4 rounded-xl text-sm font-bold uppercase tracking-widest transition shadow-lg mt-2">
                    Continue <i class="fas fa-arrow-right ml-2 text-xs"></i>
                </button>
            </form>
        </div>

        <div class="text-center mt-8">
            <p class="text-sm text-gray-500 font-medium">Don't have an account? <a href="register.php<?= !empty($store_slug) ? '?name='.urlencode($store_slug) : '' ?>" class="text-black font-bold hover:underline">Create one</a></p>
        </div>
    </div>

</body>
</html>
