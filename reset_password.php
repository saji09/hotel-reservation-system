<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$error = '';
$success = '';
$validToken = false;

$token = $_GET['token'] ?? '';

if ($token) {
    // Check if token is valid
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()");
    $stmt->execute([$token]);
    $resetRequest = $stmt->fetch();
    
    if ($resetRequest) {
        $validToken = true;
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $newPassword = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];
            
            if ($newPassword !== $confirmPassword) {
                $error = "Passwords do not match";
            } elseif (strlen($newPassword) < 8) {
                $error = "Password must be at least 8 characters long";
            } else {
                // Update password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
                $stmt->execute([$hashedPassword, $resetRequest['email']]);
                
                // Delete used token
                $stmt = $pdo->prepare("DELETE FROM password_resets WHERE token = ?");
                $stmt->execute([$token]);
                
                $success = "Password updated successfully! You can now <a href='" . BASE_URL . "/login.php'>login</a> with your new password.";
                $validToken = false; // Token is now used
            }
        }
    } else {
        $error = "Invalid or expired password reset link";
    }
} else {
    $error = "No password reset token provided";
}

require_once 'includes/header.php';
?>

<div class="login-container">
    <div class="login-box">
        <h2>Set New Password</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if ($validToken): ?>
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?token=' . $token; ?>" method="post">
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required minlength="8">
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                </div>
                
                <button type="submit" class="btn btn-primary">Update Password</button>
            </form>
        <?php endif; ?>
        
        <div class="login-links">
            <a href="<?php echo BASE_URL; ?>/login.php">Back to Login</a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>