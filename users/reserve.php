<?php
include '../db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id    = $_SESSION['user_id'];

// Check if user already has an active reservation
$check = mysqli_query($conn, "SELECT * FROM reservations WHERE user_id='$user_id' AND status IN ('Pending','Approved')");
if(mysqli_num_rows($check) > 0){
    $_SESSION['swal'] = ['title' => 'Limit Reached', 'text' => 'You already have an active reservation. Limit is 1 per account.', 'icon' => 'warning'];
    header("Location: my_reservations.php");
    exit;
}

if(isset($_POST['room_id'])) {
    $room_id    = (int)$_POST['room_id'];
    $start_date = $_POST['start_date'];
    $end_date   = $_POST['end_date'];

    $room = mysqli_fetch_assoc(mysqli_query($conn, "SELECT price_per_month FROM rooms WHERE room_id=$room_id"));
    
    $d1 = new DateTime($start_date);
    $d2 = new DateTime($end_date);
    $days = $d1->diff($d2)->days;
    $months = round($days / 30, 1);
    $total_price = ($room['price_per_month'] / 30) * $days;

    mysqli_query($conn, "INSERT INTO reservations (user_id, room_id, start_date, end_date, months, total_price)
    VALUES ('$user_id','$room_id','$start_date','$end_date','$months','$total_price')");

    header("Location: my_reservations.php");
}
