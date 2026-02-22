<?php
include '../db.php';

 $checkin = $_GET['checkin'] ?? date('Y-m-d');
 $checkout = $_GET['checkout'] ?? date('Y-m-d', strtotime('+1 day'));

// GET SINGLE ROOM BY ID (For Modal)
if (isset($_GET['id'])) {
    $rid = (int)$_GET['id'];
    $q = mysqli_query($conn, "SELECT * FROM rooms WHERE room_id=$rid");
    $room = mysqli_fetch_assoc($q);
    echo json_encode($room);
    exit;
}

// GET ALL AVAILABLE ROOMS
$rooms_query = mysqli_query($conn, "SELECT * FROM rooms WHERE availability='Available' AND is_archived=0");
$rooms = [];

while($room = mysqli_fetch_assoc($rooms_query)) {
    $rid = $room['room_id'];
    $total_beds = $room['total_beds'];
    $room_type = $room['room_type'];

        // Detailed occupancy query
        $q_occ = mysqli_query($conn, "
            SELECT bed_preference, COUNT(*) as cnt 
            FROM reservations 
            WHERE room_id = $rid 
            AND status IN ('Pending','Approved') 
            AND start_date < '$checkout' 
            AND end_date > '$checkin'
            GROUP BY bed_preference
        ");
        
        $occ_lower = 0; $occ_upper = 0; $occ_any = 0; $total_booked = 0;
        while($row_o = mysqli_fetch_assoc($q_occ)){
            $total_booked += $row_o['cnt'];
            if($row_o['bed_preference'] == 'Lower Bunk') $occ_lower += $row_o['cnt'];
            elseif($row_o['bed_preference'] == 'Upper Bunk') $occ_upper += $row_o['cnt'];
            else $occ_any += $row_o['cnt'];
        }
        
        $avail_total = max(0, $total_beds - $total_booked);
        
        // Calculate specific availability
        $avail_lower = 0; $avail_upper = 0;
        
        if($room_type == '4-Bed' || $room_type == '6-Bed'){
            $cap_upper = floor($total_beds / 2);
            $cap_lower = ceil($total_beds / 2);
            
            $avail_upper = max(0, $cap_upper - $occ_upper);
            $avail_lower = max(0, $cap_lower - $occ_lower);
            
            if($occ_any > 0) {
                $fill_lower = min($avail_lower, $occ_any);
                $avail_lower -= $fill_lower;
                $occ_any -= $fill_lower;
                
                $avail_upper -= $occ_any;
                $avail_upper = max(0, $avail_upper);
            }
        } else {
            $avail_lower = $avail_total; // Single room treated as lower/base
        }
        
        if($avail_total > 0) {
            $room['available_beds'] = $avail_total;
            $room['avail_lower'] = $avail_lower;
            $room['avail_upper'] = $avail_upper;
            $rooms[] = $room;
        }
}

header('Content-Type: application/json');
echo json_encode($rooms);