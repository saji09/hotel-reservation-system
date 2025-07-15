<?php
require_once 'config.php';

// Get all room types
function getRoomTypes() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM room_types");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get available rooms for a date range and type
function getAvailableRooms($typeId, $checkIn, $checkOut, $suite = false) {
    global $pdo;
    
    $table = $suite ? 'residential_suites' : 'rooms';
    $idField = $suite ? 'suite_id' : 'room_id';
    
    $query = "SELECT * FROM $table 
              WHERE type_id = ? AND status = 'available' 
              AND $idField NOT IN (
                  SELECT $idField FROM reservations 
                  WHERE status IN ('confirmed', 'checked_in') 
                  AND (
                      (check_in_date <= ? AND check_out_date >= ?) OR
                      (check_in_date <= ? AND check_out_date >= ?) OR
                      (check_in_date >= ? AND check_out_date <= ?)
                  )
              )";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$typeId, $checkOut, $checkIn, $checkIn, $checkOut, $checkIn, $checkOut]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Create a reservation
function createReservation($data) {
    global $pdo;
    
    $required = ['customer_id', 'check_in_date', 'check_out_date', 'adults'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            return ['success' => false, 'message' => "$field is required"];
        }
    }
    
    try {
        $pdo->beginTransaction();
        
        // Validate suite exists if suite_id is provided
        if (!empty($data['suite_id'])) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM residential_suites WHERE suite_id = ?");
            $stmt->execute([$data['suite_id']]);
            $suiteExists = $stmt->fetchColumn();
            
            if (!$suiteExists) {
                $pdo->rollBack();
                return ['success' => false, 'message' => 'Selected residential suite does not exist'];
            }
        }
        
        // Check if customer exists in customers table
        if ($data['customer_id']) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM customers WHERE customer_id = ?");
            $stmt->execute([$data['customer_id']]);
            $customerExists = $stmt->fetchColumn();
            
            if (!$customerExists) {
                // If customer doesn't exist in customers table, add them
                $stmt = $pdo->prepare("INSERT INTO customers (customer_id) VALUES (?)");
                $stmt->execute([$data['customer_id']]);
            }
        }
        
        $stmt = $pdo->prepare("INSERT INTO reservations 
                              (customer_id, room_id, suite_id, check_in_date, check_out_date, 
                              adults, children, status, credit_card_info, is_company_booking, company_id, special_requests)
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $data['customer_id'],
            $data['room_id'] ?? null,
            $data['suite_id'] ?? null,
            $data['check_in_date'],
            $data['check_out_date'],
            $data['adults'],
            $data['children'] ?? 0,
            $data['status'] ?? 'pending',
            $data['credit_card_info'] ?? null,
            $data['is_company_booking'] ?? false,
            $data['company_id'] ?? null,
            $data['special_requests'] ?? null
        ]);
        
        $reservationId = $pdo->lastInsertId();
        
        // Update suite status if suite was booked
        if (!empty($data['suite_id'])) {
            $stmt = $pdo->prepare("UPDATE residential_suites SET status = 'occupied' WHERE suite_id = ?");
            $stmt->execute([$data['suite_id']]);
        }
        
        $pdo->commit();
        
        return ['success' => true, 'reservation_id' => $reservationId];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
// Check in a guest
function checkInGuest($reservationId, $roomId = null, $suiteId = null) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Update reservation status
        $stmt = $pdo->prepare("UPDATE reservations SET status = 'checked_in' WHERE reservation_id = ?");
        $stmt->execute([$reservationId]);
        
        // Assign room if not already assigned
        if ($roomId) {
            $stmt = $pdo->prepare("UPDATE reservations SET room_id = ? WHERE reservation_id = ?");
            $stmt->execute([$roomId, $reservationId]);
            
            // Update room status
            $stmt = $pdo->prepare("UPDATE rooms SET status = 'occupied' WHERE room_id = ?");
            $stmt->execute([$roomId]);
        } elseif ($suiteId) {
            $stmt = $pdo->prepare("UPDATE reservations SET suite_id = ? WHERE reservation_id = ?");
            $stmt->execute([$suiteId, $reservationId]);
            
            // Update suite status
            $stmt = $pdo->prepare("UPDATE residential_suites SET status = 'occupied' WHERE suite_id = ?");
            $stmt->execute([$suiteId]);
        }
        
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

// Check out a guest
function checkOutGuest($reservationId) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Get reservation details
        $stmt = $pdo->prepare("SELECT * FROM reservations WHERE reservation_id = ?");
        $stmt->execute([$reservationId]);
        $reservation = $stmt->fetch();
        
        if (!$reservation) {
            throw new Exception("Reservation not found");
        }
        
        // Initialize default values
        $roomCharges = 0;
        $additionalCharges = 0;
        $tax = 0;
        $totalAmount = 0;
        
        // Calculate room charges
        if ($reservation['room_id']) {
            // Get room type and rate
            $stmt = $pdo->prepare("SELECT rt.base_price FROM rooms r 
                                 JOIN room_types rt ON r.type_id = rt.type_id 
                                 WHERE r.room_id = ?");
            $stmt->execute([$reservation['room_id']]);
            $room = $stmt->fetch();
            $nightlyRate = $room['base_price'];
            
            // Calculate nights
            $checkIn = new DateTime($reservation['check_in_date']);
            $checkOut = new DateTime($reservation['check_out_date']);
            $interval = $checkIn->diff($checkOut);
            $nights = $interval->days;
            
            $roomCharges = $nightlyRate * $nights;
            
            // Update room status
            $stmt = $pdo->prepare("UPDATE rooms SET status = 'available' WHERE room_id = ?");
            $stmt->execute([$reservation['room_id']]);
        } elseif ($reservation['suite_id']) {
            // Get suite rate
            $checkIn = new DateTime($reservation['check_in_date']);
            $checkOut = new DateTime($reservation['check_out_date']);
            $interval = $checkIn->diff($checkOut);
            $days = $interval->days;
            
            $stmt = $pdo->prepare("SELECT weekly_rate, monthly_rate FROM residential_suites WHERE suite_id = ?");
            $stmt->execute([$reservation['suite_id']]);
            $suite = $stmt->fetch();
            
            if ($days >= 28) { // Monthly rate
                $roomCharges = $suite['monthly_rate'];
                $nights = 1; // Count as one monthly charge
            } else { // Weekly rate
                $roomCharges = $suite['weekly_rate'];
                $nights = ceil($days / 7); // Count in weeks
            }
            
            // Update suite status
            $stmt = $pdo->prepare("UPDATE residential_suites SET status = 'available' WHERE suite_id = ?");
            $stmt->execute([$reservation['suite_id']]);
        }
        
        // Apply discount for company bookings
        if ($reservation['is_company_booking']) {
            $stmt = $pdo->prepare("SELECT discount_rate FROM travel_companies WHERE company_id = ?");
            $stmt->execute([$reservation['company_id']]);
            $company = $stmt->fetch();
            $discountRate = $company['discount_rate'];
            $roomCharges = $roomCharges * (1 - $discountRate);
        }
        
        // Get additional charges
        $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM additional_services WHERE reservation_id = ?");
        $stmt->execute([$reservationId]);
        $additional = $stmt->fetch();
        $additionalCharges = $additional['total'] ?? 0;
        
        // Calculate tax (10%)
        $tax = ($roomCharges + $additionalCharges) * 0.10;
        $totalAmount = $roomCharges + $additionalCharges + $tax;
        
        // Create bill
        $stmt = $pdo->prepare("INSERT INTO billing 
                              (reservation_id, room_charges, additional_charges, tax, total_amount, payment_method, payment_status)
                              VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        $paymentMethod = $_POST['payment_method'] ?? ($reservation['is_company_booking'] ? 'company' : 'cash');
        $stmt->execute([
            $reservationId,
            $roomCharges,
            $additionalCharges,
            $tax,
            $totalAmount,
            $paymentMethod,
            'paid'
        ]);
        
        // Update reservation status
        $stmt = $pdo->prepare("UPDATE reservations SET status = 'checked_out' WHERE reservation_id = ?");
        $stmt->execute([$reservationId]);
        
        $pdo->commit();
        
        return [
            'success' => true, 
            'total_amount' => $totalAmount,
            'room_charges' => $roomCharges,
            'additional_charges' => $additionalCharges,
            'tax' => $tax,
            'payment_method' => $paymentMethod
        ];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// Cancel a reservation
function cancelReservation($reservationId) {
    global $pdo;
    
    $stmt = $pdo->prepare("UPDATE reservations SET status = 'cancelled' WHERE reservation_id = ?");
    return $stmt->execute([$reservationId]);
}

// Process no-show reservations
function processNoShows() {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Get reservations that are no-shows (pending or confirmed but past check-in date)
        $today = date('Y-m-d');
        $stmt = $pdo->prepare("SELECT * FROM reservations 
                              WHERE status IN ('pending', 'confirmed') 
                              AND check_in_date < ?");
        $stmt->execute([$today]);
        $noShows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $totalRevenue = 0;
        $totalOccupancy = 0;
        
        foreach ($noShows as $reservation) {
            // Only charge if credit card was provided
            if (!empty($reservation['credit_card_info'])) {
                // Calculate one night charge
                $nightlyRate = 0;
                
                if ($reservation['room_id']) {
                    $stmt = $pdo->prepare("SELECT rt.base_price FROM rooms r 
                                          JOIN room_types rt ON r.type_id = rt.type_id 
                                          WHERE r.room_id = ?");
                    $stmt->execute([$reservation['room_id']]);
                    $room = $stmt->fetch();
                    $nightlyRate = $room['base_price'];
                }
                
                $roomCharges = $nightlyRate;
                $tax = $roomCharges * 0.10;
                $totalAmount = $roomCharges + $tax;
                
                // Create bill
                $stmt = $pdo->prepare("INSERT INTO billing 
                                      (reservation_id, room_charges, additional_charges, tax, total_amount, payment_method, payment_status)
                                      VALUES (?, ?, 0, ?, ?, 'credit_card', 'paid')");
                $stmt->execute([
                    $reservation['reservation_id'],
                    $roomCharges,
                    $tax,
                    $totalAmount
                ]);
                
                $totalRevenue += $totalAmount;
            }
            
            // Update reservation status
            $stmt = $pdo->prepare("UPDATE reservations SET status = 'no_show' WHERE reservation_id = ?");
            $stmt->execute([$reservation['reservation_id']]);
            
            $totalOccupancy++;
        }
        
        // Create report
        $stmt = $pdo->prepare("INSERT INTO reports 
                              (report_date, report_type, total_occupancy, total_revenue, details, generated_by)
                              VALUES (?, 'no_show', ?, ?, ?, ?)");
        $stmt->execute([
            date('Y-m-d'),
            $totalOccupancy,
            $totalRevenue,
            "Automated no-show report for " . date('Y-m-d'),
            1 // System user
        ]);
        
        $pdo->commit();
        return ['success' => true, 'no_shows_processed' => count($noShows)];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// Get daily occupancy report
function getDailyOccupancyReport($date) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT 
                          COUNT(*) as total_occupancy,
                          SUM(b.total_amount) as total_revenue,
                          rt.name as room_type,
                          COUNT(CASE WHEN r.status = 'checked_in' THEN 1 END) as current_guests
                          FROM reservations r
                          LEFT JOIN rooms rm ON r.room_id = rm.room_id
                          LEFT JOIN room_types rt ON rm.type_id = rt.type_id
                          LEFT JOIN billing b ON r.reservation_id = b.reservation_id
                          WHERE ? BETWEEN r.check_in_date AND r.check_out_date
                          AND r.status IN ('checked_in', 'checked_out')
                          GROUP BY rt.name");
    $stmt->execute([$date]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get financial report
function getFinancialReport($startDate, $endDate) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT 
                          DATE(b.created_at) as date,
                          SUM(b.room_charges) as room_revenue,
                          SUM(b.additional_charges) as additional_revenue,
                          SUM(b.tax) as tax,
                          SUM(b.total_amount) as total_revenue,
                          COUNT(*) as transactions
                          FROM billing b
                          WHERE DATE(b.created_at) BETWEEN ? AND ?
                          AND b.payment_status = 'paid'
                          GROUP BY DATE(b.created_at)
                          ORDER BY DATE(b.created_at)");
    $stmt->execute([$startDate, $endDate]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Create block booking for travel company
function createBlockBooking($companyId, $data) {
    global $pdo;
    
    $required = ['start_date', 'end_date', 'room_type_id', 'quantity'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            return ['success' => false, 'message' => "$field is required"];
        }
    }
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("INSERT INTO block_bookings 
                              (company_id, start_date, end_date, room_type_id, quantity)
                              VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $companyId,
            $data['start_date'],
            $data['end_date'],
            $data['room_type_id'],
            $data['quantity']
        ]);
        
        $blockId = $pdo->lastInsertId();
        
        $pdo->commit();
        return ['success' => true, 'block_id' => $blockId];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// Get user reservations
function getUserReservations($userId) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT r.*, 
                          rt.name as room_type,
                          rm.room_number,
                          rs.suite_number,
                          b.total_amount,
                          b.payment_status
                          FROM reservations r
                          LEFT JOIN rooms rm ON r.room_id = rm.room_id
                          LEFT JOIN room_types rt ON rm.type_id = rt.type_id
                          LEFT JOIN residential_suites rs ON r.suite_id = rs.suite_id
                          LEFT JOIN billing b ON r.reservation_id = b.reservation_id
                          WHERE r.customer_id = ?
                          ORDER BY r.check_in_date DESC");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Create a new user with role-specific data
 */
function createUser($pdo, $userData) {
    try {
        $pdo->beginTransaction();
        
        // Hash password
        $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
        
        // Insert into users table
        $stmt = $pdo->prepare("INSERT INTO users 
                              (username, password, email, role, first_name, last_name, phone, address) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $userData['username'],
            $hashedPassword,
            $userData['email'],
            $userData['role'],
            $userData['first_name'],
            $userData['last_name'],
            $userData['phone'],
            $userData['address']
        ]);
        
        $userId = $pdo->lastInsertId();
        
        // Handle role-specific inserts
        switch ($userData['role']) {
            case 'customer':
                $stmt = $pdo->prepare("INSERT INTO customers 
                                      (customer_id, credit_card_info, loyalty_points) 
                                      VALUES (?, ?, 0)");
                $stmt->execute([$userId, $userData['credit_card_info']]);
                break;
                
            case 'travel_company':
                $stmt = $pdo->prepare("INSERT INTO travel_companies 
                                      (company_id, company_name, discount_rate, billing_address) 
                                      VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    $userId,
                    $userData['company_name'],
                    $userData['discount_rate'],
                    $userData['address'] // Using main address as billing address
                ]);
                break;
        }
        
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("User creation failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Update user information
 */
function updateUser($pdo, $userId, $userData) {
    try {
        $pdo->beginTransaction();
        
        // Update users table
        $stmt = $pdo->prepare("UPDATE users SET 
                              email = ?, first_name = ?, last_name = ?, 
                              phone = ?, address = ?
                              WHERE user_id = ?");
        $stmt->execute([
            $userData['email'],
            $userData['first_name'],
            $userData['last_name'],
            $userData['phone'],
            $userData['address'],
            $userId
        ]);
        
        // Handle role-specific updates
        $stmt = $pdo->prepare("SELECT role FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $role = $stmt->fetchColumn();
        
        switch ($role) {
            case 'customer':
                $stmt = $pdo->prepare("UPDATE customers SET 
                                      credit_card_info = ?
                                      WHERE customer_id = ?");
                $stmt->execute([$userData['credit_card_info'], $userId]);
                break;
                
            case 'travel_company':
                $stmt = $pdo->prepare("UPDATE travel_companies SET 
                                      company_name = ?, discount_rate = ?
                                      WHERE company_id = ?");
                $stmt->execute([
                    $userData['company_name'],
                    $userData['discount_rate'],
                    $userId
                ]);
                break;
        }
        
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("User update failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete a user and their role-specific data
 */
function deleteUser($pdo, $userId) {
    try {
        $pdo->beginTransaction();
        
        // First get role to know which tables to clean up
        $stmt = $pdo->prepare("SELECT role FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $role = $stmt->fetchColumn();
        
        // Delete from role-specific table first
        switch ($role) {
            case 'customer':
                $stmt = $pdo->prepare("DELETE FROM customers WHERE customer_id = ?");
                $stmt->execute([$userId]);
                break;
                
            case 'travel_company':
                $stmt = $pdo->prepare("DELETE FROM travel_companies WHERE company_id = ?");
                $stmt->execute([$userId]);
                break;
        }
        
        // Then delete from users table
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("User deletion failed: " . $e->getMessage());
        return false;
    }
}

function getWeeklyOccupancyData() {
    global $pdo;
    
    $startDate = date('Y-m-d', strtotime('-7 days'));
    $endDate = date('Y-m-d');
    
    $stmt = $pdo->prepare("SELECT 
                          DATE(check_in_date) as date,
                          COUNT(*) as occupancy,
                          SUM(b.total_amount) as revenue
                          FROM reservations r
                          LEFT JOIN billing b ON r.reservation_id = b.reservation_id
                          WHERE DATE(check_in_date) BETWEEN ? AND ?
                          AND r.status IN ('checked_in', 'checked_out')
                          GROUP BY DATE(check_in_date)
                          ORDER BY DATE(check_in_date)");
    $stmt->execute([$startDate, $endDate]);
    
    // Fill in missing dates with 0 values
    $results = [];
    $currentDate = new DateTime($startDate);
    $endDateObj = new DateTime($endDate);
    
    while ($currentDate <= $endDateObj) {
        $dateStr = $currentDate->format('Y-m-d');
        $results[$dateStr] = [
            'date' => $currentDate->format('M j'),
            'occupancy' => 0,
            'revenue' => 0
        ];
        $currentDate->modify('+1 day');
    }
    
    // Merge with actual data
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $dateStr = $row['date'];
        $results[$dateStr] = [
            'date' => date('M j', strtotime($dateStr)),
            'occupancy' => (int)$row['occupancy'],
            'revenue' => (float)$row['revenue']
        ];
    }
    
    return array_values($results);
}

?>