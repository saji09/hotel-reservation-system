<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
checkRole(['clerk']);
require_once '../includes/functions.php';

$error = '';
$success = false;

// Check if reservation ID is provided
$reservationId = $_GET['id'] ?? 0;

if ($reservationId) {
    // Get reservation details
    $stmt = $pdo->prepare("SELECT r.*, u.first_name, u.last_name, rt.name as room_type
                          FROM reservations r
                          JOIN users u ON r.customer_id = u.user_id
                          LEFT JOIN rooms rm ON r.room_id = rm.room_id
                          LEFT JOIN room_types rt ON rm.type_id = rt.type_id
                          WHERE r.reservation_id = ?");
    $stmt->execute([$reservationId]);
    $reservation = $stmt->fetch();
    
    if (!$reservation) {
        $error = 'Reservation not found';
    }
} else {
    // Handle walk-in guests
    $reservation = null;
}

// Get available rooms for today if no room assigned
if ($reservation && !$reservation['room_id'] && !$reservation['suite_id']) {
    $availableRooms = getAvailableRooms($reservation['type_id'], date('Y-m-d'), $reservation['check_out_date']);
} else {
    $availableRooms = [];
}

// Process check-in
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $reservationId = $_POST['reservation_id'] ?? 0;
    $roomId = $_POST['room_id'] ?? null;
    $suiteId = $_POST['suite_id'] ?? null;
    
    if ($reservationId) {
        // Existing reservation
        if (checkInGuest($reservationId, $roomId, $suiteId)) {
            $success = true;
        } else {
            $error = 'Failed to check in guest';
        }
    } else {
        // Walk-in guest
        $data = [
            'customer_id' => $_POST['customer_id'],
            'check_in_date' => $_POST['check_in_date'],
            'check_out_date' => $_POST['check_out_date'],
            'adults' => $_POST['adults'],
            'children' => $_POST['children'] ?? 0,
            'room_id' => $roomId,
            'suite_id' => $suiteId,
            'status' => 'checked_in'
        ];
        
        $result = createReservation($data);
        
        if ($result['success']) {
            $success = true;
            $reservationId = $result['reservation_id'];
        } else {
            $error = $result['message'];
        }
    }
}

require_once '../includes/header.php';
?>

<div class="dashboard">
    <div class="sidebar">
        <ul>
            <li><a href="<?php echo BASE_URL; ?>/clerk/dashboard.php">Dashboard</a></li>
            <li><a href="<?php echo BASE_URL; ?>/clerk/checkin.php" class="active">Check In</a></li>
            <li><a href="<?php echo BASE_URL; ?>/clerk/checkout.php">Check Out</a></li>
            <li><a href="<?php echo BASE_URL; ?>/clerk/reservations.php">Reservations</a></li>
        </ul>
    </div>
    <div class="main-content">
        <h1><?php echo $reservation ? 'Check In Guest' : 'Walk-in Guest'; ?></h1>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                Guest checked in successfully! Reservation ID: <?php echo $reservationId; ?>
            </div>
        <?php elseif ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
            <?php if ($reservation): ?>
                <input type="hidden" name="reservation_id" value="<?php echo $reservationId; ?>">
                
                <div class="form-group">
                    <label>Guest Name</label>
                    <p><?php echo $reservation['first_name'] . ' ' . $reservation['last_name']; ?></p>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Check-in Date</label>
                        <p><?php echo date('M j, Y', strtotime($reservation['check_in_date'])); ?></p>
                    </div>
                    <div class="form-group">
                        <label>Check-out Date</label>
                        <p><?php echo date('M j, Y', strtotime($reservation['check_out_date'])); ?></p>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Room Type</label>
                    <p><?php echo $reservation['room_type']; ?></p>
                </div>
                
                <?php if (!$reservation['room_id'] && !$reservation['suite_id'] && count($availableRooms) > 0): ?>
                    <div class="form-group">
                        <label>Assign Room</label>
                        <select name="room_id" required>
                            <option value="">Select a room</option>
                            <?php foreach ($availableRooms as $room): ?>
                                <option value="<?php echo $room['room_id']; ?>">
                                    Room <?php echo $room['room_number']; ?> (Floor <?php echo $room['floor']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php elseif ($reservation['room_id']): ?>
                    <div class="form-group">
                        <label>Assigned Room</label>
                        <p>Room <?php echo $reservation['room_number']; ?></p>
                    </div>
                <?php elseif ($reservation['suite_id']): ?>
                    <div class="form-group">
                        <label>Assigned Suite</label>
                        <p>Suite <?php echo $reservation['suite_number']; ?></p>
                    </div>
                <?php else: ?>
                    <div class="alert alert-error">No available rooms of this type</div>
                <?php endif; ?>
            <?php else: ?>
                <!-- Walk-in guest form -->
                <div class="form-group">
                    <label for="customer_id">Guest</label>
                    <select id="customer_id" name="customer_id" required>
                        <option value="">Select a guest</option>
                        <?php
                        $stmt = $pdo->query("SELECT user_id, first_name, last_name FROM users WHERE role = 'customer' ORDER BY last_name");
                        while ($row = $stmt->fetch()): ?>
                            <option value="<?php echo $row['user_id']; ?>">
                                <?php echo $row['first_name'] . ' ' . $row['last_name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="check_in_date">Check-in Date</label>
                        <input type="date" id="check_in_date" name="check_in_date" required 
                               value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="check_out_date">Check-out Date</label>
                        <input type="date" id="check_out_date" name="check_out_date" required 
                               value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="adults">Adults</label>
                        <select id="adults" name="adults" required>
                            <option value="1">1</option>
                            <option value="2" selected>2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="children">Children</label>
                        <select id="children" name="children">
                            <option value="0" selected>0</option>
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="room_id">Assign Room</label>
                    <select id="room_id" name="room_id" required>
                        <option value="">Select a room</option>
                        <?php
                        // Get all available rooms for today
                        $stmt = $pdo->query("SELECT r.room_id, r.room_number, r.floor, rt.name 
                                            FROM rooms r
                                            JOIN room_types rt ON r.type_id = rt.type_id
                                            WHERE r.status = 'available'
                                            ORDER BY rt.name, r.room_number");
                        while ($room = $stmt->fetch()): ?>
                            <option value="<?php echo $room['room_id']; ?>">
                                <?php echo $room['name']; ?> - Room <?php echo $room['room_number']; ?> (Floor <?php echo $room['floor']; ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            <?php endif; ?>
            
            <button type="submit" class="btn btn-primary">Complete Check-in</button>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>