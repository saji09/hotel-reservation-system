<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header("Location: " . BASE_URL . "/" . $_SESSION['role'] . "/dashboard.php");
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    
    if (login($username, $password)) {
        // Redirect handled in login function
    } else {
        $error = 'Invalid username or password';
    }
}

require_once 'includes/header.php';
?>

<div class="login-container">
    <div class="login-box">
        <h2>Login to Your Account</h2>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>
        <div class="login-links">
            <a href="<?php echo BASE_URL; ?>/register.php">Create an account</a>
            <a href="#">Forgot password?</a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>