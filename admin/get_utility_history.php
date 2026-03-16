<?php
session_start();
include("../db.php");

if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
    http_response_code(403);
    exit('Unauthorized');
}

if(!isset($_GET['reservation_id'])){
    http_response_code(400);
    exit('Invalid Request');
}

$res_id = (int)$_GET['reservation_id'];

$history_q = mysqli_query($conn, "SELECT * FROM utility_bills WHERE reservation_id = $res_id ORDER BY bill_date DESC");

if(mysqli_num_rows($history_q) > 0){
    echo '<div class="table-responsive">';
    echo '<table class="table table-sm table-hover">';
    echo '<thead class="table-light"><tr><th>Bill Date</th><th>Electric Usage (kWh)</th><th>Water Usage (m³)</th><th class="text-end">Total Bill</th></tr></thead>';
    echo '<tbody>';
    while($row = mysqli_fetch_assoc($history_q)){
        $e_usage = $row['electric_end'] - $row['electric_start'];
        $w_usage = $row['water_end'] - $row['water_start'];
        echo '<tr>';
        echo '<td>' . date('M d, Y', strtotime($row['bill_date'])) . '</td>';
        echo '<td>' . number_format($e_usage, 2) . '</td>';
        echo '<td>' . number_format($w_usage, 2) . '</td>';
        echo '<td class="text-end fw-bold">₱' . number_format($row['total_amount'], 2) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table></div>';
} else {
    echo '<div class="text-center text-muted py-5"><i class="fas fa-file-invoice-dollar fa-3x mb-3 opacity-50"></i><p>No billing history found for this tenant.</p></div>';
}
?>