<?php
require '../common/config.php';
$store_slug = $_GET['name'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM sellers WHERE store_slug = ?");
$stmt->execute([$store_slug]);
$seller = $stmt->fetch();
if (!$seller) die("Store not found.");

if (isset($_SESSION['user_id'])) {
    header("Location: store.php?name=" . urlencode($store_slug));
    exit;
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? 'login';
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if ($action === 'register') {
        $username = trim($_POST['username']);
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $msg = "<div class='bg-red-50 text-red-700 p-3 text-sm font-bold mb-4 rounded'>Email already exists.</div>";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'customer')");
            if ($stmt->execute([$username, $email, $hash])) {
                $_SESSION['user_id'] = $pdo->lastInsertId();
                header("Location: store.php?name=" . urlencode($store_slug));
                exit;
            }
        }
    } else { // Login
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            header("Location: store.php?name=" . urlencode($store_slug));
            exit;
        } else {
            $msg = "<div class='bg-red-50 text-red-700 p-3 text-sm font-bold mb-4 rounded'>Invalid email or password.</div>";
        }
    }
}

// THEME LOGIC
$theme = $seller['theme'] ?? 'dawn';
if ($theme === 'ocean') {
    $font_family = "'Nunito', sans-serif"; $btn_class = "bg-blue-600 hover:bg-blue-700 text-white rounded-full";
} elseif ($theme === 'sunset') {
    $font_family = "'Poppins', sans-serif"; $btn_class = "bg-orange-500 hover:bg-orange-600 text-white rounded-lg";
} else {
    $font_family = "'Inter', sans-serif"; $btn_class = "bg-black hover:bg-gray-800 text-white rounded-sm";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account - <?= htmlspecialchars($seller['store_name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=Nunito:wght@400;700;900&family=Poppins:wght@400;700;900&display=swap" rel="stylesheet">
    <style> body { font-family: <?= $font_family ?>; background-color: #f9fafb; color: #121212; } </style>
</head>
<body class="antialiased flex flex-col min-h-screen items-center justify-center p-4">

    <div class="w-full max-w-md bg-white p-8 border border-gray-200 shadow-xl rounded-xl text-center">
        <?php if(!empty($seller['logo_image'])): ?>
            <img src="../uploads/<?= htmlspecialchars($seller['logo_image']) ?>" class="w-20 h-20 mx-auto rounded-full object-cover mb-4 shadow-sm border border-gray-100">
        <?php else: ?>
            <div class="w-16 h-16 bg-gray-100 rounded-full mx-auto flex items-center justify-center text-xl font-bold mb-4"><?= strtoupper(substr($seller['store_name'], 0, 1)) ?></div>
        <?php endif; ?>
        
        <h1 class="text-2xl font-black uppercase tracking-tight mb-6" id="formTitle">Log In to <?= htmlspecialchars($seller['store_name']) ?></h1>
        
        <?= $msg ?>

        <form method="POST" id="authForm" class="space-y-4 text-left">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <input type="hidden" name="action" id="formAction" value="login">

            <div id="nameField" class="hidden">
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Full Name</label>
                <input type="text" name="username" class="w-full p-3 border border-gray-300 rounded outline-none focus:border-black">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Email</label>
                <input type="email" name="email" required class="w-full p-3 border border-gray-300 rounded outline-none focus:border-black">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">Password</label>
                <input type="password" name="password" required class="w-full p-3 border border-gray-300 rounded outline-none focus:border-black">
            </div>

            <button type="submit" class="w-full <?= $btn_class ?> py-4 text-sm font-bold uppercase tracking-widest transition mt-2" id="submitBtn">Sign In</button>
        </form>

        <p class="mt-6 text-sm text-gray-500 font-bold cursor-pointer hover:text-black transition" onclick="toggleForm()" id="toggleText">New customer? Create account</p>
        <a href="store.php?name=<?= urlencode($store_slug) ?>" class="block mt-4 text-xs text-gray-400 underline hover:text-black">Return to Store</a>
    </div>

    <script>
        let isLogin = true;
        function toggleForm() {
            isLogin = !isLogin;
            document.getElementById('formAction').value = isLogin ? 'login' : 'register';
            document.getElementById('nameField').classList.toggle('hidden');
            document.getElementById('formTitle').innerText = isLogin ? 'Log In to <?= htmlspecialchars($seller['store_name']) ?>' : 'Create Account';
            document.getElementById('submitBtn').innerText = isLogin ? 'Sign In' : 'Register';
            document.getElementById('toggleText').innerText = isLogin ? 'New customer? Create account' : 'Already have an account? Log in';
        }
    </script>
</body>
</html>
