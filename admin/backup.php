<?php
session_start();
include("../db.php");
date_default_timezone_set('Asia/Manila');

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

// --- CONFIGURATION ---
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "woke_coliving";
$mysqldump_path = "C:\\xampp\\mysql\\bin\\mysqldump";

$backup_dir = "../backups/";
$archive_dir = "../backups/archive/";
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0777, true);
}
if (!file_exists($archive_dir)) {
    mkdir($archive_dir, 0777, true);
}

// Auto archive files older than 30 days
$files = scandir($backup_dir);
foreach($files as $f){
    if($f != '.' && $f != '..' && !is_dir($backup_dir . $f)){
        if(filemtime($backup_dir . $f) < strtotime('-30 days')){
            rename($backup_dir . $f, $archive_dir . $f);
        }
    }
}

$message = "";
$error = "";

// Handle Password Protected DB Backup
if(isset($_POST['action']) && $_POST['action'] == 'backup_db') {
    $admin_user = $_SESSION['admin_username'];
    $admin_pass = $_POST['admin_password'] ?? '';
    
    $q = mysqli_query($conn, "SELECT password FROM admin WHERE username='$admin_user'");
    $row = mysqli_fetch_assoc($q);
    
    if (!$row || $row['password'] !== $admin_pass) {
        $error = "Incorrect password. Database backup failed.";
    } else {
        $date = date("Y-m-d_H-i-s");
        $filename = "db_backup_$date.sql";
        $filepath = $backup_dir . $filename;

        $password_param = !empty($db_pass) ? "-p" . escapeshellarg($db_pass) : "";
        $command = sprintf('"%s" -h %s -u %s %s %s > "%s"', $mysqldump_path, escapeshellarg($db_host), escapeshellarg($db_user), $password_param, escapeshellarg($db_name), $filepath);

        system($command, $return_var);

        if($return_var === 0 && file_exists($filepath)) {
            $message = "Database backup created successfully.";
        } else {
            $error = "Database backup failed. Check mysqldump path.";
        }
    }
}

// Handle Actions
if(isset($_GET['action'])) {
    if($_GET['action'] == 'backup_files') {
        if(class_exists('ZipArchive')) {
            $date = date("Y-m-d_H-i-s");
            $filename = "source_code_$date.zip";
            $filepath = $backup_dir . $filename;
            
            $rootPath = realpath('../');
            $zip = new ZipArchive();
            
            if ($zip->open($filepath, ZipArchive::CREATE | ZipArchive::OVERWRITE)) {
                $files = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($rootPath),
                    RecursiveIteratorIterator::LEAVES_ONLY
                );

                foreach ($files as $name => $file) {
                    if (!$file->isDir()) {
                        $filePath = $file->getRealPath();
                        $relativePath = substr($filePath, strlen($rootPath) + 1);
                        // Exclude backups folder
                        if (strpos($filePath, 'backups') === false && strpos($filePath, '.git') === false) {
                            $zip->addFile($filePath, $relativePath);
                        }
                    }
                }
                $zip->close();
                $message = "Source code archive created successfully.";
            } else {
                $error = "Failed to create zip file.";
            }
        } else {
            $error = "ZipArchive extension missing.";
        }
    }
    elseif($_GET['action'] == 'delete' && isset($_GET['file'])) {
        $file = basename($_GET['file']);
        $is_arch = isset($_GET['archived']) && $_GET['archived'] == 1;
        $del_path = $is_arch ? $archive_dir . $file : $backup_dir . $file;
        if(file_exists($del_path)) {
            unlink($del_path);
            $message = "Backup deleted.";
        }
    }
}

// Fetch Backups
$backups = [];
$filter_type = isset($_GET['type']) ? $_GET['type'] : 'all';
$current_dir = ($filter_type == 'archived') ? $archive_dir : $backup_dir;

if(is_dir($current_dir)){
    $files = scandir($current_dir);
    foreach($files as $f){
        if($f != '.' && $f != '..' && !is_dir($current_dir . $f)){
            $is_sql = (strpos($f, '.sql') !== false);
            
            if($filter_type == 'sql' && !$is_sql) continue;
            if($filter_type == 'zip' && $is_sql) continue;

            $backups[] = [
                'file' => $f,
                'size' => round(filesize($current_dir . $f) / 1024, 2) . ' KB',
                'date' => date("M d, Y H:i", filemtime($current_dir . $f)),
                'type' => $is_sql ? 'Database' : 'Source Code',
                'is_archived' => ($filter_type == 'archived')
            ];
        }
    }
    usort($backups, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
    
    // Limit Logic
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    if($limit > 0 && count($backups) > $limit){
        $backups = array_slice($backups, 0, $limit);
    }
}

// --- Display result within the admin template ---
// Fetch Pending Counts for Sidebar
$pending_res_q = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM reservations WHERE status IN ('Pending', 'Verifying')"))['c'];
$pending_pay_q = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM payments WHERE payment_status='Unpaid' AND proof_image IS NOT NULL"))['c'];
$pending_res = $pending_res_q + $pending_pay_q;
$pending_maint = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM maintenance_requests WHERE status='Pending'"))['c'];
$pending_house = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM housekeeping_requests WHERE status='Pending'"))['c'];
$del_req_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM account_deletion_requests WHERE status='Pending'"))['c'];

$theme = get_theme_colors($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Backup & Archive | Dormitory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="admin.css">
</head>
<body>
<div class="dashboard-container">
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-wrapper">
        <?php include 'admin_topbar.php'; ?>
        <main class="main-content">
            <div class="page-header">
                <h1>System Backup & Archive</h1>
            </div>
            
            <?php if($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?= $message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card bg-white shadow-sm border-0 rounded-4 p-4">
                <div class="d-flex gap-3 mb-4">
                    <button type="button" onclick="confirmBackupDb()" class="btn btn-success fw-bold"><i class="fas fa-database me-2"></i>Backup Database (SQL)</button>
                    <a href="?action=backup_files" class="btn btn-warning text-dark fw-bold"><i class="fas fa-file-archive me-2"></i>Source Code (ZIP)</a>
                </div>
                <form id="backupDbForm" method="POST" style="display:none;">
                    <input type="hidden" name="action" value="backup_db">
                    <input type="hidden" name="admin_password" id="backupDbPassword">
                </form>
                
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">Existing Backups</h5>
                    <form method="GET" class="d-flex align-items-center gap-2">
                        <label class="fw-bold small">Type:</label>
                        <select name="type" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                            <option value="all" <?= $filter_type == 'all' ? 'selected' : '' ?>>All Recent</option>
                            <option value="sql" <?= $filter_type == 'sql' ? 'selected' : '' ?>>Database</option>
                            <option value="zip" <?= $filter_type == 'zip' ? 'selected' : '' ?>>Source Code</option>
                            <option value="archived" <?= $filter_type == 'archived' ? 'selected' : '' ?>>Archived (> 30 Days)</option>
                        </select>

                        <label class="fw-bold small ms-2">Show:</label>
                        <select name="limit" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                            <option value="5" <?= $limit == 5 ? 'selected' : '' ?>>5</option>
                            <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>10</option>
                            <option value="20" <?= $limit == 20 ? 'selected' : '' ?>>20</option>
                            <option value="0" <?= $limit == 0 ? 'selected' : '' ?>>All</option>
                        </select>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Filename</th>
                                <th>Type</th>
                                <th>Size</th>
                                <th>Date Created</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($backups as $b): 
                                $file_path = $b['is_archived'] ? $archive_dir . $b['file'] : $backup_dir . $b['file'];
                                $del_url = "?action=delete&file=" . urlencode($b['file']) . ($b['is_archived'] ? "&archived=1" : "");
                            ?>
                            <tr>
                                <td class="fw-bold"><?= $b['file'] ?></td>
                                <td><span class="badge <?= $b['type'] == 'Database' ? 'bg-primary' : 'bg-warning text-dark' ?>"><?= $b['type'] ?></span></td>
                                <td><?= $b['size'] ?></td>
                                <td><?= $b['date'] ?></td>
                                <td class="text-end">
                                    <a href="<?= $file_path ?>" class="btn btn-sm btn-outline-primary me-1" download><i class="fas fa-download"></i></a>
                                    <a href="<?= $del_url ?>" class="btn btn-sm btn-outline-danger" onclick="confirmLink(event, this.href, 'Delete this backup?')"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($backups)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-3">No backups found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
        </main>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="admin.js"></script>
<script>
function confirmBackupDb() {
    Swal.fire({
        title: 'Security Verification',
        text: 'Please enter your admin password to backup the database.',
        input: 'password',
        inputAttributes: { autocapitalize: 'off' },
        showCancelButton: true,
        confirmButtonText: 'Backup Database',
        confirmButtonColor: '#2e7d32',
        preConfirm: (password) => {
            if (!password) Swal.showValidationMessage('Password is required');
            return password;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('backupDbPassword').value = result.value;
            document.getElementById('backupDbForm').submit();
        }
    });
}

function confirmLink(e, url, msg) {
    e.preventDefault();
    Swal.fire({
        title: 'Are you sure?',
        text: msg,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) window.location.href = url;
    });
}

// Notification Sound & Auto Refresh Logic
let lastUpdate = 0;
function checkUpdates() {
    fetch('../check_updates.php')
    .then(r => r.text())
    .then(t => {
        if(lastUpdate == 0) { lastUpdate = t; } 
        else if (t > lastUpdate) { sessionStorage.setItem('playNotifSound', 'true'); location.reload(); }
    });
}
setInterval(checkUpdates, 3000);

if(sessionStorage.getItem('playNotifSound') === 'true') {
    let audio = new Audio('../assets/sounds/notification.mp3');
    audio.onerror = () => { new Audio('../assets/sounds/woke_coliving_alert.wav').play().catch(e=>{}); };
    audio.play().catch(e => console.warn('Audio autoplay blocked by browser:', e));
    sessionStorage.removeItem('playNotifSound');
}
</script>
</body>
</html>