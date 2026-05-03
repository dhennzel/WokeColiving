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
        $uid = (int)$occupant['user_id'];
        if ($uid > 0) {
            $check_active_key = mysqli_query($conn, "SELECT id FROM key_transactions WHERE user_id=$uid AND status='Active'");
            if(mysqli_num_rows($check_active_key) == 0){
                $tenants[] = ['user_id' => $uid, 'full_name' => $occupant['full_name']];
            }
        }
    }
}

echo json_encode($tenants);
?>