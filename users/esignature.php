<?php
include '../db.php';
session_start();

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
if (!isset($_GET['id'])) { header("Location: my_reservations.php"); exit; }

$reservation_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Verify ownership and status
$check = mysqli_query($conn, "SELECT * FROM reservations WHERE reservation_id=$reservation_id AND user_id=$user_id AND status IN ('Approved', 'Verifying')");
if(mysqli_num_rows($check) == 0){
    echo "Invalid reservation or not approved yet.";
    exit;
}

if(isset($_POST['signature_data'])){
    $data = $_POST['signature_data'];
    // Remove header
    $data = str_replace('data:image/png;base64,', '', $data);
    $data = str_replace(' ', '+', $data);
    $img_data = base64_decode($data);
    
    $filename = 'sig_' . $reservation_id . '_' . time() . '.png';
    $target_dir = '../assets/signatures/';
    
    if(!is_dir($target_dir)) mkdir($target_dir, 0777, true);
    
    file_put_contents($target_dir . $filename, $img_data);
    
    try {
        mysqli_query($conn, "UPDATE reservations SET signature_image='$filename' WHERE reservation_id=$reservation_id");
        
        // Log Activity
        log_activity($conn, $user_id, "Lease Signed", "Reservation #$reservation_id");
    } catch (Exception $e) {}
    header("Location: my_reservations.php?signed=1");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Sign Lease | Dormitory</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        canvas { border: 2px dashed #ccc; cursor: crosshair; background: #fff; }

        /* Night Mode Styles */
        body.theme-transition { transition: background-color 0.3s ease, color 0.3s ease; }
        body.night-mode, body.night-mode.bg-light { background-color: #121212 !important; color: #e0e0e0 !important; }
        body.night-mode .card { background-color: #1e1e1e !important; color: #e0e0e0 !important; border-color: #333 !important; }
        body.night-mode .text-muted { color: #b0b0b0 !important; }
        body.night-mode canvas { background-color: #121212 !important; border-color: #444 !important; }
        body.night-mode::-webkit-scrollbar, body.night-mode *::-webkit-scrollbar { width: 8px; height: 8px; }
        body.night-mode::-webkit-scrollbar-track, body.night-mode *::-webkit-scrollbar-track { background: #121212 !important; }
        body.night-mode::-webkit-scrollbar-thumb, body.night-mode *::-webkit-scrollbar-thumb { background: #333 !important; border-radius: 4px; }
        body.night-mode::-webkit-scrollbar-thumb:hover, body.night-mode *::-webkit-scrollbar-thumb:hover { background: #34B875 !important; }
        body.night-mode .btn-secondary { background-color: #333 !important; border-color: #444 !important; color: #e0e0e0 !important; }
        body.night-mode .btn-secondary:hover { background-color: #444 !important; color: #fff !important; }
        body.night-mode .swal2-popup { background-color: #1e1e1e !important; color: #e0e0e0 !important; }
        body.night-mode .swal2-title, body.night-mode .swal2-html-container { color: #e0e0e0 !important; }
    </style>
</head>
<body class="bg-light <?= (isset($_SESSION['night_mode']) && $_SESSION['night_mode'] == 1) ? 'night-mode' : '' ?>">
<script>
    (function() {
        const currentUserId = "<?= $_SESSION['user_id'] ?? '' ?>";
        const nightModeKey = currentUserId ? 'nightMode_' + currentUserId : 'nightMode';
        if (localStorage.getItem(nightModeKey) === 'enabled') document.body.classList.add('night-mode');
        else if (localStorage.getItem(nightModeKey) === 'disabled') document.body.classList.remove('night-mode');
    })();
</script>
    <div class="container mt-5">
        <div class="card shadow-sm p-4 mx-auto" style="max-width: 600px;">
            <h3 class="text-center mb-3">Sign Your Lease</h3>
            <p class="text-muted text-center">Please sign in the box below to confirm your reservation.</p>
            
            <div class="text-center">
                <canvas id="sig-canvas" width="500" height="200"></canvas>
            </div>
            
            <div class="d-flex justify-content-between mt-3">
                <button class="btn btn-secondary" id="clear-btn">Clear</button>
                <form method="POST" id="sig-form" action="esignature.php?id=<?= $reservation_id ?>">
                    <input type="hidden" name="signature_data" id="sig-data">
                    <button type="button" class="btn btn-success" id="save-btn">Submit Signature</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        var canvas = document.getElementById("sig-canvas");
        var ctx = canvas.getContext("2d");
        var drawing = false;
        var hasSigned = false;

        function getPos(canvas, evt) {
            var rect = canvas.getBoundingClientRect();
            var clientX = evt.clientX;
            var clientY = evt.clientY;
            if (evt.touches && evt.touches.length > 0) {
                clientX = evt.touches[0].clientX;
                clientY = evt.touches[0].clientY;
            }
            return {
                x: clientX - rect.left,
                y: clientY - rect.top
            };
        }

        function startDraw(e) {
            if(e.type === 'touchstart') e.preventDefault();
            drawing = true;
            var pos = getPos(canvas, e);
            ctx.beginPath();
            ctx.moveTo(pos.x, pos.y);
        }

        function endDraw(e) {
            if(e.type === 'touchend') e.preventDefault();
            drawing = false;
        }

        function draw(e) {
            if(e.type === 'touchmove') e.preventDefault();
            if(!drawing) return;
            hasSigned = true;
            var pos = getPos(canvas, e);
            ctx.lineWidth = 2;
            ctx.lineCap = "round";
            ctx.strokeStyle = document.body.classList.contains('night-mode') ? "#fff" : "#000";
            ctx.lineTo(pos.x, pos.y);
            ctx.stroke();
        }

        // Mouse Events
        canvas.addEventListener("mousedown", startDraw);
        canvas.addEventListener("mouseup", endDraw);
        canvas.addEventListener("mousemove", draw);

        // Touch Events (Mobile Support)
        canvas.addEventListener("touchstart", startDraw, {passive: false});
        canvas.addEventListener("touchend", endDraw, {passive: false});
        canvas.addEventListener("touchmove", draw, {passive: false});

        // Clear
        document.getElementById("clear-btn").addEventListener("click", function(){
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            hasSigned = false;
        });

        // Save
        document.getElementById("save-btn").addEventListener("click", function(){
            if(!hasSigned) {
                Swal.fire({
                    title: 'Signature Required',
                    text: 'Please sign in the box before submitting.',
                    icon: 'warning',
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'OK'
                });
                return;
            }

            // If night mode, convert the white strokes to black so the saved receipt remains printable
            if (document.body.classList.contains('night-mode')) {
                var imgData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                var data = imgData.data;
                for (var i = 0; i < data.length; i += 4) {
                    if (data[i+3] > 0) { // If pixel is drawn (not transparent)
                        data[i] = 0; data[i+1] = 0; data[i+2] = 0; // Turn to solid black
                    }
                }
                ctx.putImageData(imgData, 0, 0);
            }

            // Fix: Fill background with white to prevent black image on transparency
            var compositeOperation = ctx.globalCompositeOperation;
            ctx.globalCompositeOperation = "destination-over";
            ctx.fillStyle = "#ffffff";
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.globalCompositeOperation = compositeOperation; // Restore

            var dataUrl = canvas.toDataURL("image/png");
            document.getElementById("sig-data").value = dataUrl;
            document.getElementById("sig-form").submit();
        });

        // Night Mode Logic
        const currentUserId = "<?= $user_id ?>";
        if(localStorage.getItem('nightMode_' + currentUserId) === 'enabled') {
            document.body.classList.add('night-mode');
        }
        window.addEventListener('storage', (e) => {
            if (e.key === 'nightMode_' + currentUserId) {
                if (e.newValue === 'enabled') document.body.classList.add('night-mode');
                else document.body.classList.remove('night-mode');
                ctx.clearRect(0, 0, canvas.width, canvas.height); // Clear to prevent stroke color mismatch if theme changes
                hasSigned = false;
            }
        });
    </script>
</body>
</html>