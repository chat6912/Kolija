<?php
session_start();
// Redirect to admin panel if already logged in
if (isset($_SESSION['admin'])) {
    header("Location: admin_panel.php");
    exit();
}

$error = '';

// Default admin credentials - in a real application, store these securely
$admin_email = "sexysweetheart6971005@gmail.com";
$admin_password = "258456258456aA$"; // For simplicity, but should be stored securely in a real app

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill all required fields';
    } else {
        // Simple admin authentication
        if ($email === $admin_email && $password === $admin_password) {
            $_SESSION['admin'] = true;
            header("Location: admin_panel.php");
            exit();
        } else {
            $error = 'Invalid admin credentials';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - KOLIJA</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="auth-form admin-login">
            <div class="logo">
                <h1>KOLIJA</h1>
            </div>
            <h2>Admin Login</h2>
            
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="admin_login.php">
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Admin Email</label>
                    <input type="email" id="email" name="email" required placeholder="Enter admin email">
                </div>
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Admin Password</label>
                    <input type="password" id="password" name="password" required placeholder="Enter admin password">
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn primary-btn">Login as Admin</button>
                </div>
            </form>
            <div class="auth-links">
                <p><a href="index.php">Back to Home</a></p>
            </div>
        </div>
    </div>
</body>
</html>
