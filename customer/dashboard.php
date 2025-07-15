<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
checkRole(['customer']);
require_once '../includes/header.php';
?>

<div class="dashboard">
    <div class="sidebar">
        <ul>
            <li><a href="<?php echo BASE_URL; ?>/customer/dashboard.php" class="active">Dashboard</a></li>
            <li><a href="<?php echo BASE_URL; ?>/customer/reservations.php">My Reservations</a></li>
            <li><a href="<?php echo BASE_URL; ?>/customer/profile.php">Profile</a></li>
            <li><a href="<?php echo BASE_URL; ?>/reservations.php">Book a Room</a></li>
        </ul>
    </div>
    <div class="main-content">
        <h1>Welcome Customer Dashboard, <?php echo $_SESSION['first_name']; ?></h1>
        <p>Here you can manage your reservations and profile information.</p>
        
        <h2>Upcoming Reservations</h2>
        <?php
        $reservations = getUserReservations($_SESSION['user_id']);
        $upcoming = array_filter($reservations, function($res) {
            return $res['status'] == 'confirmed' && 
                   new DateTime($res['check_in_date']) >= new DateTime('today');
        });
        
        if (count($upcoming) > 0): ?>
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
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($upcoming as $res): ?>
                        <tr>
                            <td><?php echo $res['reservation_id']; ?></td>
                            <td><?php echo $res['room_type']; ?></td>
                            <td><?php echo $res['room_number'] ?? $res['suite_number'] ?? '-'; ?></td>
                            <td><?php echo date('M j, Y', strtotime($res['check_in_date'])); ?></td>
                            <td><?php echo date('M j, Y', strtotime($res['check_out_date'])); ?></td>
                            <td><?php echo ucfirst(str_replace('_', ' ', $res['status'])); ?></td>
                            <td>$<?php echo number_format($res['total_amount'] ?? 0, 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>You have no upcoming reservations. <a href="<?php echo BASE_URL; ?>/reservations.php">Book a room now!</a></p>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>