<?php
session_start();
include("../db.php");

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode([]);
    exit;
}

$room_id = (int)$_GET['room_id'] ?? 0;

if ($room_id > 0) {
    // Get users with approved reservations for this specific room
    $q = mysqli_query($conn, "
        SELECT DISTINCT u.user_id, CONCAT(u.last_name, ', ', u.first_name) as full_name 
        FROM users u 
        JOIN reservations r ON u.user_id = r.user_id 
        WHERE r.room_id = $room_id AND r.status = 'Approved' AND u.role = 'user' 
        ORDER BY u.last_name
    ");
    
    $tenants = [];
    while ($row = mysqli_fetch_assoc($q)) {
        $tenants[] = $row;
    }
    echo json_encode($tenants);
} else {
    echo json_encode([]);
}
?>

