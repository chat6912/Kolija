<?php
session_start();
require 'db_config.php'; // database connection

// Redirect to chat if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: chat.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $gender = $_POST['gender'] ?? '';

    if (empty($name) || empty($email) || empty($password) || empty($confirm_password) || empty($gender)) {
        $error = 'Please fill all required fields';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = 'Email already exists';
        } else {
            // Insert new user
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $created_at = date('Y-m-d H:i:s');
            $subscription_status = 'free';
            $messages_used = 0;

            $stmt = $conn->prepare("INSERT INTO users (name, email, password, gender, subscription_status, messages_used, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssis", $name, $email, $hashedPassword, $gender, $subscription_status, $messages_used, $created_at);

            if ($stmt->execute()) {
                $user_id = $stmt->insert_id;

                // Set session
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $name;
                $_SESSION['gender'] = $gender;
                $_SESSION['messages_used'] = 0;

                header("Location: chat.php");
                exit();
            } else {
                $error = 'Error creating account. Please try again.';
            }
        }
        $stmt->close();
    }
}
?>

<!-- HTML Form remains the same -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - KOLIJA</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="auth-form register-form">
            <div class="logo">
                <h1>KOLIJA</h1>
            </div>
            <h2>Create an Account</h2>

            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" action="register.php">
                <div class="form-group">
                    <label for="name"><i class="fas fa-user"></i> Full Name</label>
                    <input type="text" id="name" name="name" required placeholder="Enter your full name" value="<?php echo htmlspecialchars($name ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" id="email" name="email" required placeholder="Enter your email" value="<?php echo htmlspecialchars($email ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label><i class="fas fa-venus-mars"></i> Gender</label>
                    <div class="radio-group">
                        <label>
                            <input type="radio" name="gender" value="male" <?php echo (isset($gender) && $gender === 'male') ? 'checked' : ''; ?> required>
                            Male
                        </label>
                        <label>
                            <input type="radio" name="gender" value="female" <?php echo (isset($gender) && $gender === 'female') ? 'checked' : ''; ?>>
                            Female
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" id="password" name="password" required placeholder="Create a password">
                </div>
                <div class="form-group">
                    <label for="confirm_password"><i class="fas fa-lock"></i> Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm your password">
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn primary-btn">Register</button>
                </div>
            </form>
            <div class="auth-links">
                <p>Already have an account? <a href="login.php">Login</a></p>
                <p>Continue as <a href="chat.php?guest=true">Guest</a> (20 free messages)</p>
            </div>
        </div>
    </div>
</body>
</html>