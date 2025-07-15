<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
checkRole(['customer']);
require_once '../includes/functions.php';

$reservationId = $_GET['id'] ?? 0;
$error = '';
$success = false;

// Get reservation details
$stmt = $pdo->prepare("SELECT r.*, b.* 
                      FROM reservations r
                      LEFT JOIN billing b ON r.reservation_id = b.reservation_id
                      WHERE r.reservation_id = ? AND r.customer_id = ?");
$stmt->execute([$reservationId, $_SESSION['user_id']]);
$reservation = $stmt->fetch();

if (!$reservation) {
    header("Location: reservations.php?error=Reservation not found");
    exit();
}

// Check if already paid
if ($reservation['payment_status'] == 'paid') {
    header("Location: reservations.php?error=This reservation is already paid");
    exit();
}

// Process payment
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $paymentMethod = $_POST['payment_method'] ?? '';
    $cardNumber = $_POST['card_number'] ?? '';
    $cardExpiry = $_POST['card_expiry'] ?? '';
    $cardCvv = $_POST['card_cvv'] ?? '';
    
    // Validate payment details
    if (empty($paymentMethod)) {
        $error = "Please select a payment method";
    } elseif ($paymentMethod == 'credit_card') {
        if (empty($cardNumber) || empty($cardExpiry) || empty($cardCvv)) {
            $error = "Please enter all credit card details";
        } elseif (!preg_match('/^\d{16}$/', $cardNumber)) {
            $error = "Please enter a valid 16-digit card number";
        } elseif (!preg_match('/^\d{3,4}$/', $cardCvv)) {
            $error = "Please enter a valid CVV";
        }
    }
    
    if (empty($error)) {
        try {
            $pdo->beginTransaction();
            
            // Update billing record
            $stmt = $pdo->prepare("UPDATE billing 
                                  SET payment_method = ?, 
                                      payment_status = 'paid', 
                                      payment_date = NOW() 
                                  WHERE reservation_id = ?");
            $stmt->execute([$paymentMethod, $reservationId]);
            
            // Update reservation status if needed
            if ($reservation['status'] == 'checked_out') {
                $stmt = $pdo->prepare("UPDATE reservations SET status = 'completed' WHERE reservation_id = ?");
                $stmt->execute([$reservationId]);
            }
            
            $pdo->commit();
            $success = true;
            
            // Redirect to success page or show success message
            header("Location: reservations.php?success=Payment processed successfully");
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Payment processing failed: " . $e->getMessage();
        }
    }
}

require_once '../includes/header.php';
?>

<div class="dashboard">
    <div class="sidebar">
        <ul>
            <li><a href="<?php echo BASE_URL; ?>/customer/dashboard.php">Dashboard</a></li>
            <li><a href="<?php echo BASE_URL; ?>/customer/reservations.php">My Reservations</a></li>
            <li><a href="<?php echo BASE_URL; ?>/reservations.php">Book a Room</a></li>
            <li><a href="<?php echo BASE_URL; ?>/customer/profile.php">Profile</a></li>
        </ul>
    </div>
    <div class="main-content">
        <h1>Payment</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="payment-container">
            <div class="payment-summary">
                <h2>Payment Summary</h2>
                <div class="summary-item">
                    <span>Reservation ID:</span>
                    <span><?php echo $reservation['reservation_id']; ?></span>
                </div>
                <div class="summary-item">
                    <span>Check-in:</span>
                    <span><?php echo date('M j, Y', strtotime($reservation['check_in_date'])); ?></span>
                </div>
                <div class="summary-item">
                    <span>Check-out:</span>
                    <span><?php echo date('M j, Y', strtotime($reservation['check_out_date'])); ?></span>
                </div>
                <div class="summary-item">
                    <span>Room Charges:</span>
                    <span>LKR <?php echo number_format($reservation['room_charges'], 2); ?></span>
                </div>
                <div class="summary-item">
                    <span>Additional Charges:</span>
                    <span>LKR <?php echo number_format($reservation['additional_charges'], 2); ?></span>
                </div>
                <div class="summary-item">
                    <span>Tax (10%):</span>
                    <span>LKR <?php echo number_format($reservation['tax'], 2); ?></span>
                </div>
                <div class="summary-item total">
                    <span>Total Amount:</span>
                    <span>LKR <?php echo number_format($reservation['total_amount'], 2); ?></span>
                </div>
            </div>
            
            <div class="payment-form">
                <h2>Payment Method</h2>
                <form method="post">
                    <div class="form-group">
                        <label>Payment Method</label>
                        <div class="radio-group">
                            <label>
                                <input type="radio" name="payment_method" value="cash" <?php echo ($_POST['payment_method'] ?? '') == 'cash' ? 'checked' : ''; ?>>
                                Cash Payment (Pay at hotel)
                            </label>
                            <label>
                                <input type="radio" name="payment_method" value="credit_card" <?php echo ($_POST['payment_method'] ?? '') == 'credit_card' ? 'checked' : ''; ?>>
                                Credit Card
                            </label>
                        </div>
                    </div>
                    
                    <div id="credit-card-details" style="<?php echo ($_POST['payment_method'] ?? '') == 'credit_card' ? '' : 'display: none;'; ?>">
                        <div class="form-group">
                            <label for="card_number">Card Number</label>
                            <input type="text" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" 
                                   value="<?php echo htmlspecialchars($_POST['card_number'] ?? ''); ?>">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="card_expiry">Expiry Date</label>
                                <input type="text" id="card_expiry" name="card_expiry" placeholder="MM/YY" 
                                       value="<?php echo htmlspecialchars($_POST['card_expiry'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="card_cvv">CVV</label>
                                <input type="text" id="card_cvv" name="card_cvv" placeholder="123" 
                                       value="<?php echo htmlspecialchars($_POST['card_cvv'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Complete Payment</button>
                    <a href="reservations.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
    const creditCardDetails = document.getElementById('credit-card-details');
    
    paymentMethods.forEach(method => {
        method.addEventListener('change', function() {
            if (this.value === 'credit_card') {
                creditCardDetails.style.display = 'block';
            } else {
                creditCardDetails.style.display = 'none';
            }
        });
    });
    
    // Format card number input
    const cardNumber = document.getElementById('card_number');
    if (cardNumber) {
        cardNumber.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '')
                .replace(/(\d{4})(?=\d)/g, '$1 ');
        });
    }
    
    // Format expiry date input
    const cardExpiry = document.getElementById('card_expiry');
    if (cardExpiry) {
        cardExpiry.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '')
                .replace(/(\d{2})(?=\d)/g, '$1/')
                .substring(0, 5);
        });
    }
});
</script>

<style>
.payment-container {
    display: flex;
    gap: 30px;
    margin-top: 20px;
}

.payment-summary, .payment-form {
    flex: 1;
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.payment-summary h2, .payment-form h2 {
    margin-bottom: 20px;
    color: #0066cc;
    border-bottom: 1px solid #eee;
    padding-bottom: 10px;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    padding: 8px 0;
    border-bottom: 1px solid #f5f5f5;
}

.summary-item.total {
    font-weight: bold;
    font-size: 1.1em;
    border-top: 2px solid #0066cc;
    margin-top: 10px;
    padding-top: 15px;
}

#credit-card-details {
    margin-top: 20px;
    padding: 15px;
    background: #f9f9f9;
    border-radius: 5px;
}

.radio-group {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.radio-group label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

@media (max-width: 768px) {
    .payment-container {
        flex-direction: column;
    }
}
</style>

<?php require_once '../includes/footer.php'; ?>