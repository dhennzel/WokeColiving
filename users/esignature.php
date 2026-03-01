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
    <title>Sign Lease | Woke Coliving</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        canvas { border: 2px dashed #ccc; cursor: crosshair; background: #fff; }

        /* Night Mode Styles */
        body.night-mode { background-color: #121212 !important; color: #e0e0e0; }
        body.night-mode .card { background-color: #1e1e1e; color: #e0e0e0; border-color: #333; }
        body.night-mode .text-muted { color: #b0b0b0 !important; }
        /* Canvas remains white for signature contrast */
    </style>
</head>
<body class="bg-light">
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
            ctx.strokeStyle = "#000";
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
        if(localStorage.getItem('nightMode') === 'enabled') {
            document.body.classList.add('night-mode');
        }
        window.addEventListener('storage', (e) => {
            if (e.key === 'nightMode') {
                if (e.newValue === 'enabled') document.body.classList.add('night-mode');
                else document.body.classList.remove('night-mode');
            }
        });
    </script>
</body>
</html>