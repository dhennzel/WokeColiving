<?php
session_start();
include("../db.php");

if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Fetch Pending Counts
$pending_res_q = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM reservations WHERE status IN ('Pending', 'Verifying')"))['c'];
$pending_pay_q = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM payments WHERE payment_status='Unpaid' AND proof_image IS NOT NULL"))['c'];
$pending_res = $pending_res_q + $pending_pay_q;

$pending_maint = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM maintenance_requests WHERE status='Pending'"))['c'];
$pending_house = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM housekeeping_requests WHERE status='Pending'"))['c'];
$del_req_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM account_deletion_requests WHERE status='Pending'"))['c'];

$pk_cnt = 0;
try {
    $pk_q = mysqli_query($conn, "SELECT COUNT(*) as c FROM parking_reservations WHERE status='Active' AND end_date < CURDATE()");
    if($pk_q) $pk_cnt = mysqli_fetch_assoc($pk_q)['c'];
} catch(Exception $e){}

$fin_cnt = 0;
try {
    $fin_q = mysqli_query($conn, "SELECT COUNT(*) as c FROM (SELECT u.user_id FROM users u JOIN reservations r ON u.user_id = r.user_id JOIN payments p ON r.reservation_id = p.reservation_id WHERE p.payment_status = 'Unpaid' AND u.is_archived = 0 GROUP BY u.user_id HAVING SUM(p.amount) > 5000) as sub");
    if($fin_q) $fin_cnt = mysqli_fetch_assoc($fin_q)['c'];
} catch(Exception $e){}

$total_notifications = $pending_res + $pending_maint + $pending_house + $del_req_count + $pk_cnt + $fin_cnt;

header('Content-Type: application/json');
echo json_encode([
    'total_notifications' => (int)$total_notifications
]);
?>