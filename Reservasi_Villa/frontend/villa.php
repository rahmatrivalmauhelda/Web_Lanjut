<?php
require_once __DIR__ . '/../config/functions.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header('Location: list_villas.php');
    exit;
}

$stmt = $conn->prepare('SELECT * FROM villas WHERE id=?');
$stmt->bind_param('i', $id);
$stmt->execute();
$villa = $stmt->get_result()->fetch_assoc();

if (!$villa) {
    echo 'Villa tidak ditemukan';
    exit;
}

$fstmt = $conn->prepare('SELECT f.name FROM facilities f 
                         JOIN villa_facilities vf ON vf.facility_id=f.id 
                         WHERE vf.villa_id=?');
$fstmt->bind_param('i', $id);
$fstmt->execute();
$fac_res = $fstmt->get_result();

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book'])) {
    require_login();

    $checkin = $_POST['checkin_date'];
    $checkout = $_POST['checkout_date'];
    $user_id = $_SESSION['user_id'];

    $d1 = new DateTime($checkin);
    $d2 = new DateTime($checkout);
    $days = max(1, $d1->diff($d2)->days);
    $total = $days * intval($villa['price']);

    $bst = $conn->prepare('INSERT INTO bookings (user_id,villa_id,checkin_date,checkout_date,total_price) 
                           VALUES (?,?,?,?,?)');
    $bst->bind_param('iissi', $user_id, $id, $checkin, $checkout, $total);

    if ($bst->execute()) {
        $msg = 'Booking berhasil dibuat. Silakan upload bukti pembayaran di Dashboard Anda.';
    } else {
        $msg = 'Gagal membuat booking: ' . $conn->error;
    }
}
?>
<!doctype html>
<html>

<head>
<meta charset="utf-8">
<title><?php echo esc($villa['name']); ?></title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

    body {
        background: linear-gradient(145deg, #001628, #003a57);
        font-family: 'Segoe UI', sans-serif;
        color: #eaf6ff;
        padding-bottom: 40px;
    }

    .container {
        max-width: 1100px;
        margin-top: 30px;
    }

    .villa-img {
        width: 100%;
        border-radius: 16px;
        box-shadow: 0 8px 28px rgba(0,0,0,0.55);
        margin-bottom: 25px;
    }

    h2 {
        font-weight: 800;
        color: #ffffff;
    }

    p, li {
        color: #d9e7f7;
        font-size: 16px;
    }

    /* CARD BOOKING GLASS */
    .booking-card {
        background: rgba(255,255,255,0.06);
        padding: 22px;
        border-radius: 16px;
        border: 1px solid rgba(255,255,255,0.08);
        backdrop-filter: blur(8px);
        box-shadow: 0 10px 35px rgba(0,0,0,0.45);
    }

    .form-control {
        background: rgba(255,255,255,0.08);
        border: 1px solid rgba(255,255,255,0.12);
        color: #fff;
    }

    .form-control:focus {
        background: rgba(255,255,255,0.15);
        color: #fff;
        border-color: #6cb8ff;
        box-shadow: 0 0 0 2px #6cb8ff;
    }

    label {
        font-weight: 600;
        color: rgba(255,255,255,0.9);
    }

    .btn-primary {
        background: linear-gradient(90deg, #2563eb, #06b6d4);
        border: none;
        padding: 12px;
        font-weight: 700;
        border-radius: 12px;
        color: #fff;
        box-shadow: 0 6px 18px rgba(0,0,0,0.4);
    }

    .btn-primary:hover {
        background: linear-gradient(90deg, #1d4ed8, #0891b2);
    }

    .btn-link {
        color: #8cd3ff;
        font-weight: 600;
    }

    .btn-link:hover {
        color: #b8e7ff;
    }

</style>

</head>

<body>

<div class="container">

    <a href="list_villas.php" class="btn btn-link">&larr; Kembali</a>

    <div class="row mt-3">
        <div class="col-md-8">

            <?php if ($villa['image']): ?>
                <img src="../uploads/<?php echo esc($villa['image']); ?>" class="villa-img">
            <?php endif; ?>

            <h2><?php echo esc($villa['name']); ?></h2>

            <p><?php echo nl2br(esc($villa['description'])); ?></p>

            <p><strong>Lokasi:</strong> <?php echo esc($villa['location']); ?></p>
            <p><strong>Harga/malam:</strong>
                <span style="color:#8cd3ff;font-weight:700;">
                    Rp <?php echo number_format($villa['price'], 0, ',', '.'); ?>
                </span>
            </p>

            <h5 class="mt-4" style="font-weight:700;">Fasilitas</h5>
            <ul>
                <?php while ($f = $fac_res->fetch_assoc()): ?>
                    <li><?php echo esc($f['name']); ?></li>
                <?php endwhile; ?>
            </ul>

        </div>

        <div class="col-md-4">

            <div class="booking-card">

                <?php if ($msg): ?>
                    <div class="alert alert-info"><?php echo esc($msg); ?></div>
                <?php endif; ?>

                <?php if (!is_logged_in()): ?>
                    <p>Silakan <a href="login.php" class="text-info">login</a> untuk melakukan booking.</p>

                <?php else: ?>

                    <form method="post">
                        <div class="mb-3">
                            <label>Check-in</label>
                            <input type="date" name="checkin_date" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label>Check-out</label>
                            <input type="date" name="checkout_date" class="form-control" required>
                        </div>

                        <button name="book" class="btn btn-primary w-100">
                            Booking Sekarang
                        </button>
                    </form>

                <?php endif; ?>

            </div>

        </div>
    </div>
</div>

</body>
</html>
