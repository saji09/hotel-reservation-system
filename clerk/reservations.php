<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
checkRole(['clerk']);
require_once '../includes/functions.php';

// Get today's and upcoming reservations
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT r.*, 
                      u.first_name, u.last_name,
                      rt.name as room_type,
                      rm.room_number,
                      rs.suite_number
                      FROM reservations r
                      JOIN users u ON r.customer_id = u.user_id
                      LEFT JOIN rooms rm ON r.room_id = rm.room_id
                      LEFT JOIN room_types rt ON rm.type_id = rt.type_id
                      LEFT JOIN residential_suites rs ON r.suite_id = rs.suite_id
                      WHERE r.check_in_date >= ? AND r.status IN ('confirmed', 'checked_in')
                      ORDER BY r.check_in_date");
$stmt->execute([$today]);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle reservation actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $reservationId = $_GET['id'];
    
    if ($_GET['action'] == 'cancel') {
        if (cancelReservation($reservationId)) {
            header("Location: reservations.php?success=Reservation cancelled successfully");
            exit();
        } else {
            header("Location: reservations.php?error=Failed to cancel reservation");
            exit();
        }
    }
}

require_once '../includes/header.php';
?>

<div class="dashboard">
    <div class="sidebar">
        <ul>
            <li><a href="<?php echo BASE_URL; ?>/clerk/dashboard.php">Dashboard</a></li>
            <li><a href="<?php echo BASE_URL; ?>/clerk/checkin.php">Check In</a></li>
            <li><a href="<?php echo BASE_URL; ?>/clerk/checkout.php">Check Out</a></li>
            <li><a href="<?php echo BASE_URL; ?>/clerk/reservations.php" class="active">Reservations</a></li>
            <li><a href="<?php echo BASE_URL; ?>/clerk/profile.php">Profile</a></li>
        </ul>
    </div>
    <div class="main-content">
        <h1>Reservation Management</h1>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>
        
        <div class="filters">
            <form method="get" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="date">Date</label>
                        <input type="date" id="date" name="date" value="<?php echo $_GET['date'] ?? $today; ?>" onchange="this.form.submit()">
                    </div>
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" onchange="this.form.submit()">
                            <option value="">All Statuses</option>
                            <option value="confirmed" <?php echo (isset($_GET['status']) && $_GET['status'] == 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="checked_in" <?php echo (isset($_GET['status']) && $_GET['status'] == 'checked_in') ? 'selected' : ''; ?>>Checked In</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Reservation ID</th>
                    <th>Guest Name</th>
                    <th>Room Type</th>
                    <th>Room Number</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reservations as $res): ?>
                    <tr>
                        <td><?php echo $res['reservation_id']; ?></td>
                        <td><?php echo $res['first_name'] . ' ' . $res['last_name']; ?></td>
                        <td><?php echo $res['room_type']; ?></td>
                        <td><?php echo $res['room_number'] ?? $res['suite_number'] ?? '-'; ?></td>
                        <td><?php echo date('M j, Y', strtotime($res['check_in_date'])); ?></td>
                        <td><?php echo date('M j, Y', strtotime($res['check_out_date'])); ?></td>
                        <td><?php echo ucfirst(str_replace('_', ' ', $res['status'])); ?></td>
                        <td>
                            <?php if ($res['status'] == 'confirmed'): ?>
                                <a href="checkin.php?id=<?php echo $res['reservation_id']; ?>" class="btn btn-primary">Check In</a>
                                <a href="reservations.php?action=cancel&id=<?php echo $res['reservation_id']; ?>" class="btn btn-error">Cancel</a>
                            <?php elseif ($res['status'] == 'checked_in'): ?>
                                <a href="checkout.php?id=<?php echo $res['reservation_id']; ?>" class="btn btn-secondary">Check Out</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>