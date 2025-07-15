<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
checkRole(['travel_company']);
require_once '../includes/functions.php';

$roomTypes = getRoomTypes();
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = [
        'start_date' => $_POST['start_date'],
        'end_date' => $_POST['end_date'],
        'room_type_id' => $_POST['room_type_id'],
        'quantity' => $_POST['quantity']
    ];
    
    $result = createBlockBooking($_SESSION['user_id'], $data);
    
    if ($result['success']) {
        $success = true;
    } else {
        $error = $result['message'];
    }
}

// Get company's block bookings
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
            <li><a href="<?php echo BASE_URL; ?>/travel_company/dashboard.php">Dashboard</a></li>
            <li><a href="<?php echo BASE_URL; ?>/travel_company/block_bookings.php" class="active">Block Bookings</a></li>
            <li><a href="<?php echo BASE_URL; ?>/travel_company/profile.php">Profile</a></li>

        </ul>
    </div>
    <div class="main-content">
        <h1>Block Bookings</h1>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                Block booking created successfully!
            </div>
        <?php elseif ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <h2>Create New Block Booking</h2>
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
            <div class="form-row">
                <div class="form-group">
                    <label for="start_date">Start Date</label>
                    <input type="date" id="start_date" name="start_date" required 
                           min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label for="end_date">End Date</label>
                    <input type="date" id="end_date" name="end_date" required 
                           min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="room_type_id">Room Type</label>
                    <select id="room_type_id" name="room_type_id" required>
                        <option value="">Select a room type</option>
                        <?php foreach ($roomTypes as $type): ?>
                            <option value="<?php echo $type['type_id']; ?>">
                                <?php echo $type['name']; ?> ($<?php echo $type['base_price']; ?>/night)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="quantity">Number of Rooms</label>
                    <input type="number" id="quantity" name="quantity" min="3" required value="3">
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">Request Block Booking</button>
        </form>
        
        <h2>Your Block Bookings</h2>
        <?php if (count($blockBookings) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Room Type</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Quantity</th>
                        <th>Status</th>
                        <th>Created At</th>
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
                            <td><?php echo date('M j, Y', strtotime($booking['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>You have no block bookings yet.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>