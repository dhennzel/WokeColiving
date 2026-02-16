<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: users/index.php");
} else {
    header("Location: guest.php");
}
exit();

//buknot
