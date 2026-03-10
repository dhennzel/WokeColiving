<?php
include 'db.php';

// Show all rooms by type
echo "=== ALL ROOMS ===\n\n";
$rooms = mysqli_query($conn, "SELECT room_id, room_type, room_number, room_name FROM rooms ORDER BY room_type, room_number");

$by_type = [];
while($r = mysqli_fetch_assoc($rooms)) {
    $by_type[$r['room_type']][] = $r;
}

foreach ($by_type as $type => $room_list) {
    echo "$type Rooms:\n";
    foreach ($room_list as $room) {
        echo "  ID {$room['room_id']}: {$room['room_number']} - {$room['room_name']}\n";
    }
    echo "Total: " . count($room_list) . " rooms\n\n";
}
?>

