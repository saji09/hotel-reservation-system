<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header("Location: " . BASE_URL . "/" . $_SESSION['role'] . "/dashboard.php");
    exit();
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = [
        'username' => trim($_POST['username']),
        'password' => trim($_POST['password']),
        'email' => trim($_POST['email']),
        'first_name' => trim($_POST['first_name']),
        'last_name' => trim($_POST['last_name']),
        'phone' => trim($_POST['phone']),
        'address' => trim($_POST['address']),
        'role' => 'customer' // Default role
    ];
    
    // Additional fields for travel company
    if (isset($_POST['role']) && $_POST['role'] == 'travel_company') {
        $data['role'] = 'travel_company';
        $data['company_name'] = trim($_POST['company_name']);
        $data['billing_address'] = trim($_POST['billing_address']);
    }
    
    $result = registerUser($data);
    
    if ($result['success']) {
        $success = true;
    } else {
        $error = $result['message'];
    }
}

require_once 'includes/header.php';
?>

<div class="register-container">
    <div class="register-box">
        <h2>Create an Account</h2>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                Registration successful! <a href="<?php echo BASE_URL; ?>/login.php">Login here</a>.
            </div>
        <?php else: ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="tel" id="phone" name="phone">
                </div>
                
                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Register as:</label>
                    <div class="radio-group">
                        <label>
                            <input type="radio" name="role" value="customer" checked> Regular Customer
                        </label>
                        <label>
                            <input type="radio" name="role" value="travel_company"> Travel Company
                        </label>
                    </div>
                </div>
                
                <div id="company-fields" style="display: none;">
                    <div class="form-group">
                        <label for="company_name">Company Name</label>
                        <input type="text" id="company_name" name="company_name">
                    </div>
                    <div class="form-group">
                        <label for="billing_address">Billing Address</label>
                        <textarea id="billing_address" name="billing_address" rows="3"></textarea>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Register</button>
            </form>
            
            <div class="register-links">
                Already have an account? <a href="<?php echo BASE_URL; ?>/login.php">Login here</a>
            </div>
            
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const roleRadios = document.querySelectorAll('input[name="role"]');
                    const companyFields = document.getElementById('company-fields');
                    
                    roleRadios.forEach(radio => {
                        radio.addEventListener('change', function() {
                            if (this.value === 'travel_company') {
                                companyFields.style.display = 'block';
                                document.getElementById('company_name').required = true;
                                document.getElementById('billing_address').required = true;
                            } else {
                                companyFields.style.display = 'none';
                                document.getElementById('company_name').required = false;
                                document.getElementById('billing_address').required = false;
                            }
                        });
                    });
                });
            </script>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>