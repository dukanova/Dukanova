<?php
// SAFETY CHECK: Pehle check karega ki database connection ($pdo) exist karta hai ya nahi
if (isset($pdo) && $pdo !== null) {
    try {
        // 1. Auto-create the required tables if they don't exist
        $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, title VARCHAR(100), message TEXT, is_read BOOLEAN DEFAULT FALSE, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX(user_id))");
        $pdo->exec("CREATE TABLE IF NOT EXISTS email_logs (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, recipient_email VARCHAR(100), subject VARCHAR(150), body TEXT, sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    } catch (Exception $e) {
        // Agar table pehle se bani hai ya koi minor issue hai toh code ko crash hone se bachayega
    }
}

/**
 * Core System Alert Function
 * Logs an "email" to the database and sends an in-app notification to the user.
 */
function send_system_alert($pdo, $user_id, $email, $subject, $message) {
    // Agar function call hote time bhi PDO nahi hai, toh fail safe return
    if (!isset($pdo) || $pdo === null) return false;
    
    try {
        // Log the Email
        $stmt = $pdo->prepare("INSERT INTO email_logs (user_id, recipient_email, subject, body) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $email, $subject, $message]);

        // Send In-App Notification
        $stmt2 = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
        $stmt2->execute([$user_id, $subject, $message]);
        
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>
