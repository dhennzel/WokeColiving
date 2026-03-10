<?php
include 'db.php';

echo "=== DELETING ORPHAN KEYS ===\n\n";

// Delete orphan keys (keys not linked to any room)
$orphan_keys = mysqli_query($conn, "
    SELECT k.id, k.key_name, k.reference_id
    FROM `keys` k
    LEFT JOIN rooms r ON k.type = 'Room' AND k.reference_id = r.room_id
    WHERE k.type = 'Room' AND r.room_id IS NULL
");

$deleted = 0;
while ($k = mysqli_fetch_assoc($orphan_keys)) {
    // First delete any transactions
    mysqli_query($conn, "DELETE FROM key_transactions WHERE key_id=" . $k['id']);
    // Then delete the key
    mysqli_query($conn, "DELETE FROM `keys` WHERE id=" . $k['id']);
    echo "Deleted orphan key ID {$k['id']}: {$k['key_name']}\n";
    $deleted++;
}

echo "\nDeleted $deleted orphan keys\n\n";

// Now show current status
echo "=== CURRENT STATUS ===\n";

$keys = mysqli_query($conn, "
    SELECT r.room_type, COUNT(k.id) as cnt
    FROM `keys` k
    JOIN rooms r ON k.type = 'Room' AND k.reference_id = r.room_id
    WHERE k.type = 'Room'
    GROUP BY r.room_type
");

echo "Keys by Room Type:\n";
while ($k = mysqli_fetch_assoc($keys)) {
    echo "  {$k['room_type']}: {$k['cnt']} keys\n";
}

$total = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM `keys` WHERE type='Room'");
echo "\nTotal Room Keys: " . mysqli_fetch_assoc($total)['cnt'] . "\n";

// Show rooms count too
$rooms = mysqli_query($conn, "SELECT room_type, COUNT(*) as cnt FROM rooms GROUP BY room_type");
echo "\nRooms by Type:\n";
while ($r = mysqli_fetch_assoc($rooms)) {
    echo "  {$r['room_type']}: {$r['cnt']} rooms\n";
}
?>

