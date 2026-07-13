<?php
require '../common/config.php';

// MAGIC FIX: Agar user pehle se logged in hai, toh seedha andar aane do!
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        if (password_verify($password, $user['password']) || $password === $user['password']) {
            $_SESSION['user_id'] = $user['id'];
            // Login hote hi seedha dashboard
            header("Location: dashboard.php");
            exit;
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
    <title>Seller Login - Dukaanova</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&display=swap" rel="stylesheet">
    <style> body { font-family: 'Inter', sans-serif; background-color: #f4f6f8; } </style>
</head>
<body class="antialiased min-h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full">
        <div class="text-center mb-8">
            <div class="w-12 h-12 bg-black text-white rounded-xl flex items-center justify-center text-2xl font-black mx-auto mb-4 shadow-lg">D</div>
            <h1 class="text-2xl font-black text-[#202223] tracking-tight">Seller Login</h1>
            <p class="text-sm text-gray-500 font-medium mt-1">Manage your Dukaanova store</p>
        </div>

        <div class="bg-white p-8 rounded-2xl shadow-sm border border-[#e1e3e5]">
            <?= $msg ?>
            <form method="POST" class="space-y-5">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1.5">Email Address</label>
                    <input type="email" name="email" required placeholder="admin@store.com" class="w-full p-3.5 bg-[#f9fafb] border border-[#e1e3e5] rounded-xl text-sm outline-none focus:border-black focus:ring-1 focus:ring-black transition font-medium text-[#202223]">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-widest mb-1.5">Password</label>
                    <input type="password" name="password" required placeholder="••••••••" class="w-full p-3.5 bg-[#f9fafb] border border-[#e1e3e5] rounded-xl text-sm outline-none focus:border-black focus:ring-1 focus:ring-black transition font-medium text-[#202223]">
                </div>
                <button type="submit" name="login" class="w-full bg-black hover:bg-gray-800 text-white py-4 rounded-xl text-sm font-bold uppercase tracking-widest transition shadow-lg mt-2 flex items-center justify-center">
                    Access Dashboard <i class="fas fa-lock ml-2 text-xs"></i>
                </button>
            </form>
        </div>

        <div class="text-center mt-8">
            <p class="text-sm text-gray-500 font-medium">Want to start selling? <a href="register.php" class="text-black font-bold hover:underline">Create Store</a></p>
        </div>
    </div>

</body>
</html>
