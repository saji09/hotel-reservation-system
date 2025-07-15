<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
checkRole(['admin']);
require_once '../includes/functions.php';

$reservationId = $_GET['id'] ?? 0;

// Get reservation details
$stmt = $pdo->prepare("SELECT r.*, 
                      u.first_name, u.last_name, u.email, u.phone,
                      rt.name as room_type, rt.base_price,
                      rm.room_number, rm.floor as room_floor,
                      rs.suite_number, rs.floor as suite_floor,
                      b.*,
                      tc.company_name
                      FROM reservations r
                      JOIN users u ON r.customer_id = u.user_id
                      LEFT JOIN rooms rm ON r.room_id = rm.room_id
                      LEFT JOIN room_types rt ON rm.type_id = rt.type_id
                      LEFT JOIN residential_suites rs ON r.suite_id = rs.suite_id
                      LEFT JOIN billing b ON r.reservation_id = b.reservation_id
                      LEFT JOIN travel_companies tc ON r.company_id = tc.company_id
                      WHERE r.reservation_id = ?");
$stmt->execute([$reservationId]);
$reservation = $stmt->fetch();

if (!$reservation) {
    header("Location: reservations.php?error=Reservation not found");
    exit();
}

// Get additional services
$stmt = $pdo->prepare("SELECT * FROM additional_services WHERE reservation_id = ?");
$stmt->execute([$reservationId]);
$additionalServices = $stmt->fetchAll();

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
        </ul>
    </div>
    <div class="main-content">
        <h1>Reservation Details</h1>
        
        <div class="reservation-details">
            <div class="detail-section">
                <h2>Guest Information</h2>
                <div class="detail-row">
                    <span class="detail-label">Name:</span>
                    <span class="detail-value"><?php echo $reservation['first_name'] . ' ' . $reservation['last_name']; ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value"><?php echo $reservation['email']; ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Phone:</span>
                    <span class="detail-value"><?php echo $reservation['phone']; ?></span>
                </div>
                <?php if ($reservation['company_name']): ?>
                <div class="detail-row">
                    <span class="detail-label">Company Booking:</span>
                    <span class="detail-value"><?php echo $reservation['company_name']; ?></span>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="detail-section">
                <h2>Reservation Information</h2>
                <div class="detail-row">
                    <span class="detail-label">Reservation ID:</span>
                    <span class="detail-value"><?php echo $reservation['reservation_id']; ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value"><?php echo ucfirst(str_replace('_', ' ', $reservation['status'])); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Check-in Date:</span>
                    <span class="detail-value"><?php echo date('M j, Y', strtotime($reservation['check_in_date'])); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Check-out Date:</span>
                    <span class="detail-value"><?php echo date('M j, Y', strtotime($reservation['check_out_date'])); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Adults:</span>
                    <span class="detail-value"><?php echo $reservation['adults']; ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Children:</span>
                    <span class="detail-value"><?php echo $reservation['children']; ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Room Type:</span>
                    <span class="detail-value"><?php echo $reservation['room_type']; ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Room/Suite:</span>
                    <span class="detail-value">
                        <?php 
                        if ($reservation['room_number']) {
                            echo 'Room ' . $reservation['room_number'] . ' (Floor ' . $reservation['room_floor'] . ')';
                        } elseif ($reservation['suite_number']) {
                            echo 'Suite ' . $reservation['suite_number'] . ' (Floor ' . $reservation['suite_floor'] . ')';
                        } else {
                            echo '-';
                        }
                        ?>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Special Requests:</span>
                    <span class="detail-value"><?php echo $reservation['special_requests'] ?? 'None'; ?></span>
                </div>
            </div>
            
            <div class="detail-section">
                <h2>Billing Information</h2>
                <?php if ($reservation['bill_id']): ?>
                    <div class="detail-row">
                        <span class="detail-label">Room Charges:</span>
                        <span class="detail-value">LKR <?php echo number_format($reservation['room_charges'], 2); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Additional Charges:</span>
                        <span class="detail-value">LKR <?php echo number_format($reservation['additional_charges'], 2); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Tax (10%):</span>
                        <span class="detail-value">LKR <?php echo number_format($reservation['tax'], 2); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Total Amount:</span>
                        <span class="detail-value">LKR <?php echo number_format($reservation['total_amount'], 2); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Payment Method:</span>
                        <span class="detail-value"><?php echo ucfirst(str_replace('_', ' ', $reservation['payment_method'])); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Payment Status:</span>
                        <span class="detail-value"><?php echo ucfirst($reservation['payment_status']); ?></span>
                    </div>
                <?php else: ?>
                    <p>No billing information available yet.</p>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($additionalServices)): ?>
            <div class="detail-section">
                <h2>Additional Services</h2>
                <table class="services-table">
                    <thead>
                        <tr>
                            <th>Service Type</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($additionalServices as $service): ?>
                        <tr>
                            <td><?php echo ucfirst($service['service_type']); ?></td>
                            <td><?php echo $service['description'] ?? 'N/A'; ?></td>
                            <td>LKR <?php echo number_format($service['amount'], 2); ?></td>
                            <td><?php echo date('M j, Y', strtotime($service['date'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <div class="action-buttons">
                <a href="<?php echo BASE_URL; ?>/admin/reservations.php" class="btn btn-secondary">Back to Reservations</a>
                <?php if ($reservation['status'] == 'pending' || $reservation['status'] == 'confirmed'): ?>
                    <a href="<?php echo BASE_URL; ?>/admin/reservations.php?action=cancel&id=<?php echo $reservation['reservation_id']; ?>" class="btn btn-error">Cancel Reservation</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
    .reservation-details {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .detail-section {
        margin-bottom: 30px;
    }
    
    .detail-section h2 {
        margin-bottom: 15px;
        color: #0066cc;
        border-bottom: 1px solid #eee;
        padding-bottom: 8px;
    }
    
    .detail-row {
        display: flex;
        margin-bottom: 10px;
    }
    
    .detail-label {
        font-weight: 600;
        width: 180px;
        color: #555;
    }
    
    .detail-value {
        flex: 1;
    }
    
    .services-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }
    
    .services-table th, .services-table td {
        padding: 10px;
        border: 1px solid #ddd;
        text-align: left;
    }
    
    .services-table th {
        background-color: #f5f5f5;
    }
    
    .action-buttons {
        margin-top: 30px;
        display: flex;
        gap: 10px;
    }
</style>

<?php require_once '../includes/footer.php'; ?>