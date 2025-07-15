<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
checkRole(['customer']);
require_once '../includes/header.php';

// Get user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get customer details
$stmt = $pdo->prepare("SELECT * FROM customers WHERE customer_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$customer = $stmt->fetch();

$error = '';
$success = false;
$passwordError = '';
$passwordSuccess = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle profile update
    if (isset($_POST['update_profile'])) {
        $data = [
            'first_name' => trim($_POST['first_name']),
            'last_name' => trim($_POST['last_name']),
            'email' => trim($_POST['email']),
            'phone' => trim($_POST['phone']),
            'address' => trim($_POST['address']),
            'credit_card_info' => trim($_POST['credit_card_info']),
            'user_id' => $_SESSION['user_id']
        ];
        
        try {
            $pdo->beginTransaction();
            
            // Update users table
            $stmt = $pdo->prepare("UPDATE users SET 
                                first_name = ?, last_name = ?, email = ?, phone = ?, address = ?
                                WHERE user_id = ?");
            $stmt->execute([
                $data['first_name'],
                $data['last_name'],
                $data['email'],
                $data['phone'],
                $data['address'],
                $data['user_id']
            ]);
            
            // Update customers table
            $stmt = $pdo->prepare("UPDATE customers SET credit_card_info = ? WHERE customer_id = ?");
            $stmt->execute([
                $data['credit_card_info'],
                $data['user_id']
            ]);
            
            // Update session
            $_SESSION['first_name'] = $data['first_name'];
            $_SESSION['last_name'] = $data['last_name'];
            
            $pdo->commit();
            $success = true;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
    
    // Handle password change
    if (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        // Verify current password
        if (!password_verify($currentPassword, $user['password'])) {
            $passwordError = "Current password is incorrect";
        } elseif ($newPassword !== $confirmPassword) {
            $passwordError = "New passwords do not match";
        } elseif (strlen($newPassword) < 8) {
            $passwordError = "Password must be at least 8 characters long";
        } else {
            try {
                // Hash new password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                
                // Update password in database
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $stmt->execute([$hashedPassword, $_SESSION['user_id']]);
                
                $passwordSuccess = true;
            } catch (Exception $e) {
                $passwordError = "Error updating password: " . $e->getMessage();
            }
        }
    }
}
?>

<div class="dashboard">
    <div class="sidebar">
        <ul>
            <li><a href="<?php echo BASE_URL; ?>/customer/dashboard.php">Dashboard</a></li>
            <li><a href="<?php echo BASE_URL; ?>/customer/reservations.php">My Reservations</a></li>
            <li><a href="<?php echo BASE_URL; ?>/customer/profile.php" class="active">Profile</a></li>
            <li><a href="<?php echo BASE_URL; ?>/reservations.php">Book a Room</a></li>
        </ul>
    </div>
    <div class="main-content">
        <h1>My Profile</h1>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                Profile updated successfully!
            </div>
        <?php elseif ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="profile-sections">
            <!-- Profile Information Section -->
            <div class="profile-section">
                <h2>Profile Information</h2>
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($user['address']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="credit_card_info">Credit Card Information</label>
                        <input type="text" id="credit_card_info" name="credit_card_info" 
                            value="<?php echo htmlspecialchars($customer['credit_card_info'] ?? ''); ?>"
                            placeholder="Optional - used to guarantee reservations">
                    </div>
                    
                    <input type="hidden" name="update_profile" value="1">
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </form>
            </div>
            
            <!-- Change Password Section -->
            <div class="profile-section">
                <h2>Change Password</h2>
                
                <?php if ($passwordSuccess): ?>
                    <div class="alert alert-success">
                        Password changed successfully!
                    </div>
                <?php elseif ($passwordError): ?>
                    <div class="alert alert-error"><?php echo $passwordError; ?></div>
                <?php endif; ?>
                
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" required>
                        <small class="form-text">Password must be at least 8 characters long</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <input type="hidden" name="change_password" value="1">
                    <button type="submit" class="btn btn-primary">Change Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .profile-sections {
        display: flex;
        flex-wrap: wrap;
        gap: 30px;
    }
    
    .profile-section {
        flex: 1;
        min-width: 300px;
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .profile-section h2 {
        margin-bottom: 20px;
        color: #0066cc;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
    }
    
    .form-text {
        color: #666;
        font-size: 0.85em;
        display: block;
        margin-top: 5px;
    }
</style>

<?php require_once '../includes/footer.php'; ?>