<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    
    // Check if email exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Generate reset token (valid for 1 hour)
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Store token in database
        $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$email, $token, $expires]);
        
        // Send email with reset link (in production, use a proper mailer)
        $resetLink = BASE_URL . "/reset_password.php?token=$token";
        $subject = "Password Reset Request";
        $message = "Click this link to reset your password: $resetLink\n\nThis link will expire in 1 hour.";
        
        // In development, just show the link
        $success = "Password reset link: <a href='$resetLink'>$resetLink</a>";
    } else {
        $error = "No account found with that email address";
    }
}

require_once 'includes/header.php';
?>

<div class="login-container">
    <div class="login-box">
        <h2>Reset Password</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <button type="submit" class="btn btn-primary">Send Reset Link</button>
        </form>
        
        <div class="login-links">
            <a href="<?php echo BASE_URL; ?>/login.php">Back to Login</a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>