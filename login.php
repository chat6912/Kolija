<?php
session_start();
// Redirect to chat if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: chat.php");
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill all required fields';
    } else {
        // Process login
        $data = file_get_contents('data/users.json');
        $users = json_decode($data, true) ?: [];
        
        foreach ($users as $user) {
            if ($user['email'] === $email && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['gender'] = $user['gender'];
                $_SESSION['messages_used'] = $user['messages_used'];
                header("Location: chat.php");
                exit();
            }
        }
        
        $error = 'Invalid email or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - KOLIJA</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="auth-form">
            <div class="logo">
                <h1>KOLIJA</h1>
            </div>
            <h2>Login to Your Account</h2>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="login.php">
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" id="email" name="email" required placeholder="Enter your email">
                </div>
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" id="password" name="password" required placeholder="Enter your password">
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn primary-btn">Login</button>
                </div>
            </form>
            <div class="auth-links">
                <p>Don't have an account? <a href="register.php">Register</a></p>
                <p>Continue as <a href="chat.php?guest=true">Guest</a> (20 free messages)</p>
            </div>
        </div>
    </div>
</body>
</html>
