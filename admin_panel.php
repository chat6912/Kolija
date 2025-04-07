<?php
session_start();
// Check if user is admin
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: admin_login.php");
    exit();
}

// Initialize files if they don't exist
if (!file_exists('data/questions.json')) {
    $sample_data = [
        [
            "id" => "1",
            "questions" => ["Hello", "Hi", "Hey"],
            "answer" => "Hi there! How can I help you today?",
            "answer_male" => "Hey there! How can I assist you today, sir?",
            "answer_female" => "Hello! How may I help you today, ma'am?",
            "has_image" => false,
            "image_path" => ""
        ],
        [
            "id" => "2",
            "questions" => ["What is your name?", "Who are you?"],
            "answer" => "I am KOLIJA, your friendly chatbot assistant!",
            "answer_male" => "I am KOLIJA, your friendly chatbot assistant! I'm here to help you out, bro!",
            "answer_female" => "I am KOLIJA, your friendly chatbot assistant! I'm here to help you, sister!",
            "has_image" => false,
            "image_path" => ""
        ]
    ];
    file_put_contents('data/questions.json', json_encode($sample_data, JSON_PRETTY_PRINT));
}

// Create uploads directory if not exists
if (!is_dir('uploads')) {
    mkdir('uploads', 0755, true);
}

if (!is_dir('uploads/payments')) {
    mkdir('uploads/payments', 0755, true);
}

// Load questions
$questions_data = file_get_contents('data/questions.json');
$questions = json_decode($questions_data, true) ?: [];

// Load users
$users_data = file_get_contents('data/users.json');
$users = json_decode($users_data, true) ?: [];

// Handle payment verification
if (isset($_GET['verify_payment']) && !empty($_GET['user_id'])) {
    $user_id = $_GET['user_id'];
    foreach ($users as &$user) {
        if ($user['id'] === $user_id) {
            $user['subscription_status'] = 'premium';
            $user['payment_status'] = 'verified';
            $user['is_premium'] = true;
            
            // If user had pending premium status, remove it
            if (isset($user['pending_premium'])) {
                $user['pending_premium'] = false;
            }
            
            break;
        }
    }
    file_put_contents('data/users.json', json_encode($users, JSON_PRETTY_PRINT));
    header("Location: admin_panel.php#payments");
    exit();
}

// Handle payment rejection
if (isset($_GET['reject_payment']) && !empty($_GET['user_id'])) {
    $user_id = $_GET['user_id'];
    foreach ($users as &$user) {
        if ($user['id'] === $user_id) {
            $user['payment_status'] = 'rejected';
            
            // If user had pending premium status, remove it
            if (isset($user['pending_premium'])) {
                $user['pending_premium'] = false;
            }
            
            break;
        }
    }
    file_put_contents('data/users.json', json_encode($users, JSON_PRETTY_PRINT));
    header("Location: admin_panel.php#payments");
    exit();
}

// Handle logout
if (isset($_GET['logout'])) {
    unset($_SESSION['admin']);
    header("Location: admin_login.php");
    exit();
}

// Count pending payments and premium approvals
$pending_payments = 0;
$pending_premium = 0;
foreach ($users as $user) {
    if (isset($user['payment_status']) && $user['payment_status'] === 'pending') {
        $pending_payments++;
    }
    if (isset($user['pending_premium']) && $user['pending_premium'] === true) {
        $pending_premium++;
    }
}

// Total pending notifications
$total_pending = $pending_payments + $pending_premium;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - KOLIJA</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Admin Panel Styles */
        .admin-container {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            max-width: 100%;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }
        
        .admin-header {
            background-color: var(--primary-color);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .admin-header h1 {
            margin: 0;
            font-size: 24px;
        }
        
        .admin-content {
            display: flex;
            flex: 1;
        }
        
        .admin-sidebar {
            width: 250px;
            background-color: #222;
            color: white;
            padding: 20px 0;
        }
        
        .admin-sidebar ul {
            list-style: none;
            padding: 0;
        }
        
        .admin-sidebar li {
            margin-bottom: 5px;
        }
        
        .admin-sidebar a {
            display: block;
            padding: 10px 20px;
            color: #ccc;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .admin-sidebar li.active a,
        .admin-sidebar a:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .admin-sidebar i {
            margin-right: 10px;
        }
        
        .admin-main {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
        }
        
        .admin-section {
            display: none;
        }
        
        .admin-section.active {
            display: block;
        }
        
        .admin-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .admin-card h3 {
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            min-height: 100px;
        }
        
        .form-actions {
            margin-top: 20px;
        }
        
        .search-box {
            position: relative;
            margin-bottom: 20px;
        }
        
        .search-box input {
            width: 100%;
            padding: 10px 40px 10px 15px;
            border: 1px solid #ddd;
            border-radius: 25px;
        }
        
        .search-box i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
        }
        
        .qa-item {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            position: relative;
        }
        
        .qa-content {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .qa-questions {
            flex: 1;
            min-width: 250px;
        }
        
        .qa-answer {
            flex: 2;
            min-width: 300px;
        }
        
        .qa-questions ul {
            list-style: disc;
            padding-left: 20px;
            margin-top: 5px;
        }
        
        .qa-answer h4 {
            margin-top: 10px;
            margin-bottom: 5px;
            color: var(--primary-dark);
        }
        
        .qa-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }
        
        .user-item {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .user-info {
            display: flex;
            flex-direction: column;
        }
        
        .user-name {
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-email {
            color: #666;
            margin: 5px 0 10px;
        }
        
        .user-details {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 15px;
        }
        
        .user-details p {
            margin: 0;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            color: white;
            background-color: #999;
        }
        
        .badge.premium {
            background-color: var(--primary-color);
        }
        
        .badge.free {
            background-color: #6c757d;
        }
        
        .badge.pending {
            background-color: var(--warning-color);
        }
        
        .loading {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        
        .no-records {
            text-align: center;
            padding: 20px;
            color: #666;
            font-style: italic;
        }
        
        .error-message {
            color: var(--error-color);
            padding: 10px;
            background-color: #ffebee;
            border-radius: 4px;
            margin-bottom: 10px;
        }
        
        .payment-verification-list,
        .premium-approval-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .payment-verification-item,
        .payment-history-item,
        .premium-approval-item {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .payment-user-info {
            flex: 1;
            min-width: 250px;
        }
        
        .payment-proof {
            flex: 1;
            min-width: 200px;
            max-width: 300px;
        }
        
        .payment-proof img {
            width: 100%;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        
        .payment-actions {
            display: flex;
            gap: 10px;
            align-items: flex-start;
            justify-content: flex-end;
            flex: 1;
        }
        
        .status-pending {
            color: var(--warning-color);
            font-weight: 600;
        }
        
        .status-verified {
            color: var(--success-color);
            font-weight: 600;
        }
        
        .status-rejected {
            color: var(--error-color);
            font-weight: 600;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 0;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            width: 80%;
            max-width: 800px;
            animation: modalFadeIn 0.3s;
        }
        
        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .modal-header {
            padding: 15px 20px;
            background-color: var(--primary-color);
            color: white;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h2 {
            margin: 0;
            font-size: 20px;
        }
        
        .close-modal {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .modal-body {
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <h1>KOLIJA Admin Panel</h1>
            <div class="admin-controls">
                <a href="?logout=true" class="btn danger-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </header>
        
        <div class="admin-content">
            <div class="admin-sidebar">
                <nav>
                    <ul>
                        <li class="active"><a href="#questions"><i class="fas fa-comments"></i> Manage Q&A</a></li>
                        <li><a href="#users"><i class="fas fa-users"></i> Users</a></li>
                        <li><a href="#payments"><i class="fas fa-credit-card"></i> Payments <?php if ($total_pending > 0): ?><span class="badge"><?php echo $total_pending; ?></span><?php endif; ?></a></li>
                        <li><a href="#support"><i class="fas fa-life-ring"></i> Support Tickets</a></li>
                    </ul>
                </nav>
            </div>
            
            <div class="admin-main">
                <section id="questions" class="admin-section active">
                    <h2>Manage Questions & Answers</h2>
                    
                    <div class="admin-card add-qa">
                        <h3>Add New Q&A</h3>
                        <form id="addQAForm" enctype="multipart/form-data">
                            <div class="form-group">
                                <label>Multiple Questions (one per line)</label>
                                <textarea name="questions" placeholder="Enter multiple questions, one per line" required></textarea>
                            </div>
                            <div class="form-group">
                                <label>Default Answer (for guests or unspecified gender)</label>
                                <textarea name="answer" placeholder="Enter the default answer for these questions" required></textarea>
                            </div>
                            <div class="form-group">
                                <label>Answer for Male Users</label>
                                <textarea name="answer_male" placeholder="Enter the answer for male users (optional)"></textarea>
                            </div>
                            <div class="form-group">
                                <label>Answer for Female Users</label>
                                <textarea name="answer_female" placeholder="Enter the answer for female users (optional)"></textarea>
                            </div>
                            <div class="form-group">
                                <label>Image (optional)</label>
                                <input type="file" name="image" accept="image/*">
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn primary-btn">Add Q&A</button>
                            </div>
                        </form>
                    </div>
                    
                    <div class="admin-card qa-list">
                        <h3>Existing Q&A Pairs</h3>
                        <div class="search-box">
                            <input type="text" id="searchQA" placeholder="Search questions or answers...">
                            <i class="fas fa-search"></i>
                        </div>
                        
                        <div id="qaContainer">
                            <!-- QA items will be loaded dynamically via JavaScript -->
                            <div class="loading">Loading Q&A pairs...</div>
                        </div>
                    </div>
                </section>
                
                <section id="support" class="admin-section">
                    <h2>Support Tickets</h2>
                    
                    <div class="admin-card">
                        <h3>Active Support Tickets</h3>
                        <div id="activeTicketsContainer">
                            <!-- Active tickets will be loaded dynamically via JavaScript -->
                            <div class="loading">Loading active tickets...</div>
                        </div>
                    </div>
                    
                    <div class="admin-card">
                        <h3>Closed Support Tickets</h3>
                        <div id="closedTicketsContainer">
                            <!-- Closed tickets will be loaded dynamically via JavaScript -->
                            <div class="loading">Loading closed tickets...</div>
                        </div>
                    </div>
                </section>
                
                <section id="users" class="admin-section">
                    <h2>User Management</h2>
                    
                    <div class="admin-card">
                        <h3>Registered Users</h3>
                        <div class="search-box">
                            <input type="text" id="searchUsers" placeholder="Search users...">
                            <i class="fas fa-search"></i>
                        </div>
                        
                        <div id="usersContainer">
                            <!-- Users will be loaded dynamically via AJAX -->
                            <div class="loading">Loading users...</div>
                        </div>
                    </div>
                </section>
                
                <section id="payments" class="admin-section">
                    <h2>Payment Management</h2>
                    
                    <div class="admin-card">
                        <h3>Pending Premium Approvals</h3>
                        
                        <?php if ($pending_premium === 0): ?>
                            <p class="no-records">No pending premium approvals.</p>
                        <?php else: ?>
                            <div class="premium-approval-list">
                                <?php foreach ($users as $user): ?>
                                    <?php if (isset($user['pending_premium']) && $user['pending_premium'] === true): ?>
                                        <div class="premium-approval-item">
                                            <div class="payment-user-info">
                                                <h4><?php echo htmlspecialchars($user['name']); ?> (<?php echo htmlspecialchars($user['email']); ?>)</h4>
                                                <p><strong>Gender:</strong> <?php echo htmlspecialchars($user['gender']); ?></p>
                                                <p><strong>Messages Used:</strong> <?php echo htmlspecialchars($user['messages_used'] ?? '0'); ?></p>
                                            </div>
                                            
                                            <div class="payment-actions">
                                                <a href="?verify_payment=true&user_id=<?php echo $user['id']; ?>" class="btn primary-btn"><i class="fas fa-check"></i> Approve Premium</a>
                                                <a href="?reject_payment=true&user_id=<?php echo $user['id']; ?>" class="btn danger-btn"><i class="fas fa-times"></i> Reject</a>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Payment Screenshot Verification -->
                    <div class="admin-card">
                        <h3>Pending Payments</h3>
                        
                        <?php if ($pending_payments === 0): ?>
                            <p class="no-records">No pending payments to verify.</p>
                        <?php else: ?>
                            <div class="payment-verification-list">
                                <?php foreach ($users as $user): ?>
                                    <?php if (isset($user['payment_status']) && $user['payment_status'] === 'pending'): ?>
                                        <div class="payment-verification-item">
                                            <div class="payment-user-info">
                                                <h4><?php echo htmlspecialchars($user['name']); ?> (<?php echo htmlspecialchars($user['email']); ?>)</h4>
                                                <p><strong>Date:</strong> <?php echo htmlspecialchars($user['payment_date'] ?? 'N/A'); ?></p>
                                                <p><strong>Gender:</strong> <?php echo htmlspecialchars($user['gender']); ?></p>
                                                <p><strong>Current Status:</strong> <span class="status-pending">Pending Verification</span></p>
                                            </div>
                                            
                                            <div class="payment-proof">
                                                <h4>Payment Proof:</h4>
                                                <img src="<?php echo htmlspecialchars($user['payment_proof']); ?>" alt="Payment proof">
                                            </div>
                                            
                                            <div class="payment-actions">
                                                <a href="?verify_payment=true&user_id=<?php echo $user['id']; ?>" class="btn primary-btn"><i class="fas fa-check"></i> Verify & Approve</a>
                                                <a href="?reject_payment=true&user_id=<?php echo $user['id']; ?>" class="btn danger-btn"><i class="fas fa-times"></i> Reject</a>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="admin-card">
                        <h3>Payment History</h3>
                        <div class="payment-history-list">
                            <?php 
                            $hasHistory = false;
                            foreach ($users as $user): 
                                if (isset($user['payment_status']) && ($user['payment_status'] === 'verified' || $user['payment_status'] === 'rejected')):
                                    $hasHistory = true;
                            ?>
                                <div class="payment-history-item">
                                    <div class="payment-user-info">
                                        <h4><?php echo htmlspecialchars($user['name']); ?> (<?php echo htmlspecialchars($user['email']); ?>)</h4>
                                        <p><strong>Date:</strong> <?php echo htmlspecialchars($user['payment_date'] ?? 'N/A'); ?></p>
                                        <p><strong>Status:</strong> 
                                            <?php if ($user['payment_status'] === 'verified'): ?>
                                                <span class="status-verified">Verified</span>
                                            <?php else: ?>
                                                <span class="status-rejected">Rejected</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    
                                    <?php if (isset($user['payment_proof']) && !empty($user['payment_proof'])): ?>
                                    <div class="payment-proof">
                                        <h4>Payment Proof:</h4>
                                        <img src="<?php echo htmlspecialchars($user['payment_proof']); ?>" alt="Payment proof">
                                    </div>
                                    <?php endif; ?>
                                </div>
                            <?php 
                                endif;
                            endforeach; 
                            
                            if (!$hasHistory):
                            ?>
                                <p class="no-records">No payment history available.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
    
    <!-- Edit QA Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Question & Answer</h2>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <form id="editQAForm" enctype="multipart/form-data">
                    <input type="hidden" id="editQAId" name="id">
                    <div class="form-group">
                        <label>Multiple Questions (one per line)</label>
                        <textarea id="editQuestions" name="questions" placeholder="Enter multiple questions, one per line" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Default Answer (for guests or unspecified gender)</label>
                        <textarea id="editAnswer" name="answer" placeholder="Enter the default answer for these questions" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Answer for Male Users</label>
                        <textarea id="editAnswerMale" name="answer_male" placeholder="Enter the answer for male users (optional)"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Answer for Female Users</label>
                        <textarea id="editAnswerFemale" name="answer_female" placeholder="Enter the answer for female users (optional)"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Current Image:</label>
                        <div id="currentImage"></div>
                    </div>
                    <div class="form-group">
                        <label>Replace Image (optional):</label>
                        <input type="file" name="image" accept="image/*">
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="removeImage" name="remove_image" value="1">
                            Remove current image
                        </label>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn primary-btn">Update Q&A</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/admin.js"></script>
</body>
</html>
