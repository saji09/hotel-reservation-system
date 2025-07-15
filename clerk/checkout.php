<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
checkRole(['clerk']);
require_once '../includes/functions.php';

$reservationId = $_GET['id'] ?? 0;
$error = '';
$success = false;
$billDetails = null;

// Get reservation details
$stmt = $pdo->prepare("SELECT r.*, u.first_name, u.last_name, 
                       rm.room_number, rt.name as room_type,
                       rs.suite_number
                       FROM reservations r
                       JOIN users u ON r.customer_id = u.user_id
                       LEFT JOIN rooms rm ON r.room_id = rm.room_id
                       LEFT JOIN room_types rt ON rm.type_id = rt.type_id
                       LEFT JOIN residential_suites rs ON r.suite_id = rs.suite_id
                       WHERE r.reservation_id = ?");
$stmt->execute([$reservationId]);
$reservation = $stmt->fetch();

if (!$reservation) {
    $error = 'Reservation not found';
}

// Process check-out
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $reservation) {
    $result = checkOutGuest($reservationId);
    
    if ($result['success']) {
        $success = true;
        $billDetails = $result;
    } else {
        $error = $result['message'];
    }
}

require_once '../includes/header.php';
?>

<div class="dashboard">
    <div class="sidebar">
        <ul>
            <li><a href="<?php echo BASE_URL; ?>/clerk/dashboard.php">Dashboard</a></li>
            <li><a href="<?php echo BASE_URL; ?>/clerk/checkin.php">Check In</a></li>
            <li><a href="<?php echo BASE_URL; ?>/clerk/checkout.php" class="active">Check Out</a></li>
            <li><a href="<?php echo BASE_URL; ?>/clerk/reservations.php">Reservations</a></li>
        </ul>
    </div>
    <div class="main-content">
        <h1>Check Out Guest</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($reservation): ?>
            <?php if ($success): ?>
                <div class="alert alert-success">
                    Guest checked out successfully! Total amount: $<?php echo number_format($billDetails['total_amount'], 2); ?>
                </div>
                
                <h2>Check-out Statement</h2>
                <div class="bill-details">
                    <p><strong>Guest Name:</strong> <?php echo $reservation['first_name'] . ' ' . $reservation['last_name']; ?></p>
                    <p><strong>Room:</strong> 
                        <?php echo $reservation['room_number'] ? 'Room ' . $reservation['room_number'] : 'Suite ' . $reservation['suite_number']; ?>
                        (<?php echo $reservation['room_type']; ?>)
                    </p>
                    <p><strong>Check-in Date:</strong> <?php echo date('M j, Y', strtotime($reservation['check_in_date'])); ?></p>
                    <p><strong>Check-out Date:</strong> <?php echo date('M j, Y'); ?></p>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Room Charges</td>
                                <td>$<?php echo number_format($billDetails['room_charges'], 2); ?></td>
                            </tr>
                            <tr>
                                <td>Additional Charges</td>
                                <td>$<?php echo number_format($billDetails['additional_charges'], 2); ?></td>
                            </tr>
                            <tr>
                                <td>Tax (10%)</td>
                                <td>$<?php echo number_format($billDetails['tax'], 2); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Total Amount</strong></td>
                                <td><strong>$<?php echo number_format($billDetails['total_amount'], 2); ?></strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="reservation-details">
                    <p><strong>Guest Name:</strong> <?php echo $reservation['first_name'] . ' ' . $reservation['last_name']; ?></p>
                    <p><strong>Room:</strong> 
                        <?php echo $reservation['room_number'] ? 'Room ' . $reservation['room_number'] : 'Suite ' . $reservation['suite_number']; ?>
                        (<?php echo $reservation['room_type']; ?>)
                    </p>
                    <p><strong>Check-in Date:</strong> <?php echo date('M j, Y', strtotime($reservation['check_in_date'])); ?></p>
                    <p><strong>Check-out Date:</strong> <?php echo date('M j, Y', strtotime($reservation['check_out_date'])); ?></p>
                    
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?id=<?php echo $reservationId; ?>" method="post">
                        <div class="form-group">
                            <label>Payment Method</label>
                            <div class="radio-group">
                                <label>
                                    <input type="radio" name="payment_method" value="cash" checked> Cash
                                </label>
                                <label>
                                    <input type="radio" name="payment_method" value="credit_card"> Credit Card
                                </label>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Process Check-out</button>
                    </form>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>