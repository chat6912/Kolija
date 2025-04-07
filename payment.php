<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user data
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? '';

// Create uploads directory for payment proofs if it doesn't exist
if (!is_dir('uploads/payments')) {
    mkdir('uploads/payments', 0755, true);
}

// Load user data
$users_data = file_get_contents('data/users.json');
$users = json_decode($users_data, true) ?: [];

$current_user = null;
foreach ($users as $user) {
    if ($user['id'] === $user_id) {
        $current_user = $user;
        break;
    }
}

if (!$current_user) {
    header("Location: login.php");
    exit();
}

$is_premium = isset($current_user['is_premium']) && $current_user['is_premium'] === true;
$subscription_status = $current_user['subscription_status'] ?? 'free';
$payment_status = $current_user['payment_status'] ?? 'none';
$payment_proof = $current_user['payment_proof'] ?? '';
$pending_premium = isset($current_user['pending_premium']) && $current_user['pending_premium'] === true;

// Process payment proof upload
$upload_success = false;
$upload_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['payment_proof'])) {
    $file = $_FILES['payment_proof'];
    
    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $upload_error = 'File upload failed';
    } elseif ($file['size'] > 5000000) { // 5MB limit
        $upload_error = 'File is too large (max 5MB)';
    } else {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowed_types)) {
            $upload_error = 'Only JPG, PNG, and GIF files are allowed';
        } else {
            // Generate unique filename
            $filename = 'payment_' . $user_id . '_' . uniqid() . '_' . basename($file['name']);
            $filepath = 'uploads/payments/' . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Update user data
                foreach ($users as &$user) {
                    if ($user['id'] === $user_id) {
                        $user['payment_proof'] = $filepath;
                        $user['payment_status'] = 'pending';
                        $user['payment_date'] = date('Y-m-d H:i:s');
                        $user['pending_premium'] = true;
                        break;
                    }
                }
                
                file_put_contents('data/users.json', json_encode($users, JSON_PRETTY_PRINT));
                $payment_status = 'pending';
                $payment_proof = $filepath;
                $pending_premium = true;
                
                // Update session
                $_SESSION['pending_premium'] = true;
                
                $upload_success = true;
            } else {
                $upload_error = 'Failed to save the file';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - KOLIJA</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .qr-code-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 8px;
            background-color: white;
        }
        
        .payment-qr-code {
            max-width: 200px;
            margin-bottom: 10px;
        }
        
        .qr-code-instruction {
            font-size: 14px;
            color: #666;
            text-align: center;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="payment-page">
            <div class="logo">
                <h1>KOLIJA</h1>
            </div>
            
            <?php if ($is_premium): ?>
            
            <div class="payment-success">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2>Premium Activated!</h2>
                <p>You now have unlimited access to KOLIJA.</p>
                <a href="chat.php" class="btn primary-btn">Continue to Chat</a>
            </div>
            
            <?php elseif ($payment_status === 'pending' || $pending_premium): ?>
            
            <div class="payment-pending">
                <div class="pending-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <h2>Payment Verification Pending</h2>
                <p>Your payment proof has been submitted and is awaiting verification by the admin.</p>
                <p>You'll get premium access once your payment is verified.</p>
                
                <?php if ($payment_proof): ?>
                <div class="payment-proof">
                    <h3>Your Payment Proof</h3>
                    <img src="<?php echo htmlspecialchars($payment_proof); ?>" alt="Payment proof">
                </div>
                <?php endif; ?>
                
                <a href="chat.php" class="btn primary-btn">Back to Chat</a>
            </div>
            
            <?php else: ?>
            
            <div class="subscription-plans">
                <h2>Upgrade Your Experience</h2>
                <p class="subscription-intro">Get unlimited access to KOLIJA with our premium subscription</p>
                
                <div class="plan premium-plan">
                    <div class="plan-header">
                        <h3>Premium Plan</h3>
                        <div class="plan-price">
                            <span class="currency">₹</span>
                            <span class="amount">299</span>
                            <span class="period">/month</span>
                        </div>
                    </div>
                    <div class="plan-features">
                        <ul>
                            <li><i class="fas fa-check"></i> Unlimited messages</li>
                            <li><i class="fas fa-check"></i> Image sharing</li>
                            <li><i class="fas fa-check"></i> Priority support</li>
                            <li><i class="fas fa-check"></i> No ads</li>
                        </ul>
                    </div>
                </div>
                
                <div class="payment-methods">
                    <h3>Payment Methods</h3>
                    <div class="upi-payment">
                        <div class="upi-header">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/e/e1/UPI-Logo-vector.svg" alt="UPI Logo" class="upi-logo">
                            <h4>UPI Payment</h4>
                        </div>
                        
                        <div class="upi-info">
                            <p>Please send ₹299 to the following UPI ID:</p>
                            <div class="upi-id-display">kolija@upi</div>
                            
                            <div class="qr-code-container">
                                <img src="uploads/qr/GooglePay_QR.png" alt="Google Pay QR Code" class="payment-qr-code">
                                <p class="qr-code-instruction">Scan this QR code with Google Pay or any UPI app</p>
                            </div>
                            
                            <p class="payment-instruction">After completing the payment, please upload the screenshot of your payment receipt below.</p>
                        </div>
                        
                        <?php if ($upload_success): ?>
                            <div class="success-message">Payment proof uploaded successfully! Our team will verify it soon.</div>
                        <?php endif; ?>
                        
                        <?php if ($upload_error): ?>
                            <div class="error-message"><?php echo $upload_error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" enctype="multipart/form-data" class="payment-proof-form">
                            <div class="form-group">
                                <label for="payment_proof">Upload Payment Screenshot</label>
                                <input type="file" id="payment_proof" name="payment_proof" accept="image/*" required>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn primary-btn">Submit Payment Proof</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <?php endif; ?>
            
            <div class="payment-footer">
                <a href="chat.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Chat</a>
            </div>
        </div>
    </div>
</body>
</html>
