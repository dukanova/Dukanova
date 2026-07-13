<?php
// 🔥 1. START SESSION (Bina iske Login aur Cart kaam nahi karega!) 🔥
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 🔥 2. DATABASE CREDENTIALS 🔥
$host     = 'sql203.infinityfree.com';        // Ya fir 'localhost' agar pehle wo chal raha tha
$dbname   = 'if0_41448657_Dukaanova';        // Tumhare database ka naam
$username = 'if0_41448657';             // Default 'root' hota hai, par apne app me check karo
$password = '9QJJXP5KTnxOgzd';                 // ⚠️ SABSE BADI GALTI YAHI HAI! Apna sahi password yahan dalo (jaise 'root' ya 'admin')

// 🔥 3. SET TIMEZONE (For accurate order times) 🔥
date_default_timezone_set('Asia/Kolkata'); // Indian Standard Time

// 🔥 4. CREATE PDO CONNECTION 🔥
try {
    // UTF-8 set kiya hai taaki Hindi/Gujarati emojis wagera sab support kare
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    
    // Security & Debugging Mode On
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

} catch (PDOException $e) {
    // Agar database connect nahi hua toh ekdum clean error dikhega
    die("
        <div style='font-family: system-ui, sans-serif; text-align: center; padding: 50px; background-color: #f9fafb; min-height: 100vh;'>
            <h1 style='font-size: 4rem; margin-bottom: 10px;'>🔌</h1>
            <h2 style='color: #111827; font-weight: 900;'>Database Connection Failed!</h2>
            <p style='color: #4b5563; margin-bottom: 20px;'>Please check your credentials in <b>common/config.php</b></p>
            <div style='background: #fee2e2; color: #991b1b; padding: 15px; border-radius: 10px; display: inline-block; font-family: monospace; font-size: 14px; max-width: 600px; word-wrap: break-word;'>
                <b>Error:</b> " . htmlspecialchars($e->getMessage()) . "
            </div>
            <p style='margin-top: 30px; font-size: 12px; color: #9ca3af;'>Ensure you have created a database named '<b>$dbname</b>' and your MySQL server is running.</p>
        </div>
    ");
}
?>
