<?php
include 'db.php';
// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$q = mysqli_query($conn, "SELECT setting_value FROM site_settings WHERE setting_key='last_update'");
$row = mysqli_fetch_assoc($q);
echo $row['setting_value'] ?? time();
?>