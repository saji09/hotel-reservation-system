<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
checkRole(['customer']);
require_once '../includes/header.php';

$reservations = getUserReservations($_SESSION['user_id']);
?>

<div class="dashboard">
    <div class="sidebar">
        <ul>
            <li><a href="<?php echo BASE_URL; ?>/customer/dashboard.php">Dashboard</a></li>
            <li><a href="<?php echo BASE_URL; ?>/customer/reservations.php" class="active">My Reservations</a></li>
            <li><a href="<?php echo BASE_URL; ?>/reservations.php">Book a Room</a></li>
            <li><a href="<?php echo BASE_URL; ?>/customer/profile.php">Profile</a></li>
        </ul>
    </div>
    <div class="main-content">
        <h1>My Reservations</h1>
        
        <?php if (count($reservations) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Reservation ID</th>
                        <th>Room Type</th>
                        <th>Room Number</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Status</th>
                        <th>Amount</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservations as $res): ?>
                        <tr>
                            <td><?php echo $res['reservation_id']; ?></td>
                            <td><?php echo $res['room_type']; ?></td>
                            <td><?php echo $res['room_number'] ?? $res['suite_number'] ?? '-'; ?></td>
                            <td><?php echo date('M j, Y', strtotime($res['check_in_date'])); ?></td>
                            <td><?php echo date('M j, Y', strtotime($res['check_out_date'])); ?></td>
                            <td><?php echo ucfirst(str_replace('_', ' ', $res['status'])); ?></td>
                            <td>LKR <?php echo number_format($res['total_amount'] ?? 0, 2); ?></td>
                            <td>
                                <?php if ($res['status'] == 'pending' || $res['status'] == 'confirmed'): ?>
                                    <a href="cancel.php?id=<?php echo $res['reservation_id']; ?>" class="btn btn-secondary">Cancel</a>
                                <?php endif; ?>
                                <?php if ($res['status'] == 'checked_out' && $res['payment_status'] == 'pending'): ?>
                                    <a href="pay.php?id=<?php echo $res['reservation_id']; ?>" class="btn btn-primary">Pay Now</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>You have no reservations yet. <a href="<?php echo BASE_URL; ?>/reservations.php">Book a room now!</a></p>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>