<?php
require_once 'config.php';
require_once 'functions.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_available_rooms':
        $typeId = $_GET['type_id'] ?? 0;
        $checkIn = $_GET['check_in'] ?? '';
        $checkOut = $_GET['check_out'] ?? '';
        
        if ($typeId && $checkIn && $checkOut) {
            $rooms = getAvailableRooms($typeId, $checkIn, $checkOut);
            echo json_encode(['success' => true, 'rooms' => $rooms]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Missing parameters']);
        }
        break;
        
    // In includes/ajax.php
    case 'get_available_suites':
        $typeId = $_GET['type_id'] ?? 0;
        $checkIn = $_GET['check_in'] ?? '';
        $checkOut = $_GET['check_out'] ?? '';
        $duration = $_GET['duration'] ?? 'weekly';
        
        if ($typeId && $checkIn && $checkOut) {
            $query = "SELECT * FROM residential_suites 
                    WHERE type_id = ? AND status = 'available' 
                    AND suite_id NOT IN (
                        SELECT suite_id FROM reservations 
                        WHERE status IN ('confirmed', 'checked_in') 
                        AND (
                            (check_in_date <= ? AND check_out_date >= ?) OR
                            (check_in_date <= ? AND check_out_date >= ?) OR
                            (check_in_date >= ? AND check_out_date <= ?)
                        )
                    )";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$typeId, $checkOut, $checkIn, $checkIn, $checkOut, $checkIn, $checkOut]);
            $suites = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'suites' => $suites]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Missing parameters']);
        }
    break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>