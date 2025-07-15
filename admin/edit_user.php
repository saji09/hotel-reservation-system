<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
checkRole(['admin']);
require_once '../includes/functions.php';

$userId = $_GET['id'] ?? 0;

// Get user details
$stmt = $pdo->prepare("
    SELECT u.*, 
           c.credit_card_info, c.loyalty_points,
           tc.company_name, tc.discount_rate, tc.billing_address
    FROM users u
    LEFT JOIN customers c ON u.user_id = c.customer_id
    LEFT JOIN travel_companies tc ON u.user_id = tc.company_id
    WHERE u.user_id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: users.php?error=User not found");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $userData = [
        'email' => trim($_POST['email']),
        'first_name' => trim($_POST['first_name']),
        'last_name' => trim($_POST['last_name']),
        'phone' => trim($_POST['phone']),
        'address' => trim($_POST['address']),
        'company_name' => trim($_POST['company_name'] ?? ''),
        'discount_rate' => $_POST['discount_rate'] ?? 0.10,
        'credit_card_info' => trim($_POST['credit_card_info'] ?? '')
    ];

    if (updateUser($pdo, $userId, $userData)) {
        header("Location: users.php?success=User updated successfully");
        exit();
    } else {
        $error = "Failed to update user";
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
            <li><a href="<?php echo BASE_URL; ?>/admin/users.php" class="active">User Management</a></li>
        </ul>
    </div>
    <div class="main-content">
        <h1>Edit User: <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h1>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="post">
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name*</label>
                    <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name*</label>
                    <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email*</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="address">Address</label>
                <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($user['address']); ?></textarea>
            </div>
            
            <?php if ($user['role'] == 'customer'): ?>
                <div class="form-group">
                    <label for="credit_card_info">Credit Card Info</label>
                    <input type="text" id="credit_card_info" name="credit_card_info" 
                           value="<?php echo htmlspecialchars($user['credit_card_info'] ?? ''); ?>">
                </div>
            <?php elseif ($user['role'] == 'travel_company'): ?>
                <div class="form-group">
                    <label for="company_name">Company Name*</label>
                    <input type="text" id="company_name" name="company_name" 
                           value="<?php echo htmlspecialchars($user['company_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="discount_rate">Discount Rate</label>
                    <input type="number" id="discount_rate" name="discount_rate" min="0" max="1" step="0.01" 
                           value="<?php echo htmlspecialchars($user['discount_rate']); ?>">
                </div>
            <?php endif; ?>
            
            <input type="hidden" name="update_user">
            <button type="submit" class="btn btn-primary">Update User</button>
            <a href="users.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>