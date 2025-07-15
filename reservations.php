<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$roomTypes = getRoomTypes();
$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = [
        'customer_id' => isLoggedIn() ? $_SESSION['user_id'] : null,
        'check_in_date' => $_POST['check_in_date'],
        'check_out_date' => $_POST['check_out_date'],
        'adults' => $_POST['adults'],
        'children' => $_POST['children'] ?? 0,
        'room_id' => $_POST['room_id'] ?? null,
        'suite_id' => $_POST['suite_id'] ?? null,
        'credit_card_info' => $_POST['credit_card_info'] ?? null,
        'special_requests' => $_POST['special_requests'] ?? null,
        'status' => 'pending'
    ];
    
    if (isset($_POST['is_company_booking'])) {
        $data['is_company_booking'] = true;
        $data['company_id'] = $_SESSION['user_id'];
    }
    
    $result = createReservation($data);
    
    if ($result['success']) {
        $success = true;
    } else {
        $error = $result['message'];
    }
}

require_once 'includes/header.php';
?>

<div class="reservation-container">
    <h1>Make a Reservation</h1>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            Reservation created successfully! Your reservation ID is <?php echo $result['reservation_id']; ?>.
            <?php if (isLoggedIn()): ?>
                <a href="<?php echo BASE_URL; ?>/customer/reservations.php">View your reservations</a>
            <?php else: ?>
                Please <a href="<?php echo BASE_URL; ?>/login.php">login</a> to view your reservations.
            <?php endif; ?>
        </div>
    <?php else: ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
            <div class="form-row">
                <div class="form-group">
                    <label for="check_in_date">Check-in Date</label>
                    <input type="date" id="check_in_date" name="check_in_date" required 
                           min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label for="check_out_date">Check-out Date</label>
                    <input type="date" id="check_out_date" name="check_out_date" required 
                           min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
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
                <label>Room Type</label>
                <div class="room-type-selector">
                    <?php foreach ($roomTypes as $type): ?>
                        <div class="room-type" data-type-id="<?php echo $type['type_id']; ?>">
                            <img src="<?php echo BASE_URL; ?>/assets/images/rooms/<?php echo $type['image_path'] ?? 'default.jpg'; ?>" alt="<?php echo $type['name']; ?>">
                            <h4><?php echo $type['name']; ?></h4>
                            <p><?php echo $type['description']; ?></p>
                            <p class="price">$<?php echo $type['base_price']; ?> per night</p>
                            <button type="button" class="btn btn-secondary select-room">Select</button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div id="available-rooms" style="display: none;">
                <h3>Available Rooms</h3>
                <div class="available-rooms-list"></div>
            </div>
            
            <div id="residential-suites" style="display: none;">
                <h3>Residential Suites</h3>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="residential_suite" id="residential_suite">
                        Check this if you want to book a residential suite (weekly or monthly rates)
                    </label>
                </div>
                <div id="suite-options" style="display: none;">
                    <div class="form-group">
                        <label>Duration:</label>
                        <select id="suite_duration" name="suite_duration">
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>
                    <div class="available-suites-list"></div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="special_requests">Special Requests</label>
                <textarea id="special_requests" name="special_requests" rows="3"></textarea>
            </div>
            
            <div class="form-group">
                <label for="credit_card_info">Credit Card Information (optional)</label>
                <input type="text" id="credit_card_info" name="credit_card_info" 
                       placeholder="If provided, your reservation will be guaranteed">
            </div>
            
            <?php if (isLoggedIn() && $_SESSION['role'] == 'travel_company'): ?>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_company_booking" id="is_company_booking">
                        This is a company booking (discounted rate)
                    </label>
                </div>
            <?php endif; ?>
            
            <input type="hidden" id="room_id" name="room_id">
            <input type="hidden" id="suite_id" name="suite_id">
            <input type="hidden" id="selected_type_id" name="type_id">
            
            <button type="submit" class="btn btn-primary">Complete Reservation</button>
        </form>
        
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Room type selection
                const roomTypes = document.querySelectorAll('.room-type');
                const availableRoomsSection = document.getElementById('available-rooms');
                const availableRoomsList = document.querySelector('.available-rooms-list');
                const residentialSuitesSection = document.getElementById('residential-suites');
                const suiteOptions = document.getElementById('suite-options');
                const availableSuitesList = document.querySelector('.available-suites-list');
                const residentialSuiteCheckbox = document.getElementById('residential_suite');
                const roomIdInput = document.getElementById('room_id');
                const suiteIdInput = document.getElementById('suite_id');
                const selectedTypeIdInput = document.getElementById('selected_type_id');
                const checkInDate = document.getElementById('check_in_date');
                const checkOutDate = document.getElementById('check_out_date');
                
                // Handle room type selection
                roomTypes.forEach(type => {
                    type.querySelector('.select-room').addEventListener('click', function() {
                        // Remove active class from all
                        roomTypes.forEach(t => t.classList.remove('active'));
                        
                        // Add active class to selected
                        type.classList.add('active');
                        
                        const typeId = type.getAttribute('data-type-id');
                        selectedTypeIdInput.value = typeId;
                        
                        // Show available rooms section
                        availableRoomsSection.style.display = 'block';
                        residentialSuitesSection.style.display = 'block';
                        
                        // Reset selections
                        roomIdInput.value = '';
                        suiteIdInput.value = '';
                        residentialSuiteCheckbox.checked = false;
                        suiteOptions.style.display = 'none';
                        
                        // Load available rooms
                        loadAvailableRooms(typeId);
                    });
                });
                
                // Handle residential suite checkbox
                residentialSuiteCheckbox.addEventListener('change', function() {
                    if (this.checked) {
                        suiteOptions.style.display = 'block';
                        availableRoomsSection.style.display = 'none';
                        loadAvailableSuites(selectedTypeIdInput.value);
                    } else {
                        suiteOptions.style.display = 'none';
                        availableRoomsSection.style.display = 'block';
                        suiteIdInput.value = '';
                    }
                });
                
                // Handle suite duration change
                document.getElementById('suite_duration').addEventListener('change', function() {
                    if (residentialSuiteCheckbox.checked) {
                        loadAvailableSuites(selectedTypeIdInput.value);
                    }
                });
                
                // Function to load available rooms
                function loadAvailableRooms(typeId) {
                    const ciDate = checkInDate.value;
                    const coDate = checkOutDate.value;
                    
                    if (!ciDate || !coDate) {
                        alert('Please select check-in and check-out dates first');
                        return;
                    }
                    
                    fetch('<?php echo BASE_URL; ?>/includes/ajax.php?action=get_available_rooms&type_id=' + typeId + 
                          '&check_in=' + ciDate + '&check_out=' + coDate)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                availableRoomsList.innerHTML = '';
                                
                                if (data.rooms.length > 0) {
                                    data.rooms.forEach(room => {
                                        const roomElement = document.createElement('div');
                                        roomElement.className = 'available-room';
                                        roomElement.innerHTML = `
                                            <h4>Room ${room.room_number} (Floor ${room.floor})</h4>
                                            <button type="button" class="btn btn-secondary select-room-btn" 
                                                    data-room-id="${room.room_id}">
                                                Select
                                            </button>
                                        `;
                                        availableRoomsList.appendChild(roomElement);
                                        
                                        // Add event listener to select button
                                        roomElement.querySelector('.select-room-btn').addEventListener('click', function() {
                                            document.querySelectorAll('.available-room').forEach(r => {
                                                r.classList.remove('selected');
                                            });
                                            roomElement.classList.add('selected');
                                            roomIdInput.value = room.room_id;
                                            suiteIdInput.value = '';
                                            residentialSuiteCheckbox.checked = false;
                                            suiteOptions.style.display = 'none';
                                        });
                                    });
                                } else {
                                    availableRoomsList.innerHTML = '<p>No available rooms for selected dates.</p>';
                                }
                            } else {
                                alert('Error loading available rooms: ' + data.message);
                            }
                        });
                }
                
                // Function to load available suites
                function loadAvailableSuites(typeId) {
                    const ciDate = checkInDate.value;
                    const coDate = checkOutDate.value;
                    const duration = document.getElementById('suite_duration').value;
                    
                    if (!ciDate || !coDate) {
                        alert('Please select check-in and check-out dates first');
                        return;
                    }
                    
                    fetch('<?php echo BASE_URL; ?>/includes/ajax.php?action=get_available_suites&type_id=' + typeId + 
                          '&check_in=' + ciDate + '&check_out=' + coDate + '&duration=' + duration)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                availableSuitesList.innerHTML = '';
                                
                                if (data.suites.length > 0) {
                                    data.suites.forEach(suite => {
                                        const suiteElement = document.createElement('div');
                                        suiteElement.className = 'available-suite';
                                        suiteElement.innerHTML = `
                                            <h4>Suite ${suite.suite_number} (Floor ${suite.floor})</h4>
                                            <p>Rate: $${duration === 'weekly' ? suite.weekly_rate : suite.monthly_rate} per ${duration}</p>
                                            <button type="button" class="btn btn-secondary select-suite-btn" 
                                                    data-suite-id="${suite.suite_id}">
                                                Select
                                            </button>
                                        `;
                                        availableSuitesList.appendChild(suiteElement);
                                        
                                        // Add event listener to select button
                                        suiteElement.querySelector('.select-suite-btn').addEventListener('click', function() {
                                            document.querySelectorAll('.available-suite').forEach(s => {
                                                s.classList.remove('selected');
                                            });
                                            suiteElement.classList.add('selected');
                                            suiteIdInput.value = suite.suite_id;
                                            roomIdInput.value = '';
                                        });
                                    });
                                } else {
                                    availableSuitesList.innerHTML = '<p>No available suites for selected dates.</p>';
                                }
                            } else {
                                alert('Error loading available suites: ' + data.message);
                            }
                        });
                }
                
                // Reload available rooms when dates change
                checkInDate.addEventListener('change', function() {
                    if (selectedTypeIdInput.value) {
                        if (residentialSuiteCheckbox.checked) {
                            loadAvailableSuites(selectedTypeIdInput.value);
                        } else {
                            loadAvailableRooms(selectedTypeIdInput.value);
                        }
                    }
                });
                
                checkOutDate.addEventListener('change', function() {
                    if (selectedTypeIdInput.value) {
                        if (residentialSuiteCheckbox.checked) {
                            loadAvailableSuites(selectedTypeIdInput.value);
                        } else {
                            loadAvailableRooms(selectedTypeIdInput.value);
                        }
                    }
                });
            });
        </script>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>