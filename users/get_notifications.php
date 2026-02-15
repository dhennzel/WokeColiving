<?php
include '../db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle Mark Read
if (isset($_POST['mark_read'])) {
    mysqli_query($conn, "UPDATE notifications SET is_read=1 WHERE user_id=$user_id");
    echo json_encode(['success' => true]);
    exit;
}

// Fetch Unread Count
$unread_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM notifications WHERE user_id=$user_id AND is_read=0");
$unread_count = mysqli_fetch_assoc($unread_res)['cnt'];

// Fetch Notifications
$notifications = [];
$notif_query = mysqli_query($conn, "SELECT * FROM notifications WHERE user_id=$user_id ORDER BY created_at DESC LIMIT 10");

while($row = mysqli_fetch_assoc($notif_query)){
    $notifications[] = [
        'message' => $row['message'],
        'type' => $row['type'],
        'is_read' => $row['is_read'],
        'created_at' => date('M d, h:i A', strtotime($row['created_at']))
    ];
}

header('Content-Type: application/json');
echo json_encode([
    'unread_count' => (int)$unread_count,
    'notifications' => $notifications
]);
?>