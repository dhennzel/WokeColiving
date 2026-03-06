<?php
session_start();
include("../db.php");

if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
    header("Location: admin_login.php");
    exit;
}

if(!isset($_GET['id'])) die("Invalid Request");
$id = (int)$_GET['id'];

// Fetch Reservation Info
$query = "SELECT r.*, CONCAT(u.last_name, ', ', u.first_name, IF(u.middle_name IS NOT NULL AND u.middle_name != '', CONCAT(' ', u.middle_name), '')) as full_name, u.email, u.phone_number, u.gender, u.is_walkin, rm.room_name, rm.room_type, rm.total_price as room_price 
          FROM reservations r 
          JOIN users u ON r.user_id = u.user_id 
          JOIN rooms rm ON r.room_id = rm.room_id 
          WHERE r.reservation_id = $id";
$res = mysqli_query($conn, $query);
if(mysqli_num_rows($res) == 0) die("Reservation not found");
$data = mysqli_fetch_assoc($res);
$theme = get_theme_colors($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Registration #<?= $data['reservation_id'] ?> | Woke Coliving</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="admin_CSS/admin_style.css">
    <style>
        :root {
            --primary-green: <?= $theme['primary'] ?>;
            --dark-green: <?= $theme['dark'] ?>;
            --accent-yellow: <?= $theme['accent'] ?>;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="receipt-container">
        <!-- Header -->
        <div class="header d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" class="logo me-3">
                <div>
                    <div class="company-name">Woke Coliving INC</div>
                    <small class="text-muted">123 Coliving Street, City Center</small><br>
                    <small class="text-muted">contact@wokecoliving.com | +63 912 345 6789</small>
                </div>
            </div>
            <div class="text-end">
                <h4 class="fw-bold text-uppercase mb-0">Registration Form</h4>
                <div class="text-muted">#<?= str_pad($data['reservation_id'], 6, '0', STR_PAD_LEFT) ?></div>
                <div class="small text-muted">Date: <?= date('M d, Y') ?></div>
            </div>
        </div>

        <!-- Guest & Room Info -->
        <div class="row mb-4">
            <div class="col-6">
                <div class="mb-3">
                    <div class="label">Guest Name</div>
                    <div class="value"><?= $data['full_name'] ?></div>
                </div>
                <div class="mb-3">
                    <div class="label">Contact Info</div>
                    <div><?= $data['email'] ?></div>
                    <div><?= $data['phone_number'] ?></div>
                    <div>Gender: <?= $data['gender'] ?></div>
                </div>
            </div>
            <div class="col-6 text-end">
                <div class="mb-3">
                    <div class="label">Room Details</div>
                    <div class="value"><?= $data['room_name'] ?></div>
                    <div><?= $data['room_type'] ?></div>
                    <div>Bed: <?= $data['bed_preference'] ?></div>
                </div>
                <div class="mb-3">
                    <div class="label">Stay Duration</div>
                    <div>In: <strong><?= date('F d, Y', strtotime($data['start_date'])) ?></strong></div>
                    <div>Out: <strong><?= date('F d, Y', strtotime($data['end_date'])) ?></strong></div>
                    <div>(<?= $data['months'] ?> Months)</div>
                </div>
            </div>
        </div>

        <!-- Terms -->
        <div class="mb-4 p-3 bg-light rounded border">
            <div class="label mb-2">House Rules & Regulations</div>
            <ul class="small text-muted mb-0" style="line-height: 1.6; padding-left: 20px;">
                <li><strong>Quiet Hours:</strong> Please observe quiet hours from 10:00 PM to 7:00 AM to respect other tenants.</li>
                <li><strong>Cleanliness:</strong> Clean up after yourself in common areas (kitchen, living room, bathroom). Do not leave personal items in shared spaces.</li>
                <li><strong>Visitors:</strong> Visitors are allowed until 9:00 PM only. No overnight guests are permitted without prior admin approval.</li>
                <li><strong>Smoking/Alcohol:</strong> Smoking and drinking alcohol are strictly prohibited inside the premises.</li>
                <li><strong>Security:</strong> Always lock your room and the main door when leaving. The management is not liable for lost valuables.</li>
                <li><strong>Utilities:</strong> For stays of 6 months or longer, water and electricity are billed separately based on consumption.</li>
                <li><strong>Damages:</strong> Any damage to property or furniture caused by the tenant will be charged accordingly.</li>
            </ul>
            <p class="small text-muted text-justify mt-3 mb-0">
                I, the undersigned, hereby agree to the terms and conditions of Woke Coliving INC stated above. I understand that violation of these rules may result in penalties or eviction.
            </p>
        </div>

        <!-- Signatures -->
        <div class="row mt-5 pt-4">
            <div class="col-6">
                <?php if(!empty($data['signature_image'])): ?>
                    <div class="text-center">
                        <img src="../assets/signatures/<?= $data['signature_image'] ?>" class="sig-img">
                        <div style="border-top: 1px solid #000; width: 100%;"></div>
                        <strong><?= $data['full_name'] ?></strong><br>Guest Signature
                    </div>
                <?php else: ?>
                    <div class="sig-box">
                        <strong><?= $data['full_name'] ?></strong><br>
                        Guest Signature
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-6">
                <div class="sig-box">
                    <strong>Authorized Admin</strong><br>
                    Woke Coliving Representative
                </div>
            </div>
        </div>

        <!-- Print Button -->
        <div class="text-center mt-5 no-print">
            <button onclick="window.print()" class="btn btn-success btn-lg"><i class="fas fa-print me-2"></i>Print Form</button>
            <button onclick="window.close()" class="btn btn-secondary btn-lg ms-2">Close</button>
        </div>
    </div>
</div>

</body>
</html>