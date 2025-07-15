<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
checkRole(['admin']);
require_once '../includes/functions.php';

// Get today's occupancy
$today = date('Y-m-d');
$occupancyReport = getDailyOccupancyReport($today);
$totalOccupancy = array_sum(array_column($occupancyReport, 'total_occupancy'));
$totalRevenue = array_sum(array_column($occupancyReport, 'total_revenue'));

// Get recent reservations
$stmt = $pdo->query("SELECT r.reservation_id, r.check_in_date, r.check_out_date, 
                     u.first_name, u.last_name, rt.name as room_type,
                     rm.room_number, r.status
                     FROM reservations r
                     JOIN users u ON r.customer_id = u.user_id
                     LEFT JOIN rooms rm ON r.room_id = rm.room_id
                     LEFT JOIN room_types rt ON rm.type_id = rt.type_id
                     ORDER BY r.created_at DESC
                     LIMIT 5");
$recentReservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>

<div class="dashboard">
    <div class="sidebar">
        <ul>
            <li><a href="<?php echo BASE_URL; ?>/admin/dashboard.php" class="active">Dashboard</a></li>
            <li><a href="<?php echo BASE_URL; ?>/admin/reports.php">Reports</a></li>
            <li><a href="<?php echo BASE_URL; ?>/admin/rooms.php">Manage Rooms</a></li>
            <li><a href="<?php echo BASE_URL; ?>/admin/reservations.php">Reservations</a></li>
            <li><a href="<?php echo BASE_URL; ?>/admin/room_types.php">Room Types</a></li>
        </ul>
    </div>
    <div class="main-content">
        <h1>Admin Dashboard</h1>
        
        <div class="stats">
            <div class="stat-card">
                <h3>Today's Occupancy</h3>
                <p><?php echo $totalOccupancy; ?> Rooms</p>
            </div>
            <div class="stat-card">
                <h3>Today's Revenue</h3>
                <p>$<?php echo number_format($totalRevenue, 2); ?></p>
            </div>
            <div class="stat-card">
                <h3>Current Guests</h3>
                <p>
                    <?php 
                    $stmt = $pdo->query("SELECT COUNT(*) FROM reservations 
                                        WHERE status = 'checked_in'");
                    echo $stmt->fetchColumn();
                    ?>
                </p>
            </div>
        </div>
        
        <h2>Today's Occupancy by Room Type</h2>
        <?php if (count($occupancyReport) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Room Type</th>
                        <th>Occupancy</th>
                        <th>Current Guests</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($occupancyReport as $row): ?>
                        <tr>
                            <td><?php echo $row['room_type']; ?></td>
                            <td><?php echo $row['total_occupancy']; ?></td>
                            <td><?php echo $row['current_guests']; ?></td>
                            <td>$<?php echo number_format($row['total_revenue'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No occupancy data for today.</p>
        <?php endif; ?>
        
        <h2>Recent Reservations</h2>
        <?php if (count($recentReservations) > 0): ?>
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
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentReservations as $res): ?>
                        <tr>
                            <td><?php echo $res['reservation_id']; ?></td>
                            <td><?php echo $res['first_name'] . ' ' . $res['last_name']; ?></td>
                            <td><?php echo $res['room_type']; ?></td>
                            <td><?php echo $res['room_number'] ?? '-'; ?></td>
                            <td><?php echo date('M j', strtotime($res['check_in_date'])); ?></td>
                            <td><?php echo date('M j', strtotime($res['check_out_date'])); ?></td>
                            <td><?php echo ucfirst(str_replace('_', ' ', $res['status'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No recent reservations.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>