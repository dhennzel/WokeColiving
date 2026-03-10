<?php
session_start();
include("../db.php");

// Only allow admin
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
    header("Location: admin_login.php");
    exit;
}

// Ensure is_hidden column exists
$check_col_hidden = mysqli_query($conn, "SHOW COLUMNS FROM rooms LIKE 'is_hidden'");
if(mysqli_num_rows($check_col_hidden) == 0) {
    mysqli_query($conn, "ALTER TABLE rooms ADD COLUMN is_hidden TINYINT(1) DEFAULT 0");
}

// Find and hide Room 203
$room_number = '203';
$result = mysqli_query($conn, "SELECT room_id, room_number, is_hidden FROM rooms WHERE room_number = '$room_number' OR room_number LIKE '%203%'");

if(mysqli_num_rows($result) > 0) {
    $room = mysqli_fetch_assoc($result);
    $room_id = $room['room_id'];
    
    // Update to hide the room
    mysqli_query($conn, "UPDATE rooms SET is_hidden = 1 WHERE room_id = $room_id");
    
    echo "Room 203 (ID: $room_id) has been hidden from the dashboard.";
    echo "<br><a href='admin_dashboard.php'>Go back to Dashboard</a>";
} else {
    echo "Room 203 not found in the database.";
    echo "<br><a href='admin_dashboard.php'>Go back to Dashboard</a>";
}
?>

