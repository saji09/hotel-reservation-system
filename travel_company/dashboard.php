<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
checkRole(['travel_company']);
require_once '../includes/functions.php';

// Get company details
$stmt = $pdo->prepare("SELECT tc.*, u.email, u.phone 
                      FROM travel_companies tc
                      JOIN users u ON tc.company_id = u.user_id
                      WHERE tc.company_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);

// Get block bookings
$stmt = $pdo->prepare("SELECT bb.*, rt.name as room_type
                      FROM block_bookings bb
                      JOIN room_types rt ON bb.room_type_id = rt.type_id
                      WHERE bb.company_id = ?
                      ORDER BY bb.start_date DESC");
$stmt->execute([$_SESSION['user_id']]);
$blockBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>

<div class="dashboard">
    <div class="sidebar">
        <ul>
            <li><a href="<?php echo BASE_URL; ?>/travel_company/dashboard.php" class="active">Dashboard</a></li>
            <li><a href="<?php echo BASE_URL; ?>/travel_company/block_bookings.php">Block Bookings</a></li>
            <li><a href="<?php echo BASE_URL; ?>/travel_company/profile.php">Profile</a></li>
        </ul>
    </div>
    <div class="main-content">
        <?php if ($company): ?>
            <h1>Welcome Travel Dashboard, <?php echo $company['company_name']; ?></h1>
            <p>Here you can manage your block bookings and reservations.</p>

            <div class="company-details">
                <h2>Company Information</h2>
                <p><strong>Contact Email:</strong> <?php echo $company['email']; ?></p>
                <p><strong>Phone:</strong> <?php echo $company['phone']; ?></p>
                <p><strong>Discount Rate:</strong> <?php echo ($company['discount_rate'] * 100); ?>%</p>
                <p><strong>Billing Address:</strong> <?php echo nl2br($company['billing_address']); ?></p>
            </div>
        <?php else: ?>
            <h1>Welcome Travel Dashboard</h1>
            <p>Company information could not be found. Please contact support.</p>
        <?php endif; ?>

        <h2>Recent Block Bookings</h2>
        <?php if (count($blockBookings) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Room Type</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Quantity</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($blockBookings as $booking): ?>
                        <tr>
                            <td><?php echo $booking['room_type']; ?></td>
                            <td><?php echo date('M j, Y', strtotime($booking['start_date'])); ?></td>
                            <td><?php echo date('M j, Y', strtotime($booking['end_date'])); ?></td>
                            <td><?php echo $booking['quantity']; ?></td>
                            <td><?php echo ucfirst($booking['status']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No block bookings yet. <a href="<?php echo BASE_URL; ?>/travel_company/block_bookings.php">Create one now!</a></p>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
