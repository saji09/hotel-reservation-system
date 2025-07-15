<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
checkRole(['admin']);
require_once '../includes/functions.php';

$error = '';
$success = '';
$user = [];

// Get current admin details
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    try {
        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, address = ? WHERE user_id = ?");
        $stmt->execute([$firstName, $lastName, $email, $phone, $address, $_SESSION['user_id']]);
        
        // Update session variables
        $_SESSION['first_name'] = $firstName;
        $_SESSION['last_name'] = $lastName;
        
        $success = "Profile updated successfully!";
    } catch (PDOException $e) {
        $error = "Error updating profile: " . $e->getMessage();
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    // Verify current password
    if (!password_verify($currentPassword, $user['password'])) {
        $error = "Current password is incorrect";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "New passwords do not match";
    } elseif (strlen($newPassword) < 8) {
        $error = "Password must be at least 8 characters long";
    } else {
        // Update password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $stmt->execute([$hashedPassword, $_SESSION['user_id']]);
        $success = "Password changed successfully!";
    }
}

require_once '../includes/header.php';
?>

<div class="dashboard">
    <div class="sidebar">
        <ul>
            <li><a href="<?php echo BASE_URL; ?>/admin/dashboard.php">Dashboard</a></li>
            <li><a href="<?php echo BASE_URL; ?>/admin/reports.php">Reports</a></li>
            <li><a href="<?php echo BASE_URL; ?>/admin/rooms.php">Manage Rooms</a></li>
            <li><a href="<?php echo BASE_URL; ?>/admin/reservations.php">Reservations</a></li>
            <li><a href="<?php echo BASE_URL; ?>/admin/room_types.php">Room Types</a></li>
             <li><a href="<?php echo BASE_URL; ?>/admin/users.php">User Management</a></li>
            <li><a href="<?php echo BASE_URL; ?>/admin/profile.php" class="active">Profile</a></li>
        </ul>
    </div>
    <div class="main-content">
        <h1>Admin Profile</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="profile-sections">
            <!-- Profile Information Section -->
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
                    
                    <input type="hidden" name="update_profile" value="1">
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </form>
            </div>
            
            <!-- Change Password Section -->
            <div class="profile-section">
                <h2>Change Password</h2>
                <form method="post">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" required minlength="8">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
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
        gap: 30px;
        flex-wrap: wrap;
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
        margin-top: 0;
        color: #0066cc;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
        margin-bottom: 20px;
    }
</style>

<?php require_once '../includes/footer.php'; ?>