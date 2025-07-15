<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
checkRole(['admin']);
require_once '../includes/functions.php';

// Get all reservations
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$sql = "SELECT r.*, 
            u.first_name, u.last_name,
            rt.name as room_type,
            rm.room_number,
            rs.suite_number,
            b.total_amount,
            b.payment_status
        FROM reservations r
        JOIN users u ON r.customer_id = u.user_id
        LEFT JOIN rooms rm ON r.room_id = rm.room_id
        LEFT JOIN room_types rt ON rm.type_id = rt.type_id
        LEFT JOIN residential_suites rs ON r.suite_id = rs.suite_id
        LEFT JOIN billing b ON r.reservation_id = b.reservation_id
        WHERE 1=1";

$params = [];

if (!empty($search)) {
    $sql .= " AND (u.first_name LIKE :search OR u.last_name LIKE :search OR r.reservation_id = :exact_id)";
    $params[':search'] = '%' . $search . '%';
    $params[':exact_id'] = $search;
}

if (!empty($statusFilter)) {
    $sql .= " AND r.status = :status";
    $params[':status'] = $statusFilter;
}

$sql .= " ORDER BY r.check_in_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle reservation cancellation
if (isset($_GET['action']) && $_GET['action'] == 'cancel' && isset($_GET['id'])) {
    $reservationId = $_GET['id'];
    if (cancelReservation($reservationId)) {
        header("Location: reservations.php?success=Cancelled successfully");
        exit();
    } else {
        header("Location: reservations.php?error=Failed to cancel reservation");
        exit();
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
            <li><a href="<?php echo BASE_URL; ?>/admin/reservations.php" class="active">Reservations</a></li>
            <li><a href="<?php echo BASE_URL; ?>/admin/room_types.php">Room Types</a></li>
            <li><a href="<?php echo BASE_URL; ?>/admin/users.php">User Management</a></li>
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
                        <label for="status">Status</label>
                        <select id="status" name="status" onchange="this.form.submit()">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo (isset($_GET['status']) && $_GET['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo (isset($_GET['status']) && $_GET['status'] == 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="checked_in" <?php echo (isset($_GET['status']) && $_GET['status'] == 'checked_in') ? 'selected' : ''; ?>>Checked In</option>
                            <option value="checked_out" <?php echo (isset($_GET['status']) && $_GET['status'] == 'checked_out') ? 'selected' : ''; ?>>Checked Out</option>
                            <option value="cancelled" <?php echo (isset($_GET['status']) && $_GET['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="search">Search</label>
                        <input type="text" id="search" name="search" placeholder="Guest name or ID" value="<?php echo $_GET['search'] ?? ''; ?>">
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
                    <th>Amount</th>
                    <th>Payment</th>
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
                        <td>LKR <?php echo number_format($res['total_amount'] ?? 0, 2); ?></td>
                        <td><?php echo ucfirst($res['payment_status'] ?? '-'); ?></td>
                        <td>
                            <a href="view_reservation.php?id=<?php echo $res['reservation_id']; ?>" class="btn btn-secondary">View</a>
                            <?php if ($res['status'] == 'pending' || $res['status'] == 'confirmed'): ?>
                                <a href="reservations.php?action=cancel&id=<?php echo $res['reservation_id']; ?>" class="btn btn-error">Cancel</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>