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
        
    case 'get_available_suites':
        $typeId = $_GET['type_id'] ?? 0;
        $checkIn = $_GET['check_in'] ?? '';
        $checkOut = $_GET['check_out'] ?? '';
        $duration = $_GET['duration'] ?? 'weekly';
        
        if ($typeId && $checkIn && $checkOut) {
            $suites = getAvailableRooms($typeId, $checkIn, $checkOut, true);
            echo json_encode(['success' => true, 'suites' => $suites]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Missing parameters']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>