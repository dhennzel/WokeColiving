<?php
session_start();
include("../db.php");

// Only allow admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

if(!isset($_GET['room_id'])){
    http_response_code(400);
    echo json_encode(['error' => 'Room ID not provided']);
    exit;
}

$room_id = (int)$_GET['room_id'];

// Use the existing function from db.php to get occupants
$occupants = get_room_occupants($conn, $room_id);

// Filter for approved tenants only, as keys should only be released to them
$tenants = [];
foreach($occupants as $occupant){
    if($occupant['status'] == 'Approved'){
        $tenants[] = ['user_id' => $occupant['user_id'], 'full_name' => $occupant['full_name']];
    }
}

echo json_encode($tenants);
?>