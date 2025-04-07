<?php
session_start();
// Redirect to chat if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: chat.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KOLIJA - Smart Chatbot</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="welcome-page">
            <div class="logo">
                <h1>কলিজা</h1>
                <p>তোমাৰ বিশ্বাসী জিৱনসংগী</p>
            </div>
            <div class="welcome-buttons">
                <a href="login.php" class="btn primary-btn">Login</a>
                <a href="register.php" class="btn secondary-btn">Register</a>
                <p class="free-trial-text">Try for free - 20 messages without registration!</p>
                <a href="chat.php?guest=true" class="btn ghost-btn">Continue as Guest</a>
                <p class="admin-link"><a href="admin_login.php">Admin Login</a></p>
            </div>
            <div class="features">
                <div class="feature">
                    <i class="fas fa-robot"></i>
                    <h3>Smart Chatbot</h3>
                    <p>Get instant answers to your questions</p>
                </div>
                <div class="feature">
                    <i class="fas fa-image"></i>
                    <h3>Image Support</h3>
                    <p>Share and receive images in your conversations</p>
                </div>
                <div class="feature">
                    <i class="fas fa-lock"></i>
                    <h3>Secure</h3>
                    <p>Your data is always protected</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
