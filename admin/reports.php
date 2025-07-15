<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
checkRole(['admin']);
require_once '../includes/functions.php';
// Include export libraries
require_once '../vendor/autoload.php'; // Assuming you've installed required libraries via Composer
use Mpdf\Mpdf;

$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');

// Handle export requests
if (isset($_GET['action']) && $_GET['action'] == 'export') {
    $financialReport = getFinancialReport($startDate, $endDate);
    $totalRevenue = array_sum(array_column($financialReport, 'total_revenue'));
    
    $exportType = $_GET['type'] ?? 'excel';
    
    switch ($exportType) {
        case 'pdf':
            exportToPDF($financialReport, $startDate, $endDate, $totalRevenue);
            break;
        case 'excel':
            exportToExcel($financialReport, $startDate, $endDate, $totalRevenue);
            break;
        default:
            // Default to Excel if type not specified
            exportToExcel($financialReport, $startDate, $endDate, $totalRevenue);
    }
    exit();
}

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
            <li><a href="<?php echo BASE_URL; ?>/admin/users.php">User Management</a></li>
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
            <p>LKR <?php echo number_format($totalRevenue, 2); ?></p>
        </div>
        
        <?php if (count($financialReport) > 0): ?>
            <div id="report-content">
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
                                <td>LKR <?php echo number_format($row['room_revenue'], 2); ?></td>
                                <td>LKR <?php echo number_format($row['additional_revenue'], 2); ?></td>
                                <td>LKR <?php echo number_format($row['tax'], 2); ?></td>
                                <td>LKR <?php echo number_format($row['total_revenue'], 2); ?></td>
                                <td><?php echo $row['transactions']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="report-actions">
                <div class="btn-group">
                    <button class="btn btn-secondary dropdown-toggle" type="button" id="exportDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-download"></i> Export
                    </button>
                    <div class="dropdown-menu" aria-labelledby="exportDropdown">
                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/reports.php?action=export&type=excel&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>">
                            <i class="fas fa-file-excel"></i> Excel
                        </a>
                        <a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/reports.php?action=export&type=pdf&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>">
                            <i class="fas fa-file-pdf"></i> PDF
                        </a>
                    </div>
                </div>
                <button class="btn btn-secondary" onclick="window.print()">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
        <?php else: ?>
            <p>No financial data for selected period.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

<style>
    @media print {
        .sidebar, .report-filters, .report-actions {
            display: none;
        }
        .main-content {
            width: 100%;
            padding: 0;
        }
        #report-content {
            width: 100%;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 8px;
            text-align: left;
        }
    }
</style>

<?php
// Add these functions to your includes/functions.php file

function exportToPDF($reportData, $startDate, $endDate, $totalRevenue) {
    $mpdf = new \Mpdf\Mpdf();
    
    $html = '<h1>Financial Report</h1>';
    $html .= '<p>Period: ' . date('M j, Y', strtotime($startDate)) . ' to ' . date('M j, Y', strtotime($endDate)) . '</p>';
    $html .= '<p>Total Revenue: LKR ' . number_format($totalRevenue, 2) . '</p>';
    
    $html .= '<table border="1" cellspacing="0" cellpadding="5" width="100%">';
    $html .= '<thead><tr>
                <th>Date</th>
                <th>Room Revenue</th>
                <th>Additional Revenue</th>
                <th>Tax</th>
                <th>Total Revenue</th>
                <th>Transactions</th>
              </tr></thead>';
    $html .= '<tbody>';
    
    foreach ($reportData as $row) {
        $html .= '<tr>
                    <td>' . date('M j, Y', strtotime($row['date'])) . '</td>
                    <td>LKR ' . number_format($row['room_revenue'], 2) . '</td>
                    <td>LKR ' . number_format($row['additional_revenue'], 2) . '</td>
                    <td>LKR ' . number_format($row['tax'], 2) . '</td>
                    <td>LKR ' . number_format($row['total_revenue'], 2) . '</td>
                    <td>' . $row['transactions'] . '</td>
                  </tr>';
    }
    
    $html .= '</tbody></table>';
    
    $mpdf->WriteHTML($html);
    $mpdf->Output('financial_report_' . date('Y-m-d') . '.pdf', 'D');
}

function exportToExcel($reportData, $startDate, $endDate, $totalRevenue) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="financial_report_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');
    
    echo '<table border="1">';
    echo '<tr><th colspan="6">Financial Report</th></tr>';
    echo '<tr><th colspan="6">Period: ' . date('M j, Y', strtotime($startDate)) . ' to ' . date('M j, Y', strtotime($endDate)) . '</th></tr>';
    echo '<tr><th colspan="6">Total Revenue: LKR ' . number_format($totalRevenue, 2) . '</th></tr>';
    echo '<tr>
            <th>Date</th>
            <th>Room Revenue</th>
            <th>Additional Revenue</th>
            <th>Tax</th>
            <th>Total Revenue</th>
            <th>Transactions</th>
          </tr>';
    
    foreach ($reportData as $row) {
        echo '<tr>
                <td>' . date('M j, Y', strtotime($row['date'])) . '</td>
                <td>LKR ' . number_format($row['room_revenue'], 2) . '</td>
                <td>LKR ' . number_format($row['additional_revenue'], 2) . '</td>
                <td>LKR ' . number_format($row['tax'], 2) . '</td>
                <td>LKR ' . number_format($row['total_revenue'], 2) . '</td>
                <td>' . $row['transactions'] . '</td>
              </tr>';
    }
    
    echo '</table>';
    exit();
}
?>