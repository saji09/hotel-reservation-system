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

// Get weekly data for charts
$weeklyData = getWeeklyOccupancyData();

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
            <li><a href="<?php echo BASE_URL; ?>/admin/users.php">User Management</a></li>
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
                <p>LKR <?php echo number_format($totalRevenue, 2); ?></p>
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
        
        <!-- Charts Section -->
        <div class="chart-row">
            <div class="chart-container">
                <h2>Weekly Occupancy</h2>
                <canvas id="occupancyChart"></canvas>
            </div>
            <div class="chart-container">
                <h2>Weekly Revenue</h2>
                <canvas id="revenueChart"></canvas>
            </div>
        </div>
        
        <div class="chart-row">
            <div class="chart-container">
                <h2>Room Type Distribution</h2>
                <canvas id="roomTypeChart"></canvas>
            </div>
            <div class="chart-container">
                <h2>Reservation Status</h2>
                <canvas id="statusChart"></canvas>
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
                            <td>LKR <?php echo number_format($row['total_revenue'], 2); ?></td>
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

<!-- Add Chart.js library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // Weekly Occupancy Chart
    const occupancyCtx = document.getElementById('occupancyChart').getContext('2d');
    const occupancyChart = new Chart(occupancyCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($weeklyData, 'date')); ?>,
            datasets: [{
                label: 'Occupancy',
                data: <?php echo json_encode(array_column($weeklyData, 'occupancy')); ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.7)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Rooms'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Date'
                    }
                }
            }
        }
    });

    // Weekly Revenue Chart
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    const revenueChart = new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($weeklyData, 'date')); ?>,
            datasets: [{
                label: 'Revenue (LKR)',
                data: <?php echo json_encode(array_column($weeklyData, 'revenue')); ?>,
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 2,
                tension: 0.1,
                fill: true
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Revenue (LKR)'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Date'
                    }
                }
            }
        }
    });

    // Room Type Distribution Chart
    const roomTypeCtx = document.getElementById('roomTypeChart').getContext('2d');
    const roomTypeChart = new Chart(roomTypeCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_column($occupancyReport, 'room_type')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($occupancyReport, 'total_occupancy')); ?>,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(153, 102, 255, 0.7)',
                    'rgba(255, 159, 64, 0.7)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'right',
                }
            }
        }
    });

    // Reservation Status Chart
    const statusCtx = document.getElementById('statusChart').getContext('2d');
    const statusChart = new Chart(statusCtx, {
        type: 'pie',
        data: {
            labels: ['Pending', 'Confirmed', 'Checked In', 'Checked Out', 'Cancelled'],
            datasets: [{
                data: [
                    <?php 
                    $stmt = $pdo->query("SELECT COUNT(*) FROM reservations WHERE status = 'pending'");
                    echo $stmt->fetchColumn() . ',';
                    $stmt = $pdo->query("SELECT COUNT(*) FROM reservations WHERE status = 'confirmed'");
                    echo $stmt->fetchColumn() . ',';
                    $stmt = $pdo->query("SELECT COUNT(*) FROM reservations WHERE status = 'checked_in'");
                    echo $stmt->fetchColumn() . ',';
                    $stmt = $pdo->query("SELECT COUNT(*) FROM reservations WHERE status = 'checked_out'");
                    echo $stmt->fetchColumn() . ',';
                    $stmt = $pdo->query("SELECT COUNT(*) FROM reservations WHERE status = 'cancelled'");
                    echo $stmt->fetchColumn();
                    ?>
                ],
                backgroundColor: [
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(153, 102, 255, 0.7)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'right',
                }
            }
        }
    });
</script>

<style>
    .chart-row {
    display: flex;
    gap: 20px;
    margin-bottom: 30px;
    flex-wrap: wrap;
    max-width: 1200px;
    margin-left: auto;
    margin-right: auto;
}

    
    .chart-container {
    flex: 1;
    background: #fff;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    max-height: 350px; /* Set max height */
    overflow: hidden;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.chart-container canvas {
    max-height: 260px; /* Controls actual chart height */
    height: 260px !important;
}
    
    .chart-container h2 {
        margin-top: 0;
        font-size: 18px;
        color: #333;
    }
    
    @media (max-width: 768px) {
        .chart-row {
            flex-direction: column;
        }
    }
</style>

<?php require_once '../includes/footer.php'; ?>