<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
checkRole(['clerk']);
require_once '../includes/functions.php';

// Get clerk details
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$error = '';
$success = '';
$passwordError = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    try {
        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE user_id = ?");
        $stmt->execute([$firstName, $lastName, $email, $phone, $_SESSION['user_id']]);
        
        // Update session variables
        $_SESSION['first_name'] = $firstName;
        $_SESSION['last_name'] = $lastName;
        
        $success = 'Profile updated successfully!';
    } catch (PDOException $e) {
        $error = 'Error updating profile: ' . $e->getMessage();
    }
}

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    // Verify current password
    if (!password_verify($currentPassword, $user['password'])) {
        $passwordError = 'Current password is incorrect';
    } elseif ($newPassword !== $confirmPassword) {
        $passwordError = 'New passwords do not match';
    } elseif (strlen($newPassword) < 8) {
        $passwordError = 'Password must be at least 8 characters long';
    } else {
        // Update password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        try {
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $stmt->execute([$hashedPassword, $_SESSION['user_id']]);
            $success = 'Password changed successfully!';
        } catch (PDOException $e) {
            $passwordError = 'Error changing password: ' . $e->getMessage();
        }
    }
}

require_once '../includes/header.php';
?>

<div class="dashboard">
    <div class="sidebar">
        <ul>
            <li><a href="<?php echo BASE_URL; ?>/clerk/dashboard.php">Dashboard</a></li>
            <li><a href="<?php echo BASE_URL; ?>/clerk/checkin.php">Check In</a></li>
            <li><a href="<?php echo BASE_URL; ?>/clerk/checkout.php">Check Out</a></li>
            <li><a href="<?php echo BASE_URL; ?>/clerk/reservations.php">Reservations</a></li>
            <li><a href="<?php echo BASE_URL; ?>/clerk/profile.php" class="active">Profile</a></li>
        </ul>
    </div>
    <div class="main-content">
        <h1>My Profile</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="profile-section">
            <h2>Profile Information</h2>
            <form method="post">
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
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                    </div>
                </div>
                
                <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
            </form>
        </div>
        
        <div class="password-section">
            <h2>Change Password</h2>
            <?php if ($passwordError): ?>
                <div class="alert alert-error"><?php echo $passwordError; ?></div>
            <?php endif; ?>
            
            <form method="post">
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" required>
                        <small>Must be at least 8 characters long</small>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>
                
                <button type="submit" name="reset_password" class="btn btn-primary">Change Password</button>
            </form>
        </div>
    </div>
</div>

<style>
    .profile-section, .password-section {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    
    .profile-section h2, .password-section h2 {
        margin-bottom: 20px;
        color: #0066cc;
        border-bottom: 1px solid #eee;
        padding-bottom: 8px;
    }
    
    .form-row {
        display: flex;
        gap: 20px;
        margin-bottom: 15px;
    }
    
    .form-row .form-group {
        flex: 1;
    }
    
    small {
        display: block;
        margin-top: 5px;
        color: #666;
        font-size: 0.8em;
    }
</style>

<?php require_once '../includes/footer.php'; ?>