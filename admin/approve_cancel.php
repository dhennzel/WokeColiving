<?php
include '../db.php';
session_start();

if ($_SESSION['role'] != 'admin') exit;

if (isset($_GET['approve'])) {
    $id = $_GET['approve'];
    mysqli_query($conn, "UPDATE reservations SET status='Approved' WHERE reservation_id=$id");
}

$query = mysqli_query($conn, "SELECT r.*, u.full_name, rm.room_name 
FROM reservations r
JOIN users u ON r.user_id=u.user_id
JOIN rooms rm ON r.room_id=rm.room_id");
?>

<h2>Admin Reservations</h2>

<table border="1">
<tr>
    <th>User</th>
    <th>Room</th>
    <th>Status</th>
    <th>Action</th>
</tr>

<?php while($row = mysqli_fetch_assoc($query)) { ?>
<tr>
    <td><?php echo $row['full_name']; ?></td>
    <td><?php echo $row['room_name']; ?></td>
    <td><?php echo $row['status']; ?></td>
    <td>
        <a href="?approve=<?php echo $row['reservation_id']; ?>">Approve</a>
    </td>
</tr>
<?php } ?>
</table>
