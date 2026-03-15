<?php
session_start();
include("../db.php");

// Only allow admin
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
    header("Location: admin_login.php");
    exit;
}

// Super Admin only
if(($_SESSION['admin_role'] ?? 'Admin') != 'Super Admin'){
    header("Location: admin_dashboard.php?error=access_denied");
    exit;
}

// Ensure table exists
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) UNIQUE NOT NULL,
    setting_value TEXT
)");

$message = "";
$error = "";

// Handle Upload (Cropped Image)
if(isset($_POST['upload_cropped_hero'])){
    // Fetch existing images first
    $current_images = [];
    $q = mysqli_query($conn, "SELECT setting_value FROM site_settings WHERE setting_key='hero_image'");
    if($row = mysqli_fetch_assoc($q)){
        $decoded = json_decode($row['setting_value'], true);
        if(is_array($decoded)) $current_images = $decoded;
        elseif(!empty($row['setting_value'])) $current_images[] = $row['setting_value'];
    }

    if(isset($_POST['cropped_image_data'])){
        $data = $_POST['cropped_image_data'];
        // Decode Base64
        list($type, $data) = explode(';', $data);
        list(, $data)      = explode(',', $data);
        $data = base64_decode($data);
        
        $filename = time() . "_hero.png";
        $target_dir = "../assets/images/";
        if(!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        if(file_put_contents($target_dir . $filename, $data)){
            $current_images[] = $filename;
            $json_val = mysqli_real_escape_string($conn, json_encode(array_values($current_images)));
            $sql = "INSERT INTO site_settings (setting_key, setting_value) VALUES ('hero_image', '$json_val') ON DUPLICATE KEY UPDATE setting_value='$json_val'";
            mysqli_query($conn, $sql);
            $message = "Image cropped and uploaded successfully!";
        } else {
            $error = "Failed to save image.";
        }
    } else {
        $error = "No image data received.";
    }
}

// Handle Update (Replace or Re-crop)
if(isset($_POST['update_cropped_hero']) && isset($_POST['old_image_name'])){
    $old_img = $_POST['old_image_name'];
    
    // Fetch existing
    $current_images = [];
    $q = mysqli_query($conn, "SELECT setting_value FROM site_settings WHERE setting_key='hero_image'");
    if($row = mysqli_fetch_assoc($q)){
        $decoded = json_decode($row['setting_value'], true);
        if(is_array($decoded)) $current_images = $decoded;
    }

    if(isset($_POST['cropped_image_data'])){
        $data = $_POST['cropped_image_data'];
        // Decode Base64
        list($type, $data) = explode(';', $data);
        list(, $data)      = explode(',', $data);
        $data = base64_decode($data);
        
        // Create new filename to avoid cache issues
        $filename = time() . "_hero_edit.png";
        $target_dir = "../assets/images/";
        if(!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        if(file_put_contents($target_dir . $filename, $data)){
            // Find index and replace
            $key = array_search($old_img, $current_images);
            if($key !== false) {
                $current_images[$key] = $filename;
                // Optional: unlink("../assets/images/" . $old_img);
            }
            
            $json_val = mysqli_real_escape_string($conn, json_encode(array_values($current_images)));
            $sql = "UPDATE site_settings SET setting_value='$json_val' WHERE setting_key='hero_image'";
            mysqli_query($conn, $sql);
            $message = "Image updated successfully!";
        } else {
            $error = "Failed to save updated image.";
        }
    }
}

// Handle Delete Specific Image
if(isset($_POST['delete_image'])){
    $img_to_delete = $_POST['delete_image'];
    $q = mysqli_query($conn, "SELECT setting_value FROM site_settings WHERE setting_key='hero_image'");
    if($row = mysqli_fetch_assoc($q)){
        $current_images = json_decode($row['setting_value'], true);
        if(!is_array($current_images) && !empty($row['setting_value'])) $current_images = [$row['setting_value']];
        
        // Remove image from array
        if(($key = array_search($img_to_delete, $current_images)) !== false) {
            unset($current_images[$key]);
            // Optional: unlink("../assets/images/" . $img_to_delete); // Delete file from server
        }
        
        $json_val = mysqli_real_escape_string($conn, json_encode(array_values($current_images)));
        $sql = "UPDATE site_settings SET setting_value='$json_val' WHERE setting_key='hero_image'";
        if(mysqli_query($conn, $sql)){
            $message = "Image removed successfully.";
        }
    }
}

// Handle Reorder
if(isset($_POST['move_image']) && isset($_POST['direction'])){
    $img_name = $_POST['move_image'];
    $direction = $_POST['direction'];
    
    $q = mysqli_query($conn, "SELECT setting_value FROM site_settings WHERE setting_key='hero_image'");
    if($row = mysqli_fetch_assoc($q)){
        $current_images = json_decode($row['setting_value'], true);
        if(!is_array($current_images)) $current_images = [];
        
        $key = array_search($img_name, $current_images);
        if($key !== false){
            if($direction == 'up' && $key > 0){
                // Swap with previous
                $temp = $current_images[$key-1];
                $current_images[$key-1] = $current_images[$key];
                $current_images[$key] = $temp;
            } elseif($direction == 'down' && $key < count($current_images)-1){
                // Swap with next
                $temp = $current_images[$key+1];
                $current_images[$key+1] = $current_images[$key];
                $current_images[$key] = $temp;
            }
            
            $json_val = mysqli_real_escape_string($conn, json_encode(array_values($current_images)));
            mysqli_query($conn, "UPDATE site_settings SET setting_value='$json_val' WHERE setting_key='hero_image'");
            header("Location: manage_hero.php");
            exit;
        }
    }
}

// Fetch Current Images
$current_images = [];
$q = mysqli_query($conn, "SELECT setting_value FROM site_settings WHERE setting_key='hero_image'");
if($row = mysqli_fetch_assoc($q)){
    $decoded = json_decode($row['setting_value'], true);
    if(is_array($decoded)) $current_images = $decoded;
    elseif(!empty($row['setting_value'])) $current_images[] = $row['setting_value'];
}

// Fetch Pending Counts for Sidebar
$pending_res_q = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM reservations WHERE status IN ('Pending', 'Verifying')"))['c'];
$pending_pay_q = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM payments WHERE payment_status='Unpaid' AND proof_image IS NOT NULL"))['c'];
$pending_res = $pending_res_q + $pending_pay_q;
$pending_maint = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM maintenance_requests WHERE status='Pending'"))['c'];
$pending_house = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM housekeeping_requests WHERE status='Pending'"))['c'];
$waitlist_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM waitlist WHERE notified_at IS NULL"))['c'];
$del_req_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM account_deletion_requests WHERE status='Pending'"))['c'];

$theme = get_theme_colors($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Hero Image | Woke Coliving INC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="admin.css">
    <style>
        .hero-card-img { height: 200px; object-fit: cover; width: 100%; border-bottom: 1px solid #eee; }
    </style>
</head>
<body>
<div class="dashboard-container">
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-wrapper">
        <?php include 'admin_topbar.php'; ?>
        <main class="main-content">
            <div class="page-header">
                <h1>Manage Homepage Hero Images</h1>
            </div>
            <?php if($message) echo "<div class='alert alert-success'>$message</div>"; ?>
            <?php if($error) echo "<div class='alert alert-danger'>$error</div>"; ?>

            <div class="card card-custom p-4">
                <h5 class="mb-3">Current Hero Backgrounds</h5>
                <div class="row g-3">
                    <?php if(!empty($current_images)): ?>
                        <?php foreach($current_images as $index => $img): ?>
                            <?php if(file_exists("../assets/images/$img")): ?>
                            <div class="col-md-4">
                                <div class="card h-100">
                                    <img src="../assets/images/<?= $img ?>" class="hero-card-img">
                                    <div class="card-body d-flex justify-content-between align-items-center bg-light">
                                        <!-- Move Up/Left -->
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="move_image" value="<?= $img ?>">
                                            <input type="hidden" name="direction" value="up">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary" <?= $index == 0 ? 'disabled' : '' ?>><i class="fas fa-arrow-left"></i></button>
                                        </form>

                                        <!-- Edit Actions -->
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-warning text-white" onclick="editImage('<?= $img ?>', 'replace')" title="Upload New & Crop"><i class="fas fa-upload"></i></button>
                                            <button type="button" class="btn btn-sm btn-info text-white" onclick="editImage('<?= $img ?>', 'recrop', '../assets/images/<?= $img ?>')" title="Re-crop Current"><i class="fas fa-crop"></i></button>
                                        </div>

                                        <!-- Delete -->
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="delete_image" value="<?= $img ?>">
                                            <button type="button" class="btn btn-sm btn-danger" onclick="confirmHeroDelete(this.form)"><i class="fas fa-trash"></i></button>
                                        </form>

                                        <!-- Move Down/Right -->
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="move_image" value="<?= $img ?>">
                                            <input type="hidden" name="direction" value="down">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary" <?= $index == count($current_images)-1 ? 'disabled' : '' ?>><i class="fas fa-arrow-right"></i></button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12"><div class="alert alert-secondary">Using Default System Image (hero.jpg)</div></div>
                    <?php endif; ?>
                </div>

                <hr class="my-4">
                <h5 class="mb-3">Add New Image</h5>
                <div class="mb-3">
                    <label class="form-label">Select Image to Crop & Upload</label>
                    <input type="file" id="upload_input" class="form-control" accept="image/*" onchange="handleFileSelect(this)">
                    <small class="text-muted">Image will be cropped to 16:9 aspect ratio for perfect fit.</small>
                </div>
            </div>
        </div>
    </div>
        </main>
    </div>
</div>

<!-- CROP MODAL -->
<div class="modal fade" id="cropModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Crop Image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="edit-msg" class="alert alert-info py-1 small" style="display:none;"></div>
                <div class="img-container">
                    <img id="image-to-crop" src="">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="crop-and-upload">Crop & Upload</button>
            </div>
        </div>
    </div>
</div>

<form method="POST" id="cropped_form" style="display:none;">
    <input type="hidden" name="cropped_image_data" id="cropped_image_data">
    <input type="hidden" name="upload_cropped_hero" id="action_input" value="1">
    <input type="hidden" name="old_image_name" id="old_image_name">
</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
<script src="admin.js"></script>
<script>
    let cropper;
    const image = document.getElementById('image-to-crop');
    const input = document.getElementById('upload_input');
    const modal = new bootstrap.Modal(document.getElementById('cropModal'));
    let isEdit = false;

    function handleFileSelect(inputElement) {
        const files = inputElement.files;
        if (files && files.length > 0) {
            const file = files[0];
            const reader = new FileReader();
            reader.onload = function (e) {
                image.src = e.target.result;
                modal.show();
                if(cropper) cropper.destroy();
                setTimeout(() => {
                    cropper = new Cropper(image, { aspectRatio: 16 / 9, viewMode: 1 });
                }, 200);
            };
            reader.readAsDataURL(file);
        }
    }

    function editImage(imgName, type, imgSrc = null) {
        isEdit = true;
        document.getElementById('old_image_name').value = imgName;
        const msg = document.getElementById('edit-msg');
        msg.style.display = 'block';

        if(type === 'replace') {
            msg.innerText = "Select a new image to replace the existing one.";
            document.getElementById('upload_input').click();
        } else if(type === 'recrop') {
            msg.innerText = "Re-cropping the current image. Quality may decrease if zoomed in.";
            image.src = imgSrc;
            modal.show();
            if(cropper) cropper.destroy();
            setTimeout(() => {
                cropper = new Cropper(image, { aspectRatio: 16 / 9, viewMode: 1 });
            }, 200);
        }
    }

    document.getElementById('crop-and-upload').addEventListener('click', function () {
        const canvas = cropper.getCroppedCanvas({ width: 1920, height: 1080 }); // Force HD
        document.getElementById('cropped_image_data').value = canvas.toDataURL('image/png');
        
        if(isEdit) {
            document.getElementById('action_input').name = 'update_cropped_hero';
        } else {
            document.getElementById('action_input').name = 'upload_cropped_hero';
        }
        document.getElementById('cropped_form').submit();
    });

    document.getElementById('cropModal').addEventListener('hidden.bs.modal', function () {
        isEdit = false;
        document.getElementById('edit-msg').style.display = 'none';
        document.getElementById('action_input').name = 'upload_cropped_hero';
        document.getElementById('upload_input').value = '';
    });

    function confirmHeroDelete(form) {
        Swal.fire({
            title: 'Delete Image?',
            text: "Are you sure you want to remove this hero image?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) form.submit();
        });
    }
</script>
</body>
</html>