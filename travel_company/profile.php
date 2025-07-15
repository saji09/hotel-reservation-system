<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
checkRole(['travel_company']);
require_once '../includes/functions.php';

// Get company details
$stmt = $pdo->prepare("SELECT u.*, tc.* FROM users u 
                      JOIN travel_companies tc ON u.user_id = tc.company_id
                      WHERE u.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$company = $stmt->fetch();

$error = '';
$success = '';
$passwordError = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $data = [
        'company_name' => trim($_POST['company_name']),
        'email' => trim($_POST['email']),
        'phone' => trim($_POST['phone']),
        'address' => trim($_POST['address']),
        'billing_address' => trim($_POST['billing_address']),
        'discount_rate' => floatval($_POST['discount_rate']),
        'user_id' => $_SESSION['user_id']
    ];

    try {
        $pdo->beginTransaction();
        
        // Update users table
        $stmt = $pdo->prepare("UPDATE users SET 
                              email = ?, phone = ?, address = ?
                              WHERE user_id = ?");
        $stmt->execute([
            $data['email'],
            $data['phone'],
            $data['address'],
            $data['user_id']
        ]);
        
        // Update travel_companies table
        $stmt = $pdo->prepare("UPDATE travel_companies SET 
                              company_name = ?, billing_address = ?, discount_rate = ?
                              WHERE company_id = ?");
        $stmt->execute([
            $data['company_name'],
            $data['billing_address'],
            $data['discount_rate'],
            $data['user_id']
        ]);
        
        // Update session
        $_SESSION['email'] = $data['email'];
        
        $pdo->commit();
        $success = 'Profile updated successfully!';
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = 'Error updating profile: ' . $e->getMessage();
    }
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Verify current password
    $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!password_verify($currentPassword, $user['password'])) {
        $passwordError = 'Current password is incorrect';
    } elseif ($newPassword !== $confirmPassword) {
        $passwordError = 'New passwords do not match';
    } elseif (strlen($newPassword) < 8) {
        $passwordError = 'Password must be at least 8 characters';
    } else {
        // Update password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $stmt->execute([$hashedPassword, $_SESSION['user_id']]);
        
        $success = 'Password changed successfully!';
    }
}

require_once '../includes/header.php';
?>

<div class="dashboard">
    <div class="sidebar">
        <ul>
            <li><a href="<?php echo BASE_URL; ?>/travel_company/dashboard.php">Dashboard</a></li>
            <li><a href="<?php echo BASE_URL; ?>/travel_company/block_bookings.php">Block Bookings</a></li>
            <li><a href="<?php echo BASE_URL; ?>/travel_company/profile.php" class="active">Profile</a></li>
        </ul>
    </div>
    <div class="main-content">
        <h1>Company Profile</h1>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="tabs">
            <button class="tab-btn active" onclick="openTab('profile')">Profile Information</button>
            <button class="tab-btn" onclick="openTab('password')">Change Password</button>
        </div>
        
        <div id="profile" class="tab-content active">
            <form method="post">
                <div class="form-group">
                    <label for="company_name">Company Name</label>
                    <input type="text" id="company_name" name="company_name" 
                           value="<?php echo htmlspecialchars($company['company_name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($company['email']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="tel" id="phone" name="phone" 
                           value="<?php echo htmlspecialchars($company['phone']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($company['address']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="billing_address">Billing Address</label>
                    <textarea id="billing_address" name="billing_address" rows="3"><?php echo htmlspecialchars($company['billing_address']); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="discount_rate">Discount Rate (%)</label>
                    <input type="number" id="discount_rate" name="discount_rate" step="0.01" min="0" max="50"
                           value="<?php echo htmlspecialchars($company['discount_rate'] * 100); ?>" required>
                </div>
                
                <input type="hidden" name="update_profile">
                <button type="submit" class="btn btn-primary">Update Profile</button>
            </form>
        </div>
        
        <div id="password" class="tab-content">
            <?php if ($passwordError): ?>
                <div class="alert alert-error"><?php echo $passwordError; ?></div>
            <?php endif; ?>
            
            <form method="post">
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required>
                    <small class="form-text">Minimum 8 characters</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <input type="hidden" name="reset_password">
                <button type="submit" class="btn btn-primary">Change Password</button>
            </form>
        </div>
    </div>
</div>

<script>
    function openTab(tabId) {
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Deactivate all tab buttons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Activate the selected tab
        document.getElementById(tabId).classList.add('active');
        event.currentTarget.classList.add('active');
    }
</script>

<style>
    .tabs {
        margin: 20px 0;
        border-bottom: 1px solid #ddd;
    }
    
    .tab-btn {
        padding: 10px 20px;
        background: none;
        border: none;
        border-bottom: 3px solid transparent;
        cursor: pointer;
        font-size: 16px;
    }
    
    .tab-btn.active {
        border-bottom-color: #0066cc;
        font-weight: bold;
    }
    
    .tab-content {
        display: none;
        padding: 20px 0;
    }
    
    .tab-content.active {
        display: block;
    }
    
    .form-text {
        color: #666;
        font-size: 0.9em;
    }
</style>

<?php require_once '../includes/footer.php'; ?>