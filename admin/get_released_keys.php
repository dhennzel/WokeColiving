<?php
session_start();
include("../db.php");

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$room_filter = '';
if (isset($_GET['room']) && !empty($_GET['room'])) {
    $room_filter = " AND (r.room_name = '" . mysqli_real_escape_string($conn, $_GET['room']) . "' OR r.room_number = '" . mysqli_real_escape_string($conn, $_GET['room']) . "')";
}

$released_keys = [];
$q = mysqli_query($conn, "
    SELECT 
        kt.id as trans_id,
        kt.released_at,
        k.id as key_id,
        k.key_name,
        CONCAT(u.first_name, ' ', u.last_name) as holder_name,
        u.user_id,
        r.room_name,
        r.room_number
    FROM key_transactions kt 
    JOIN `keys` k ON kt.key_id = k.id 
    LEFT JOIN rooms r ON k.reference_id = r.room_id
    JOIN users u ON kt.user_id = u.user_id 
    WHERE kt.status = 'Active' $room_filter
    ORDER BY kt.released_at DESC
");

while ($row = mysqli_fetch_assoc($q)) {
    $released_keys[] = $row;
}

header('Content-Type: application/json');
echo json_encode($released_keys);
?>
