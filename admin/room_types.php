<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
checkRole(['admin']);
require_once '../includes/functions.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_type'])) {
        // Add new room type
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $basePrice = floatval($_POST['base_price']);
        $capacity = intval($_POST['capacity']);
        
        try {
            // Handle image upload
            $imagePath = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
                $uploadDir = '../assets/images/rooms/';
                $imageName = uniqid() . '_' . basename($_FILES['image']['name']);
                $targetPath = $uploadDir . $imageName;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                    $imagePath = $imageName;
                }
            }
            
            $stmt = $pdo->prepare("INSERT INTO room_types 
                                 (name, description, base_price, capacity, image_path) 
                                 VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $description, $basePrice, $capacity, $imagePath]);
            
            header("Location: room_types.php?success=Room type added successfully");
            exit();
        } catch (PDOException $e) {
            header("Location: room_types.php?error=" . urlencode($e->getMessage()));
            exit();
        }
    } elseif (isset($_POST['update_type'])) {
        // Update existing room type
        $typeId = $_POST['type_id'];
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $basePrice = floatval($_POST['base_price']);
        $capacity = intval($_POST['capacity']);
        
        try {
            // Handle image update
            $imagePath = $_POST['current_image'];
            if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
                $uploadDir = '../assets/images/rooms/';
                $imageName = uniqid() . '_' . basename($_FILES['image']['name']);
                $targetPath = $uploadDir . $imageName;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                    // Delete old image if it exists
                    if ($imagePath && file_exists($uploadDir . $imagePath)) {
                        unlink($uploadDir . $imagePath);
                    }
                    $imagePath = $imageName;
                }
            }
            
            $stmt = $pdo->prepare("UPDATE room_types SET 
                                  name = ?, description = ?, base_price = ?, 
                                  capacity = ?, image_path = ?
                                  WHERE type_id = ?");
            $stmt->execute([$name, $description, $basePrice, $capacity, $imagePath, $typeId]);
            
            header("Location: room_types.php?success=Room type updated successfully");
            exit();
        } catch (PDOException $e) {
            header("Location: room_types.php?error=" . urlencode($e->getMessage()));
            exit();
        }
    }
}

// Handle deletion
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $typeId = $_GET['id'];
    
    try {
        // First check if any rooms are using this type
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM rooms WHERE type_id = ?");
        $stmt->execute([$typeId]);
        $roomCount = $stmt->fetchColumn();
        
        if ($roomCount > 0) {
            header("Location: room_types.php?error=Cannot delete type - rooms are assigned to it");
            exit();
        }
        
        // Get image path to delete the file
        $stmt = $pdo->prepare("SELECT image_path FROM room_types WHERE type_id = ?");
        $stmt->execute([$typeId]);
        $imagePath = $stmt->fetchColumn();
        
        // Delete the room type
        $stmt = $pdo->prepare("DELETE FROM room_types WHERE type_id = ?");
        $stmt->execute([$typeId]);
        
        // Delete the image file if it exists
        if ($imagePath) {
            $filePath = '../assets/images/rooms/' . $imagePath;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        
        header("Location: room_types.php?success=Room type deleted successfully");
        exit();
    } catch (PDOException $e) {
        header("Location: room_types.php?error=" . urlencode($e->getMessage()));
        exit();
    }
}

// Get all room types
$roomTypes = $pdo->query("SELECT * FROM room_types ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/header.php';
?>

<div class="dashboard">
    <div class="sidebar">
        <ul>
            <li><a href="<?php echo BASE_URL; ?>/admin/dashboard.php">Dashboard</a></li>
            <li><a href="<?php echo BASE_URL; ?>/admin/reports.php">Reports</a></li>
            <li><a href="<?php echo BASE_URL; ?>/admin/rooms.php">Manage Rooms</a></li>
            <li><a href="<?php echo BASE_URL; ?>/admin/reservations.php">Reservations</a></li>
            <li><a href="<?php echo BASE_URL; ?>/admin/room_types.php" class="active">Room Types</a></li>
            <li><a href="<?php echo BASE_URL; ?>/admin/users.php">User Management</a></li>
        </ul>
    </div>
    <div class="main-content">
        <h1>Room Type Management</h1>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>
        
        <div class="tabs">
            <button class="tab-btn active" onclick="openTab('manage-types')">Manage Types</button>
            <button class="tab-btn" onclick="openTab('add-type')">Add New Type</button>
        </div>
        
        <div id="manage-types" class="tab-content active">
            <h2>Current Room Types</h2>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Base Price</th>
                        <th>Capacity</th>
                        <th>Image</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($roomTypes as $type): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($type['name']); ?></td>
                            <td><?php echo htmlspecialchars($type['description']); ?></td>
                            <td>$<?php echo number_format($type['base_price'], 2); ?></td>
                            <td><?php echo $type['capacity']; ?></td>
                            <td>
                                <?php if ($type['image_path']): ?>
                                    <img src="<?php echo BASE_URL; ?>/assets/images/rooms/<?php echo htmlspecialchars($type['image_path']); ?>" 
                                         alt="<?php echo htmlspecialchars($type['name']); ?>" 
                                         style="max-width: 100px; max-height: 60px;">
                                <?php else: ?>
                                    No image
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-secondary" 
                                        onclick="openEditModal(<?php echo $type['type_id']; ?>, 
                                        '<?php echo htmlspecialchars($type['name'], ENT_QUOTES); ?>',
                                        '<?php echo htmlspecialchars($type['description'], ENT_QUOTES); ?>',
                                        <?php echo $type['base_price']; ?>,
                                        <?php echo $type['capacity']; ?>,
                                        '<?php echo htmlspecialchars($type['image_path'], ENT_QUOTES); ?>')">
                                    Edit
                                </button>
                                <a href="room_types.php?action=delete&id=<?php echo $type['type_id']; ?>" 
                                   class="btn btn-error" 
                                   onclick="return confirm('Are you sure you want to delete this room type?')">
                                    Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div id="add-type" class="tab-content">
            <h2>Add New Room Type</h2>
            <form method="post" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">Room Type Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3" required></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="base_price">Base Price (per night)</label>
                        <input type="number" id="base_price" name="base_price" min="0" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label for="capacity">Maximum Capacity</label>
                        <input type="number" id="capacity" name="capacity" min="1" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="image">Room Image</label>
                    <input type="file" id="image" name="image" accept="image/*">
                </div>
                
                <input type="hidden" name="add_type">
                <button type="submit" class="btn btn-primary">Add Room Type</button>
            </form>
        </div>
        
        <!-- Edit Modal -->
        <div id="editModal" class="modal">
            <div class="modal-content">
                <span class="close-btn" onclick="closeEditModal()">&times;</span>
                <h2>Edit Room Type</h2>
                <form id="editForm" method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="edit_name">Room Type Name</label>
                        <input type="text" id="edit_name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_description">Description</label>
                        <textarea id="edit_description" name="description" rows="3" required></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit_base_price">Base Price (per night)</label>
                            <input type="number" id="edit_base_price" name="base_price" min="0" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_capacity">Maximum Capacity</label>
                            <input type="number" id="edit_capacity" name="capacity" min="1" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Current Image</label>
                        <div id="current-image-container">
                            <img id="current-image" src="" style="max-width: 200px; display: none;">
                            <p id="no-image" style="display: none;">No image</p>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_image">New Image (leave blank to keep current)</label>
                        <input type="file" id="edit_image" name="image" accept="image/*">
                    </div>
                    
                    <input type="hidden" id="edit_type_id" name="type_id">
                    <input type="hidden" id="edit_current_image" name="current_image">
                    <input type="hidden" name="update_type">
                    <button type="submit" class="btn btn-primary">Update Room Type</button>
                </form>
            </div>
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
    
    function openEditModal(typeId, name, description, basePrice, capacity, imagePath) {
        document.getElementById('edit_type_id').value = typeId;
        document.getElementById('edit_name').value = name;
        document.getElementById('edit_description').value = description;
        document.getElementById('edit_base_price').value = basePrice;
        document.getElementById('edit_capacity').value = capacity;
        document.getElementById('edit_current_image').value = imagePath;
        
        const currentImage = document.getElementById('current-image');
        const noImage = document.getElementById('no-image');
        
        if (imagePath) {
            currentImage.src = '<?php echo BASE_URL; ?>/assets/images/rooms/' + imagePath;
            currentImage.style.display = 'block';
            noImage.style.display = 'none';
        } else {
            currentImage.style.display = 'none';
            noImage.style.display = 'block';
        }
        
        document.getElementById('editModal').style.display = 'block';
    }
    
    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }
    
    // Close modal when clicking outside of it
    window.onclick = function(event) {
        const modal = document.getElementById('editModal');
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
</script>

<style>
    /* Modal styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.4);
    }
    
    .modal-content {
        background-color: #fff;
        margin: 10% auto;
        padding: 20px;
        border-radius: 8px;
        width: 80%;
        max-width: 600px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .close-btn {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }
    
    .close-btn:hover {
        color: #333;
    }
</style>

<?php require_once '../includes/footer.php'; ?>