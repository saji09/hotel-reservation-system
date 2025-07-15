<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
checkRole(['admin']);
require_once '../includes/functions.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_user'])) {
        $userData = [
            'username' => trim($_POST['username']),
            'password' => $_POST['password'],
            'email' => trim($_POST['email']),
            'role' => $_POST['role'],
            'first_name' => trim($_POST['first_name']),
            'last_name' => trim($_POST['last_name']),
            'phone' => trim($_POST['phone']),
            'address' => trim($_POST['address']),
            'company_name' => trim($_POST['company_name'] ?? ''),
            'discount_rate' => $_POST['discount_rate'] ?? 0.10,
            'credit_card_info' => trim($_POST['credit_card_info'] ?? '')
        ];

        if (createUser($pdo, $userData)) {
            header("Location: users.php?success=User created successfully");
            exit();
        } else {
            $error = "Failed to create user";
        }
    } elseif (isset($_POST['update_user'])) {
        $userId = $_POST['user_id'];
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
}

// Handle user deletion
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    if (deleteUser($pdo, $_GET['id'])) {
        header("Location: users.php?success=User deleted successfully");
        exit();
    } else {
        $error = "Failed to delete user";
    }
}

// Get all users with their role-specific data
$stmt = $pdo->query("
    SELECT u.*, 
           c.credit_card_info, c.loyalty_points,
           tc.company_name, tc.discount_rate, tc.billing_address
    FROM users u
    LEFT JOIN customers c ON u.user_id = c.customer_id
    LEFT JOIN travel_companies tc ON u.user_id = tc.company_id
    ORDER BY u.role, u.last_name, u.first_name
");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        <h1>User Management</h1>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div id="manage-users" class="tab-content active">
            <h2>Current Users</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Details</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['user_id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?></td>
                            <td>
                                <?php if ($user['role'] == 'customer'): ?>
                                    Loyalty: <?php echo $user['loyalty_points']; ?> pts
                                <?php elseif ($user['role'] == 'travel_company'): ?>
                                    Discount: <?php echo ($user['discount_rate'] * 100); ?>%
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="edit_user.php?id=<?php echo $user['user_id']; ?>" class="btn btn-secondary">Edit</a>
                                <a href="users.php?action=delete&id=<?php echo $user['user_id']; ?>" 
                                   class="btn btn-error" 
                                   onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div id="add-user" class="tab-content">
            <h2>Add New User</h2>
            <form method="post" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label for="username">Username*</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password*</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name*</label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name*</label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email*</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="role">Role*</label>
                    <select id="role" name="role" required onchange="toggleRoleFields()">
                        <option value="">Select Role</option>
                        <option value="admin">Admin</option>
                        <option value="clerk">Clerk</option>
                        <option value="customer">Customer</option>
                        <option value="travel_company">Travel Company</option>
                    </select>
                </div>
                
                <div id="customer-fields" style="display: none;">
                    <div class="form-group">
                        <label for="credit_card_info">Credit Card Info</label>
                        <input type="text" id="credit_card_info" name="credit_card_info">
                    </div>
                </div>
                
                <div id="company-fields" style="display: none;">
                    <div class="form-group">
                        <label for="company_name">Company Name*</label>
                        <input type="text" id="company_name" name="company_name">
                    </div>
                    <div class="form-group">
                        <label for="discount_rate">Discount Rate</label>
                        <input type="number" id="discount_rate" name="discount_rate" min="0" max="1" step="0.01" value="0.10">
                    </div>
                </div>
                
                <input type="hidden" name="add_user">
                <button type="submit" class="btn btn-primary">Create User</button>
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
    
    function toggleRoleFields() {
        const role = document.getElementById('role').value;
        
        // Hide all role-specific fields first
        document.getElementById('customer-fields').style.display = 'none';
        document.getElementById('company-fields').style.display = 'none';
        
        // Show relevant fields based on role
        if (role === 'customer') {
            document.getElementById('customer-fields').style.display = 'block';
        } else if (role === 'travel_company') {
            document.getElementById('company-fields').style.display = 'block';
            document.getElementById('company_name').required = true;
        }
    }
</script>

<?php require_once '../includes/footer.php'; ?>