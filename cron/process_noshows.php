<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

$result = processNoShows();

// Log the result
file_put_contents('../logs/noshow_processing.log', 
    date('Y-m-d H:i:s') . ' - Processed ' . ($result['no_shows_processed'] ?? 0) . ' no-show reservations' . PHP_EOL, 
    FILE_APPEND);
?>