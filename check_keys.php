<?php
include 'db.php';

// Get room counts by type
echo "=== FINAL KEY STATUS ===\n\n";

$keys = mysqli_query($conn, "
    SELECT k.id, k.key_name, k.type, k.status, k.reference_id, r.room_type, r.room_number
    FROM `keys` k
    LEFT JOIN rooms r ON k.type = 'Room' AND k.reference_id = r.room_id
    ORDER BY k.type, r.room_type, k.id
");

$total = 0;
$orphan = 0;
$by_type = [];

while($k = mysqli_fetch_assoc($keys)) {
    if ($k['type'] == 'Room') {
        $total++;
        if ($k['room_type']) {
            if (!isset($by_type[$k['room_type']])) $by_type[$k['room_type']] = 0;
            $by_type[$k['room_type']]++;
        } else {
            $orphan++;
            echo "ORPHAN KEY: ID {$k['id']} - {$k['key_name']} (no room)\n";
        }
    }
}

echo "\nKeys by Room Type:\n";
foreach ($by_type as $type => $count) {
    echo "  $type: $count keys\n";
}

echo "\nTotal Room Keys: $total\n";
if ($orphan > 0) {
    echo "Orphan keys (not linked to room): $orphan\n";
}

// Show total available keys
$available = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM `keys` WHERE type='Room' AND status='Available'");
echo "Available Keys: " . mysqli_fetch_assoc($available)['cnt'] . "\n";
?>

