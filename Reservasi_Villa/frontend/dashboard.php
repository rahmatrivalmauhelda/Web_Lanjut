<?php
require_once __DIR__ . '/../config/functions.php';
require_login();

$msg = '';

// ============================
// Upload Bukti Pembayaran
// ============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_payment'])) {

    $booking_id = intval($_POST['booking_id']);

    $bst = $conn->prepare("SELECT * FROM bookings WHERE id=? AND user_id=?");
    $bst->bind_param("ii", $booking_id, $_SESSION['user_id']);
    $bst->execute();
    $b = $bst->get_result()->fetch_assoc();

    if (!$b) {
        $msg = "Booking tidak ditemukan.";
    } else {

        if (!empty($_FILES['proof']['name'])) {

            $tmp = $_FILES['proof']['tmp_name'];
            $fname = time() . "_" . basename($_FILES['proof']['name']);
            move_uploaded_file($tmp, __DIR__ . '/../uploads/' . $fname);

            $amount = intval($_POST['amount']);
            $method = $_POST['method'];

            $payment_method_id = intval($_POST['payment_method_id']);

            $ins = $conn->prepare("INSERT INTO booking_payments (booking_id, payment_method_id, amount, proof_image)VALUES (?,?,?,?)");
            $ins->bind_param("iiis", $booking_id, $payment_method_id, $amount, $fname);

            if ($ins->execute()) {
                $msg = "Bukti pembayaran berhasil diupload. Menunggu verifikasi admin.";
            } else {
                $msg = "Gagal menyimpan pembayaran: " . $conn->error;
            }
        } else {
            $msg = "Pilih file bukti pembayaran terlebih dahulu.";
        }
    }
}

// ============================
// Load Data Booking User
// ============================
$st = $conn->prepare("SELECT b.*, v.name AS villa_name 
                      FROM bookings b 
                      JOIN villas v ON v.id=b.villa_id 
                      WHERE b.user_id=? 
                      ORDER BY b.created_at DESC");
$st->bind_param("i", $_SESSION['user_id']);
$st->execute();
$bookings = $st->get_result();

// use local uploaded file path (from conversation history)
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Reservasi Saya</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --bg1: #001628;
            --bg2: #003a57;
            --panel: rgba(255, 255, 255, 0.04);
            --muted: rgba(235, 245, 255, 0.65);
            --accent: #1283d7;
            --accent-2: #06b6d4;
            --glass: rgba(255, 255, 255, 0.02);
        }

        html,
        body {
            height: 100%;
            margin: 0;
            font-family: 'Segoe UI', Inter, system-ui, -apple-system, Roboto, "Helvetica Neue", Arial;
            background: linear-gradient(145deg, var(--bg1), var(--bg2));
            color: #eaf6ff;
        }

        .app {
            display: flex;
            min-height: 100vh;
        }

        /* SIDEBAR */
        .sidebar {
            width: 220px;
            background: linear-gradient(180deg, #122b3a, #0f2030);
            padding: 22px 16px;
            color: #dbeafe;
            box-shadow: 0 6px 30px rgba(2, 6, 23, 0.6);
            border-right: 1px solid rgba(255, 255, 255, 0.06);
            position: relative;
        }

        .brand {
            display: flex;
            gap: 12px;
            align-items: center;
            margin-bottom: 18px;
        }

        .brand .logo {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            overflow: hidden;
            background: linear-gradient(135deg, #8be3ff, #2563eb);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 18px rgba(3, 10, 20, 0.6);
        }

        .brand .logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .brand .title {
            font-size: 14px;
            font-weight: 800;
            color: #fff;
        }

        .brand .sub {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.6);
        }

        .nav {
            margin-top: 8px;
        }

        .nav a {
            display: flex;
            gap: 10px;
            align-items: center;
            padding: 10px 12px;
            color: rgba(235, 245, 255, 0.92);
            border-radius: 8px;
            text-decoration: none;
            margin-bottom: 8px;
            font-weight: 600;
            transition: .12s ease;
        }

        .nav a svg {
            opacity: 0.95;
        }

        .nav a:hover {
            background: rgba(255, 255, 255, 0.04);
            color: #fff;
            transform: translateX(4px);
        }

        .nav a.active {
            background: rgba(255, 255, 255, 0.04);
            color: #fff;
            box-shadow: 0 8px 22px rgba(0, 0, 0, 0.45);
        }

        .sidebar .logout {
            position: absolute;
            bottom: 18px;
            left: 16px;
            right: 16px;
            font-size: 13px;
            color: rgba(255, 255, 255, 0.7);
        }

        .sidebar .logout a {
            display: inline-block;
            margin-top: 8px;
            padding: 8px 12px;
            background: rgba(255, 255, 255, 0.06);
            border-radius: 8px;
            color: #fff;
            text-decoration: none;
            font-weight: 700;
        }

        /* CONTENT */
        .content {
            flex: 1;
            padding: 28px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
        }

        .topbar h3 {
            margin: 0;
            font-weight: 700;
            color: #eaf6ff;
        }

        .topbar .muted {
            color: var(--muted);
        }

        .panel {
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.03), rgba(255, 255, 255, 0.02));
            border-radius: 14px;
            padding: 18px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.45);
            border: 1px solid rgba(255, 255, 255, 0.04);
            margin-bottom: 18px;
        }

        .table-card {
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.03), rgba(255, 255, 255, 0.02));
            padding: 16px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.04);
            box-shadow: 0 8px 28px rgba(0, 0, 0, 0.45);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            color: #eaf6ff;
        }

        thead th {
            background: rgba(0, 45, 95, 0.85);
            color: #fff;
            padding: 12px;
            text-align: left;
            font-weight: 700;
            border-bottom: none;
        }

        tbody td {
            padding: 12px;
            border-top: 1px solid rgba(255, 255, 255, 0.03);
            vertical-align: middle;
        }

        .badge-status {
            padding: 6px 10px;
            border-radius: 8px;
            font-size: 12px;
            display: inline-block;
            font-weight: 700;
        }

        .badge-wait {
            background: #f59e0b;
            color: #071423;
        }

        .badge-paid {
            background: #10b981;
            color: #02160e;
        }

        .upload-box {
            background: rgba(255, 255, 255, 0.02);
            padding: 10px;
            border-radius: 10px;
            border: 1px dashed rgba(255, 255, 255, 0.04);
        }

        .btn-primary {
            background: linear-gradient(90deg, var(--accent), var(--accent-2));
            border: none;
            color: #04202b;
            font-weight: 800;
        }

        .btn-outline {
            background: transparent;
            border: 1px solid rgba(255, 255, 255, 0.06);
            color: #eaf6ff;
            border-radius: 8px;
            padding: 6px 10px;
            text-decoration: none;
            font-weight: 700;
        }

        small {
            color: var(--muted);
        }

        @media (max-width: 992px) {
            .sidebar {
                display: none;
            }

            .content {
                padding: 18px;
            }

            table,
            thead,
            tbody,
            th,
            td,
            tr {
                display: block;
            }

            thead {
                display: none;
            }

            tbody tr {
                margin-bottom: 12px;
                border-radius: 8px;
                background: rgba(255, 255, 255, 0.02);
                padding: 10px;
            }

            tbody td {
                display: flex;
                justify-content: space-between;
                padding: 8px;
            }
        }
    </style>
</head>

<body>
    <div class="app">
        <!-- SIDEBAR -->
        <aside class="sidebar" aria-label="Sidebar menu">
            <div class="brand">
                <div class="logo">
                    C
                </div>
                <div>
                    <div class="title">Villa Alahan</div>
                    <div class="sub">Customer</div>
                </div>
            </div>

            <nav class="nav" role="navigation">
                <a href="list_villas.php">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 3l9 6v12H3V9l9-6z" />
                    </svg>
                    Lihat Villa
                </a>

                <a href="dashboard.php" class="active">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M7 10l5 5 5-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    Reservasi Saya
                </a>
            </nav>

            <div class="logout">
                <div style="font-size:13px;color:rgba(255,255,255,0.75);">Logged as <strong><?php echo htmlspecialchars($_SESSION['name'] ?? 'User'); ?></strong></div>
                <div style="margin-top:8px">
                    <a href="logout.php">Logout</a>
                </div>
            </div>
        </aside>

        <!-- CONTENT -->
        <main class="content">
            <div class="topbar">
                <div>
                    <h3>Reservasi Saya</h3>
                    <div class="muted">Ringkasan reservasi & pengelolaan pembayaran</div>
                </div>
            </div>

            <?php if ($msg): ?>
                <div class="panel">
                    <div class="small-muted"><?php echo esc($msg); ?></div>
                </div>
            <?php endif; ?>

            <div class="table-card">
                <h5 class="mb-3">Booking Saya</h5>
                <div class="table-wrap">
                    <table aria-describedby="Daftar booking">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Villa</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Payment</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($b = $bookings->fetch_assoc()):
                                $payment = $conn->query(
                                    "SELECT * FROM booking_payments 
                                     WHERE booking_id=" . intval($b['id']) . " 
                                     ORDER BY id DESC LIMIT 1"
                                )->fetch_assoc();
                            ?>
                                <tr>
                                    <td><?php echo intval($b['id']); ?></td>
                                    <td><?php echo esc($b['villa_name']); ?></td>
                                    <td><?php echo esc($b['checkin_date']); ?></td>
                                    <td><?php echo esc($b['checkout_date']); ?></td>

                                    <td><strong>Rp <?php echo number_format($b['total_price'], 0, ',', '.'); ?></strong></td>

                                    <td>
                                        <?php if ($b['payment_status'] === 'paid'): ?>
                                            <span class="badge-status badge-paid">Lunas / Verified</span>
                                        <?php else: ?>
                                            <span class="badge-status badge-wait">Menunggu / Belum Verifikasi</span>
                                        <?php endif; ?>
                                    </td>

                                    <td style="min-width:250px;">
                                        <?php if ($payment): ?>

                                            <div style="margin-bottom:6px;">Sudah Upload</div>
                                            <?php if (!empty($payment['proof_image']) && file_exists(__DIR__ . '/../uploads/' . $payment['proof_image'])): ?>
                                                <a target="_blank" class="btn-outline" href="../uploads/<?php echo esc($payment['proof_image']); ?>">Lihat Bukti</a>
                                            <?php endif; ?>
                                            <div class="muted" style="margin-top:6px;">Rp <?php echo number_format($payment['amount'], 0, ',', '.'); ?></div>

                                            <?php if ($b['payment_status'] !== 'paid'): ?>
                                                <div class="small-muted text-warning" style="margin-top:6px">Menunggu verifikasi admin</div>
                                            <?php else: ?>
                                                <div class="small-muted text-success" style="margin-top:6px">Diverifikasi</div>
                                            <?php endif; ?>

                                        <?php else: ?>

                                            <form method="post" enctype="multipart/form-data" class="upload-box">
                                                <input type="hidden" name="booking_id" value="<?php echo intval($b['id']); ?>">

                                                <div class="mb-2">
                                                    <input type="number" name="amount"
                                                        value="<?php echo intval($b['total_price']); ?>"
                                                        class="form-control" required>
                                                </div>

                                                <div class="mb-2">
                                                    <select name="method" class="form-select" required>
                                                        <option value="transfer">Transfer</option>
                                                        <option value="cod">COD</option>
                                                    </select>
                                                </div>

                                                <div class="mb-2">
                                                    <input type="file" name="proof" class="form-control" accept="image/*" required>
                                                </div>

                                                <button name="upload_payment" class="btn btn-primary w-100 btn-sm">
                                                    Upload Bukti Pembayaran
                                                </button>
                                            </form>

                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>
</body>

</html>