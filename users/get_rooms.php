<?php
include '../db.php';

$checkin = $_GET['checkin'] ?? '';
$checkout = $_GET['checkout'] ?? '';

// GET SINGLE ROOM BY ID (For Modal)
if (isset($_GET['id'])) {
    $rid = (int)$_GET['id'];
    $q = mysqli_query($conn, "SELECT * FROM rooms WHERE room_id=$rid");
    $room = mysqli_fetch_assoc($q);
    echo json_encode($room);
    exit;
}

function getTotalBeds($conn, $room_id) {
    $row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT total_beds FROM rooms WHERE room_id=$room_id"));
    return $row['total_beds'];
}

function getAvailableBeds($conn, $room_id, $checkin, $checkout) {
    $query = mysqli_query($conn, "
        SELECT COUNT(*) AS booked
        FROM reservations
        WHERE room_id = $room_id
        AND status IN ('Pending','Approved')
        AND start_date < '$checkout'
        AND end_date > '$checkin'
    ");
    $res = mysqli_fetch_assoc($query);
    $total = getTotalBeds($conn, $room_id);
    return $total - $res['booked'];
}

// GET ALL AVAILABLE ROOMS
$rooms_query = mysqli_query($conn, "SELECT * FROM rooms WHERE status='Available'");
$rooms = [];

while($room = mysqli_fetch_assoc($rooms_query)) {
    if($checkin && $checkout) {
        $beds_left = getAvailableBeds($conn, $room['room_id'], $checkin, $checkout);
        if($beds_left > 0) {
            $room['available_beds'] = $beds_left;
            $rooms[] = $room;
        }
    } else {
        $room['available_beds'] = $room['total_beds'];
        $rooms[] = $room;
    }
}

header('Content-Type: application/json');
echo json_encode($rooms);