<?php
session_start();
include("../db.php");

// Only allow admin
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
    header("Location: admin_login.php");
    exit;
}

// --- CONFIGURATION ---
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "woke_coliving";
$mysqldump_path = "C:\\xampp\\mysql\\bin\\mysqldump";

$backup_dir = "../backups/";
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0777, true);
}

$message = "";
$error = "";

// Handle Actions
if(isset($_GET['action'])) {
    if($_GET['action'] == 'backup_db') {
        $date = date("Y-m-d_H-i-s");
        $filename = "db_backup_$date.sql";
        $filepath = $backup_dir . $filename;

        $password_param = !empty($db_pass) ? "-p" . escapeshellarg($db_pass) : "";
        $command = sprintf(
            '"%s" -h %s -u %s %s %s > "%s"',
            $mysqldump_path,
            escapeshellarg($db_host),
            escapeshellarg($db_user),
            $password_param,
            escapeshellarg($db_name),
            $filepath
        );

        system($command, $return_var);

        if($return_var === 0 && file_exists($filepath)) {
            $message = "Database backup created successfully.";
        } else {
            $error = "Database backup failed. Check mysqldump path.";
        }
    }
    elseif($_GET['action'] == 'backup_files') {
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
        if(file_exists($backup_dir . $file)) {
            unlink($backup_dir . $file);
            $message = "Backup deleted.";
        }
    }
}

// Fetch Backups
$backups = [];
if(is_dir($backup_dir)){
    $files = scandir($backup_dir);
    foreach($files as $f){
        if($f != '.' && $f != '..'){
            $backups[] = [
                'file' => $f,
                'size' => round(filesize($backup_dir . $f) / 1024, 2) . ' KB',
                'date' => date("M d, Y H:i", filemtime($backup_dir . $f)),
                'type' => (strpos($f, '.sql') !== false) ? 'Database' : 'Source Code'
            ];
        }
    }
    usort($backups, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
}

// --- Display result within the admin template ---
$theme = get_theme_colors($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Backup & Archive | Woke Coliving INC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-green: <?= $theme['primary'] ?>;
            --dark-green: <?= $theme['dark'] ?>;
            --accent-yellow: <?= $theme['accent'] ?>;
            --light-bg: #f8f9fa;
        }
        body { font-family: 'Poppins', sans-serif; background-color: var(--light-bg); }
        h1, h2, h3, h4, h5 { font-family: 'Playfair Display', serif; }
        
        #wrapper { display: flex; width: 100%; }
        #sidebar-wrapper { width: 260px; background-color: var(--dark-green); flex-shrink: 0; position: sticky; top: 0; height: 100vh; overflow-y: auto; transition: margin 0.25s ease-out; }
        #wrapper.toggled #sidebar-wrapper { margin-left: -250px; }
        @media (max-width: 768px) {
            #sidebar-wrapper { margin-left: -250px; }
            #wrapper.toggled #sidebar-wrapper { margin-left: 0; }
        }
        #page-content-wrapper { flex-grow: 1; }
        .sidebar-link { color: rgba(255,255,255,0.8); text-decoration: none; padding: 15px 25px; display: block; font-weight: 500; border-left: 5px solid transparent; transition: 0.3s; }
        .sidebar-link:hover, .sidebar-link.active { color: var(--dark-green); background-color: var(--accent-yellow); border-left-color: white; font-weight: 600; }
        .sidebar-brand { color: var(--accent-yellow); font-family: 'Playfair Display', serif; font-weight: bold; font-size: 1.3rem; padding: 25px; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.1); cursor: pointer; }
        
        .card-custom { border: none; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); background: white; }
        .reveal { opacity: 0; transform: translateY(30px); animation: fadeInUp 0.8s forwards; }
        @keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }
        
        #menu-toggle { display: none; }
        #wrapper.toggled #menu-toggle { display: inline-block; }
        @media (max-width: 768px) {
            #menu-toggle { display: inline-block; }
            #wrapper.toggled #menu-toggle { display: none; }
        }
    </style>
</head>
<body>
<div id="wrapper">
    <!-- Sidebar -->
    <div id="sidebar-wrapper">
        <div class="sidebar-brand" id="sidebar-toggle">
            <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" style="width: 35px; height: 35px; object-fit: cover;" class="me-2 rounded-circle border border-2 border-warning">
            Woke Coliving
        </div>
        <div class="list-group list-group-flush py-3">
            <a href="admin_dashboard.php" class="sidebar-link"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
            <a href="booking_management.php" class="sidebar-link"><i class="fas fa-calendar-check me-2"></i>Bookings</a>
            <a href="admin_rooms.php" class="sidebar-link"><i class="fas fa-bed me-2"></i>Manage Rooms</a>
            
            <a href="#utilitiesSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button" aria-expanded="true" aria-controls="utilitiesSubmenu">
                <span><i class="fas fa-tools me-2"></i>Utilities</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse show" id="utilitiesSubmenu">
                <a href="longterm_billing.php" class="sidebar-link ps-5"><i class="fas fa-file-invoice-dollar me-2"></i>Billing</a>
                <a href="admin_maintenance.php" class="sidebar-link ps-5"><i class="fas fa-wrench me-2"></i>Maintenance</a>
                <a href="admin_housekeeping.php" class="sidebar-link ps-5"><i class="fas fa-broom me-2"></i>Housekeeping</a>
                <a href="admin_utilities.php" class="sidebar-link ps-5"><i class="fas fa-archive me-2"></i>Utilities Archive</a>
                <a href="backup.php" class="sidebar-link ps-5 active"><i class="fas fa-database me-2"></i>Backup</a>
            </div>

            <a href="manage_hero.php" class="sidebar-link"><i class="fas fa-image me-2"></i>Hero Image</a>
            <a href="profit_report.php" class="sidebar-link"><i class="fas fa-chart-line me-2"></i>Profit Report</a>
            
            <a href="#settingsSubmenu" data-bs-toggle="collapse" class="sidebar-link d-flex justify-content-between align-items-center" role="button" aria-expanded="false" aria-controls="settingsSubmenu">
                <span><i class="fas fa-cog me-2"></i>Settings</span>
                <i class="fas fa-chevron-down small"></i>
            </a>
            <div class="collapse" id="settingsSubmenu">
                <a href="admin_profile.php" class="sidebar-link ps-5"><i class="fas fa-user-shield me-2"></i>Admin Profile</a>
                <a href="system_logs.php" class="sidebar-link ps-5"><i class="fas fa-list-alt me-2"></i>System Logs</a>
            </div>

            <a href="admin_logout.php" class="sidebar-link text-warning mt-4"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
        </div>
    </div>

    <!-- Page Content -->
    <div id="page-content-wrapper">
        <div class="container-fluid px-4 py-4 reveal">
            <div class="d-flex align-items-center mb-4">
                <a href="#" id="menu-toggle" class="text-decoration-none me-3" title="Toggle Menu">
                    <img src="../Images/WokeLogo.jpg?v=<?= time() ?>" style="width: 35px; height: 35px; object-fit: cover;" class="rounded-circle shadow-sm">
                </a>
                <h4 class="fw-bold mb-0" style="color: var(--dark-green);">System Backup & Archive</h4>
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

            <div class="card card-custom p-4">
                <div class="d-flex gap-3 mb-4">
                    <a href="?action=backup_db" class="btn btn-success fw-bold"><i class="fas fa-database me-2"></i>Backup Database (SQL)</a>
                    <a href="?action=backup_files" class="btn btn-warning text-dark fw-bold"><i class="fas fa-file-archive me-2"></i>Archive Source Code (ZIP)</a>
                </div>
                
                <h5 class="fw-bold mb-3">Existing Backups</h5>
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
                            <?php foreach($backups as $b): ?>
                            <tr>
                                <td class="fw-bold"><?= $b['file'] ?></td>
                                <td><span class="badge <?= $b['type'] == 'Database' ? 'bg-primary' : 'bg-warning text-dark' ?>"><?= $b['type'] ?></span></td>
                                <td><?= $b['size'] ?></td>
                                <td><?= $b['date'] ?></td>
                                <td class="text-end">
                                    <a href="<?= $backup_dir . $b['file'] ?>" class="btn btn-sm btn-outline-primary me-1" download><i class="fas fa-download"></i></a>
                                    <a href="?action=delete&file=<?= $b['file'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this backup?')"><i class="fas fa-trash"></i></a>
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
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleMenu(e) {
    if(e) e.preventDefault();
    document.getElementById("wrapper").classList.toggle("toggled");
}
document.getElementById("menu-toggle").addEventListener("click", toggleMenu);
document.getElementById("sidebar-toggle").addEventListener("click", toggleMenu);

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(event) {
    var sidebar = document.getElementById('sidebar-wrapper');
    var toggle = document.getElementById('menu-toggle');
    var wrapper = document.getElementById('wrapper');
    
    if (window.innerWidth <= 768 && wrapper.classList.contains('toggled')) {
        if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
            wrapper.classList.remove('toggled');
        }
    }
});
</script>
</body>
</html>