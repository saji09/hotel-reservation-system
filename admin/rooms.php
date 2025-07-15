<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
checkRole(['admin']);
require_once '../includes/functions.php';

// Get all rooms and their types
$rooms = $pdo->query("SELECT r.*, rt.name as room_type 
                     FROM rooms r
                     JOIN room_types rt ON r.type_id = rt.type_id
                     ORDER BY r.room_number")->fetchAll(PDO::FETCH_ASSOC);

// Get all room types
$roomTypes = $pdo->query("SELECT * FROM room_types ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Handle room actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_room'])) {
        $roomNumber = $_POST['room_number'];
        $typeId = $_POST['type_id'];
        $floor = $_POST['floor'];
        
        try {
            $stmt = $pdo->prepare("INSERT INTO rooms (room_number, type_id, floor) VALUES (?, ?, ?)");
            $stmt->execute([$roomNumber, $typeId, $floor]);
            header("Location: rooms.php?success=Room added successfully");
            exit();
        } catch (PDOException $e) {
            header("Location: rooms.php?error=" . urlencode($e->getMessage()));
            exit();
        }
    } elseif (isset($_POST['update_room'])) {
        $roomId = $_POST['room_id'];
        $status = $_POST['status'];
        
        try {
            $stmt = $pdo->prepare("UPDATE rooms SET status = ? WHERE room_id = ?");
            $stmt->execute([$status, $roomId]);
            header("Location: rooms.php?success=Room updated successfully");
            exit();
        } catch (PDOException $e) {
            header("Location: rooms.php?error=" . urlencode($e->getMessage()));
            exit();
        }
    }
}

require_once '../includes/header.php';
?>

<div class="dashboard">
    <div class="sidebar">
        <ul>
            <li><a href="<?php echo BASE_URL; ?>/admin/dashboard.php">Dashboard</a></li>
            <li><a href="<?php echo BASE_URL; ?>/admin/reports.php">Reports</a></li>
            <li><a href="<?php echo BASE_URL; ?>/admin/rooms.php" class="active">Manage Rooms</a></li>
            <li><a href="<?php echo BASE_URL; ?>/admin/reservations.php">Reservations</a></li>
            <li><a href="<?php echo BASE_URL; ?>/admin/room_types.php">Room Types</a></li>
            <li><a href="<?php echo BASE_URL; ?>/admin/users.php">User Management</a></li>
        </ul>
    </div>
    <div class="main-content">
        <h1>Room Management</h1>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>
        
        <div class="tabs">
            <button class="tab-btn active" onclick="openTab('manage-rooms')">Manage Rooms</button>
            <button class="tab-btn" onclick="openTab('add-room')">Add New Room</button>
        </div>
        
        <div id="manage-rooms" class="tab-content active">
            <h2>Current Rooms</h2>
            <table>
                <thead>
                    <tr>
                        <th>Room Number</th>
                        <th>Room Type</th>
                        <th>Floor</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rooms as $room): ?>
                        <tr>
                            <td><?php echo $room['room_number']; ?></td>
                            <td><?php echo $room['room_type']; ?></td>
                            <td><?php echo $room['floor']; ?></td>
                            <td>
                                <form method="post" class="status-form">
                                    <input type="hidden" name="room_id" value="<?php echo $room['room_id']; ?>">
                                    <select name="status" onchange="this.form.submit()">
                                        <option value="available" <?php echo $room['status'] == 'available' ? 'selected' : ''; ?>>Available</option>
                                        <option value="occupied" <?php echo $room['status'] == 'occupied' ? 'selected' : ''; ?>>Occupied</option>
                                        <option value="maintenance" <?php echo $room['status'] == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                    </select>
                                    <input type="hidden" name="update_room">
                                </form>
                            </td>
                            <td>
                                <button class="btn btn-error" onclick="confirmDelete(<?php echo $room['room_id']; ?>)">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div id="add-room" class="tab-content">
            <h2>Add New Room</h2>
            <form method="post">
                <div class="form-group">
                    <label for="room_number">Room Number</label>
                    <input type="text" id="room_number" name="room_number" required>
                </div>
                
                <div class="form-group">
                    <label for="type_id">Room Type</label>
                    <select id="type_id" name="type_id" required>
                        <option value="">Select a room type</option>
                        <?php foreach ($roomTypes as $type): ?>
                            <option value="<?php echo $type['type_id']; ?>"><?php echo $type['name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="floor">Floor</label>
                    <input type="number" id="floor" name="floor" min="1" max="20" required>
                </div>
                
                <input type="hidden" name="add_room">
                <button type="submit" class="btn btn-primary">Add Room</button>
            </form>
        </div>
    </div>
</div>

<script>
    function openTab(tabId) {
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Deactivate all tab buttons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Activate the selected tab
        document.getElementById(tabId).classList.add('active');
        event.currentTarget.classList.add('active');
    }
    
    function confirmDelete(roomId) {
        if (confirm('Are you sure you want to delete this room? This action cannot be undone.')) {
            window.location.href = 'delete_room.php?id=' + roomId;
        }
    }
</script>

<?php require_once '../includes/footer.php'; ?>