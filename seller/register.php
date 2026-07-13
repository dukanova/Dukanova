<?php
require '../common/config.php';

// MAGIC FIX: Agar Customer pehle se logged in hai, toh use naya account banane ki zaroorat nahi!
// Seedha Store Profile setup par bhejo.
if (isset($_SESSION['user_id'])) {
    header("Location: store_profile.php");
    exit;
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    // Hash password for security
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $msg = "<div class='bg-yellow-50 text-yellow-700 p-3 rounded-xl text-sm font-bold mb-4 border border-yellow-200 flex items-center'><i class='fas fa-exclamation-circle mr-2'></i> Email is already registered! Please log in.</div>";
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        if ($stmt->execute([$username, $email, $password])) {
            // Auto login after registration
            $_SESSION['user_id'] = $pdo->lastInsertId();
            // Seedha dukan banane wale page par bhejo
            header("Location: store_profile.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <title>Start Selling - Dukaanova</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; background-color: #f4f6f8; } </style>
</head>
<body class="antialiased min-h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full my-8">
        <div class="text-center mb-8">
            <div class="w-12 h-12 bg-black text-white rounded-xl flex items-center justify-center text-xl font-black mx-auto mb-4 shadow-lg"><i class="fas fa-store"></i></div>
            <h1 class="text-2xl font-black text-[#202223] tracking-tight">Become a Seller</h1>
            <p class="text-sm text-gray-500 font-medium mt-1">Start your e-commerce journey in 60 seconds</p>
        </div>

        <div class="bg-white p-8 rounded-2xl shadow-sm border border-[#e1e3e5]">
            <?= $msg ?>
            <form method="POST" class="space-y-5">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1.5">Full Name</label>
                    <input type="text" name="username" required placeholder="John Doe" class="w-full p-3.5 bg-[#f9fafb] border border-[#e1e3e5] rounded-xl text-sm outline-none focus:border-black focus:ring-1 focus:ring-black transition font-medium text-[#202223]">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1.5">Email Address</label>
                    <input type="email" name="email" required placeholder="john@example.com" class="w-full p-3.5 bg-[#f9fafb] border border-[#e1e3e5] rounded-xl text-sm outline-none focus:border-black focus:ring-1 focus:ring-black transition font-medium text-[#202223]">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1.5">Create Password</label>
                    <input type="password" name="password" required placeholder="Min 6 characters" class="w-full p-3.5 bg-[#f9fafb] border border-[#e1e3e5] rounded-xl text-sm outline-none focus:border-black focus:ring-1 focus:ring-black transition font-medium text-[#202223]">
                </div>
                
                <button type="submit" name="register" class="w-full bg-black hover:bg-gray-800 text-white py-4 rounded-xl text-sm font-bold uppercase tracking-widest transition shadow-lg mt-4 flex items-center justify-center">
                    Create Account <i class="fas fa-rocket ml-2 text-xs"></i>
                </button>
                <p class="text-[10px] text-gray-400 text-center mt-3 font-medium">By registering, you agree to our terms and policies.</p>
            </form>
        </div>

        <div class="text-center mt-8">
            <p class="text-sm text-gray-500 font-medium">Already have an account? <a href="login.php" class="text-black font-bold hover:underline">Log In</a></p>
        </div>
    </div>

</body>
</html>
