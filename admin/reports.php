<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
checkRole(['admin']);
require_once '../includes/functions.php';

$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');

$financialReport = getFinancialReport($startDate, $endDate);
$totalRevenue = array_sum(array_column($financialReport, 'total_revenue'));

require_once '../includes/header.php';
?>

<div class="dashboard">
    <div class="sidebar">
        <ul>
            <li><a href="<?php echo BASE_URL; ?>/admin/dashboard.php">Dashboard</a></li>
            <li><a href="<?php echo BASE_URL; ?>/admin/reports.php" class="active">Reports</a></li>
            <li><a href="<?php echo BASE_URL; ?>/admin/rooms.php">Manage Rooms</a></li>
            <li><a href="<?php echo BASE_URL; ?>/admin/reservations.php">Reservations</a></li>
            <li><a href="<?php echo BASE_URL; ?>/admin/room_types.php">Room Types</a></li>
        </ul>
    </div>
    <div class="main-content">
        <h1>Financial Reports</h1>
        
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="get" class="report-filters">
            <div class="form-row">
                <div class="form-group">
                    <label for="start_date">Start Date</label>
                    <input type="date" id="start_date" name="start_date" value="<?php echo $startDate; ?>">
                </div>
                <div class="form-group">
                    <label for="end_date">End Date</label>
                    <input type="date" id="end_date" name="end_date" value="<?php echo $endDate; ?>">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Generate Report</button>
        </form>
        
        <div class="stat-card">
            <h3>Total Revenue for Period</h3>
            <p>$<?php echo number_format($totalRevenue, 2); ?></p>
        </div>
        
        <?php if (count($financialReport) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Room Revenue</th>
                        <th>Additional Revenue</th>
                        <th>Tax</th>
                        <th>Total Revenue</th>
                        <th>Transactions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($financialReport as $row): ?>
                        <tr>
                            <td><?php echo date('M j, Y', strtotime($row['date'])); ?></td>
                            <td>$<?php echo number_format($row['room_revenue'], 2); ?></td>
                            <td>$<?php echo number_format($row['additional_revenue'], 2); ?></td>
                            <td>$<?php echo number_format($row['tax'], 2); ?></td>
                            <td>$<?php echo number_format($row['total_revenue'], 2); ?></td>
                            <td><?php echo $row['transactions']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="report-actions">
                <a href="<?php echo BASE_URL; ?>/admin/reports.php?action=export&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" class="btn btn-secondary">
                    <i class="fas fa-download"></i> Export to CSV
                </a>
            </div>
        <?php else: ?>
            <p>No financial data for selected period.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>