<?php
session_start();
// Include database connection
require_once 'db_connect.php';

// Check if user is logged in or guest
$is_guest = false;
if (!isset($_SESSION['user_id']) && !isset($_SESSION['is_guest'])) {
    header("Location: login.php");
    exit();
}

// Set guest flag if accessed as guest
if (isset($_GET['guest']) && $_GET['guest'] === 'true') {
    $is_guest = true;
    if (!isset($_SESSION['is_guest'])) {
        $_SESSION['is_guest'] = true;
        $_SESSION['guest_id'] = uniqid('guest_');
        $_SESSION['messages_used'] = 0;
    }
} elseif (isset($_SESSION['is_guest']) && $_SESSION['is_guest'] === true) {
    $is_guest = true;
}

// Get user data
$user_id = $_SESSION['user_id'] ?? $_SESSION['guest_id'];
$user_name = $_SESSION['user_name'] ?? 'Guest';
$messages_used = $_SESSION['messages_used'] ?? 0;
$message_limit = 20;
$is_premium = isset($_SESSION['is_premium']) && $_SESSION['is_premium'] === true;
$premium_approved = isset($_SESSION['premium_approved']) && $_SESSION['premium_approved'] === true;
$pending_premium = isset($_SESSION['pending_premium']) && $_SESSION['pending_premium'] === true;
$gender = $_SESSION['gender'] ?? '';

// Check premium status
$can_send_messages = true;
if (!$is_premium && !$premium_approved && $messages_used >= $message_limit) {
    $can_send_messages = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KOLIJA Chat</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="chat-container">
        <header class="chat-header">
            <h1>KOLIJA Chat</h1>
            <div class="chat-actions">
                <?php if (!$is_guest): ?>
                    <a href="help.php" class="btn secondary-btn"><i class="fas fa-headset"></i> Help</a>
                    <?php if (!$is_premium && !$premium_approved): ?>
                        <a href="payment.php" class="btn primary-btn"><i class="fas fa-crown"></i> Upgrade</a>
                    <?php endif; ?>
                    <a href="logout.php" class="btn secondary-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
                <?php else: ?>
                    <a href="register.php" class="btn primary-btn"><i class="fas fa-user-plus"></i> Register</a>
                    <a href="login.php" class="btn secondary-btn"><i class="fas fa-sign-in-alt"></i> Login</a>
                <?php endif; ?>
            </div>
        </header>
        
        <div class="chat-content">
            <div id="message-counter" class="message-counter">
                <?php if ($is_premium && $premium_approved): ?>
                    <span class="counter-premium">
                        <i class="fas fa-crown"></i> Premium user
                    </span>
                <?php elseif ($pending_premium): ?>
                    <span class="counter-pending">
                        <i class="fas fa-clock"></i> Premium approval pending
                    </span>
                <?php else: ?>
                    <span class="counter-free">
                        <i class="fas fa-comment"></i> <?php echo $messages_used; ?> / <?php echo $message_limit; ?> messages used
                    </span>
                <?php endif; ?>
            </div>
            
            <div id="chat-messages" class="chat-messages">
                <!-- Messages will be loaded here -->
            </div>
        </div>
        
        <footer class="chat-footer">
            <div class="chat-input">
                <textarea id="message-input" placeholder="Type your message..." <?php echo !$can_send_messages ? 'disabled' : ''; ?>></textarea>
                <div class="chat-input-actions">
                    <?php if (!$is_guest && ($is_premium || $premium_approved)): ?>
                        <button id="upload-button" <?php echo !$can_send_messages ? 'disabled' : ''; ?>>
                            <i class="fas fa-image"></i>
                        </button>
                        <input type="file" id="image-input" accept="image/*" style="display: none;">
                    <?php endif; ?>
                </div>
            </div>
            
            <button id="send-button" class="send-button" <?php echo !$can_send_messages ? 'disabled' : ''; ?>>
                <i class="fas fa-paper-plane"></i>
            </button>
        </footer>
        
        <!-- Hidden input fields for JavaScript -->
        <input type="hidden" id="is-premium" value="<?php echo $is_premium ? '1' : '0'; ?>">
        <input type="hidden" id="premium-approved" value="<?php echo $premium_approved ? '1' : '0'; ?>">
        <input type="hidden" id="pending-premium" value="<?php echo $pending_premium ? '1' : '0'; ?>">
    </div>
    
    <script src="js/chat.js"></script>
</body>
</html>
