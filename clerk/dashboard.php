<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
checkRole(['clerk']);
require_once '../includes/header.php';

// Get today's check-ins
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT r.*, u.first_name, u.last_name 
                      FROM reservations r
                      JOIN users u ON r.customer_id = u.user_id
                      WHERE r.check_in_date = ? AND r.status = 'confirmed'");
$stmt->execute([$today]);
$todaysCheckins = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get today's check-outs
$stmt = $pdo->prepare("SELECT r.*, u.first_name, u.last_name 
                      FROM reservations r
                      JOIN users u ON r.customer_id = u.user_id
                      WHERE r.check_out_date = ? AND r.status = 'checked_in'");
$stmt->execute([$today]);
$todaysCheckouts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="dashboard">
    <div class="sidebar">
        <ul>
            <li><a href="<?php echo BASE_URL; ?>/clerk/dashboard.php" class="active">Dashboard</a></li>
            <li><a href="<?php echo BASE_URL; ?>/clerk/checkin.php">Check In</a></li>
            <li><a href="<?php echo BASE_URL; ?>/clerk/checkout.php">Check Out</a></li>
            <li><a href="<?php echo BASE_URL; ?>/clerk/reservations.php">Reservations</a></li>
        </ul>
    </div>
    <div class="main-content">
        <h1>Welcome Clerk Dashboard, <?php echo $_SESSION['first_name']; ?></h1>
        <p>Here you can manage guest check-ins and check-outs.</p>
        
        <h2>Today's Check-ins</h2>
        <?php if (count($todaysCheckins) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Reservation ID</th>
                        <th>Guest Name</th>
                        <th>Check-in Date</th>
                        <th>Check-out Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($todaysCheckins as $res): ?>
                        <tr>
                            <td><?php echo $res['reservation_id']; ?></td>
                            <td><?php echo $res['first_name'] . ' ' . $res['last_name']; ?></td>
                            <td><?php echo date('M j, Y', strtotime($res['check_in_date'])); ?></td>
                            <td><?php echo date('M j, Y', strtotime($res['check_out_date'])); ?></td>
                            <td>
                                <a href="checkin.php?id=<?php echo $res['reservation_id']; ?>" class="btn btn-primary">Check In</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No check-ins scheduled for today.</p>
        <?php endif; ?>
        
        <h2>Today's Check-outs</h2>
        <?php if (count($todaysCheckouts) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Reservation ID</th>
                        <th>Guest Name</th>
                        <th>Check-in Date</th>
                        <th>Check-out Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($todaysCheckouts as $res): ?>
                        <tr>
                            <td><?php echo $res['reservation_id']; ?></td>
                            <td><?php echo $res['first_name'] . ' ' . $res['last_name']; ?></td>
                            <td><?php echo date('M j, Y', strtotime($res['check_in_date'])); ?></td>
                            <td><?php echo date('M j, Y', strtotime($res['check_out_date'])); ?></td>
                            <td>
                                <a href="checkout.php?id=<?php echo $res['reservation_id']; ?>" class="btn btn-primary">Check Out</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No check-outs scheduled for today.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>