<?php
require_once __DIR__ . '/../config/functions.php';
require_admin();

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($id > 0) {
        $p = $conn->query('SELECT * FROM booking_payments WHERE booking_id=' . $id)->fetch_assoc();
        if ($p) {
            if (!empty($p['proof_image']) && file_exists(__DIR__ . '/../uploads/' . $p['proof_image'])) {
                @unlink(__DIR__ . '/../uploads/' . $p['proof_image']);
            }
            $st = $conn->prepare('DELETE FROM booking_payments WHERE booking_id = ?');
            $st->bind_param('i', $id);
            $st->execute();
        }

        // hapus booking
        $st2 = $conn->prepare('DELETE FROM bookings WHERE id = ?');
        $st2->bind_param('i', $id);
        $st2->execute();

        // redirect supaya URL bersih dan mencegah penghapusan berulang saat refresh
        header('Location: bookings.php?msg=' . urlencode('Booking berhasil dihapus'));
        exit;
    }
}

if (isset($_GET['verify'])) {
    $id = intval($_GET['verify']);
    if ($id > 0) {
        $st = $conn->prepare("UPDATE bookings SET status='verified' WHERE id = ?");
        $st->bind_param('i', $id);
        $st->execute();
        header('Location: bookings.php?msg=' . urlencode('Booking diverifikasi'));
        exit;
    }
}

if (isset($_GET['verify_payment'])) {
    $pid = intval($_GET['verify_payment']);
    if ($pid > 0) {
        $p = $conn->query('SELECT * FROM booking_payments WHERE id=' . $pid)->fetch_assoc();
        if ($p) {
            $st = $conn->prepare("UPDATE booking_payments SET paid_at = NOW() WHERE id = ?");
            $st->bind_param('i', $pid);
            $st->execute();

            $st2 = $conn->prepare("UPDATE bookings SET payment_status='paid' WHERE id = ?");
            $bId = intval($p['booking_id']);
            $st2->bind_param('i', $bId);
            $st2->execute();
        }
        header('Location: bookings.php?msg=' . urlencode('Pembayaran diverifikasi'));
        exit;
    }
}

// ambil pesan (opsional)
$msg = '';
if (isset($_GET['msg']) && $_GET['msg'] !== '') {
    $msg = $_GET['msg'];
}

// ambil data bookings
$res = $conn->query('SELECT b.*, u.name as user_name, v.name as villa_name 
                     FROM bookings b 
                     JOIN users u ON u.id=b.user_id 
                     JOIN villas v ON v.id=b.villa_id 
                     ORDER BY b.created_at DESC');
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Kelola Booking</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --bg-1: #001628;
            --bg-2: #003a57;
            --panel: rgba(255, 255, 255, 0.04);
            --muted: rgba(235, 245, 255, 0.75);
        }

        html,
        body {
            height: 100%;
            margin: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(145deg, var(--bg-1), var(--bg-2));
            color: #e8f6ff;
        }

        .app {
            display: flex;
            min-height: 100vh;
        }

        /* SIDEBAR (consistent) */
        .sidebar {
            width: 220px;
            background: linear-gradient(180deg, #122b3a, #0f2030);
            padding: 20px;
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
            box-shadow: 0 8px 18px rgba(3, 10, 20, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .brand .logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .brand .title {
            font-weight: 800;
            color: #fff;
        }

        .brand .sub {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.6);
        }

        .nav {
            margin-top: 6px;
        }

        .nav a {
            display: block;
            color: var(--muted);
            padding: 8px 10px;
            border-radius: 8px;
            text-decoration: none;
            margin-bottom: 6px;
            font-weight: 600;
            transition: .12s ease;
        }

        .nav a:hover {
            transform: translateX(6px);
            color: #fff;
            background: rgba(255, 255, 255, 0.04);
        }

        .nav a.active {
            background: rgba(255, 255, 255, 0.04);
            color: #fff;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.02);
        }

        .sidebar .logout {
            position: absolute;
            bottom: 18px;
            left: 20px;
            right: 20px;
            font-size: 13px;
            color: rgba(255, 255, 255, 0.75);
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
            padding: 26px;
        }

        .panel {
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.03), rgba(255, 255, 255, 0.02));
            padding: 18px;
            border-radius: 14px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.45);
            border: 1px solid rgba(255, 255, 255, 0.04);
            margin-bottom: 18px;
        }

        .panel h3 {
            margin: 0;
            color: #eaf6ff;
        }

        .panel .muted {
            color: var(--muted);
        }

        .card-base {
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.03), rgba(255, 255, 255, 0.02));
            border-radius: 14px;
            padding: 14px;
            border: 1px solid rgba(255, 255, 255, 0.04);
            box-shadow: 0 8px 22px rgba(0, 0, 0, 0.45);
        }

        /* table styling */
        .table-wrap {
            margin-top: 8px;
            border-radius: 12px;
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            color: #eaf6ff;
            background: rgba(0, 22, 50, 0.6);
        }

        thead th {
            background: rgba(0, 45, 95, 0.85);
            color: #fff;
            font-weight: 700;
            padding: 12px 14px;
            text-align: left;
        }

        tbody td {
            padding: 12px 14px;
            border-top: 1px solid rgba(255, 255, 255, 0.03);
            vertical-align: middle;
        }

        tbody tr:hover {
            background: rgba(20, 40, 70, 0.55);
        }

        .btn-verify {
            background: linear-gradient(90deg, #10b981, #059669);
            border: none;
            color: #fff;
            padding: 6px 8px;
            border-radius: 8px;
            font-weight: 700;
            text-decoration: none;
            margin-right: 6px;
        }

        .btn-pay {
            background: linear-gradient(90deg, #2563eb, #06b6d4);
            border: none;
            color: #fff;
            padding: 6px 8px;
            border-radius: 8px;
            font-weight: 700;
            text-decoration: none;
            margin-right: 6px;
        }

        .btn-del {
            background: linear-gradient(90deg, #ff6b6b, #ef4444);
            border: none;
            color: #fff;
            padding: 6px 8px;
            border-radius: 8px;
            font-weight: 700;
            text-decoration: none;
        }

        .link-proof {
            color: #9fe7ff;
            text-decoration: none;
            font-weight: 600;
        }

        .muted {
            color: var(--muted);
            font-size: 13px;
        }

        @media (max-width: 992px) {
            .sidebar {
                display: none;
            }

            .content {
                padding: 14px;
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
                background: rgba(0, 0, 0, 0.12);
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
                <div class="logo">V</div>
                <div>
                    <div class="title">Kelola Villa</div>
                    <div class="sub">Admin Panel</div>
                </div>
            </div>

            <nav class="nav" role="navigation">
                <a href="index.php"><svg width="14" height="14" style="vertical-align:middle;margin-right:8px" viewBox="0 0 24 24" fill="none">
                        <path d="M3 13h8V3H3v10zM21 21h-8V11h8v10zM3 21h8v-6H3v6zM21 3v6h-8V3h8z" fill="currentColor" />
                    </svg> Dashboard</a>
                <a href="villas.php" class="active"><svg width="14" height="14" style="vertical-align:middle;margin-right:8px" viewBox="0 0 24 24" fill="none">
                        <path d="M12 3l9 6v12H3V9l9-6zM12 12l-4-3v8h8v-8l-4 3z" fill="currentColor" />
                    </svg> Kelola Villa</a>
                <a href="facilities.php"><svg width="14" height="14" style="vertical-align:middle;margin-right:8px" viewBox="0 0 24 24" fill="none">
                        <path d="M4 6h16v2H4V6zm0 5h16v2H4v-2zm0 5h10v2H4v-2z" fill="currentColor" />
                    </svg> Kelola Fasilitas</a>
                <a href="bookings.php"><svg width="14" height="14" style="vertical-align:middle;margin-right:8px" viewBox="0 0 24 24" fill="none">
                        <path d="M7 10l5 5 5-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg> Kelola Booking</a>
            </nav>

            <div class="logout">
                Logged as <strong><?php echo htmlspecialchars($_SESSION['user'] ?? 'Admin'); ?></strong><br>
                <div style="margin-top:8px"><a href="../frontend/logout.php">Logout</a></div>
            </div>
        </aside>

        <!-- CONTENT -->
        <main class="content">
            <div class="panel">
                <h3>🌙 Kelola Booking</h3>
                <div class="muted">Kelola reservasi dan verifikasi pembayaran</div>
                <?php if ($msg): ?>
                    <div style="margin-top:10px" class="small-muted"><?php echo htmlspecialchars($msg); ?></div>
                <?php endif; ?>
            </div>

            <div class="card-base">
                <div style="margin-bottom:12px">
                </div>
                <div class="table-wrap">
                    <table aria-describedby="Daftar booking">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>User</th>
                                <th>Villa</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($b = $res->fetch_assoc()):
                                $payment = $conn->query('SELECT * FROM booking_payments WHERE booking_id=' . intval($b['id']) . ' ORDER BY id DESC LIMIT 1')->fetch_assoc();
                            ?>
                                <tr>
                                    <td><?= intval($b['id']) ?></td>
                                    <td><?= esc($b['user_name']) ?></td>
                                    <td><?= esc($b['villa_name']) ?></td>
                                    <td><?= esc($b['checkin_date']) ?></td>
                                    <td><?= esc($b['checkout_date']) ?></td>
                                    <td>Rp <?= number_format($b['total_price'], 0, ',', '.') ?></td>

                                    <td>
                                        <div style="font-weight:700"><?= esc($b['status']) ?></div>
                                        <div class="muted" style="font-size:12px"><?= esc($b['payment_status']) ?></div>
                                    </td>

                                    <td>
                                        <?php if ($payment): ?>
                                            <?php if (!empty($payment['proof_image']) && file_exists(__DIR__ . '/../uploads/' . $payment['proof_image'])): ?>
                                                <a class="link-proof" target="_blank" href="../uploads/<?= esc($payment['proof_image']) ?>">Lihat Bukti</a><br>
                                            <?php endif; ?>
                                            <div class="muted">Rp <?= number_format($payment['amount'], 0, ',', '.') ?></div>
                                        <?php else: ?>
                                            <div class="muted">-</div>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if ($b['status'] !== 'verified'): ?>
                                            <a href="?verify=<?= intval($b['id']) ?>" class="btn-verify" onclick="return confirm('Verifikasi booking #<?= intval($b['id']) ?> ?')">Verify</a>
                                        <?php endif; ?>

                                        <?php if ($payment && $b['payment_status'] !== 'paid'): ?>
                                            <a href="?verify_payment=<?= intval($payment['id']) ?>" class="btn-pay" onclick="return confirm('Tandai pembayaran sebagai sudah dibayar?')">Verify Payment</a>
                                        <?php endif; ?>

                                        <!-- tombol hapus -->
                                        <a href="?delete=<?= intval($b['id']) ?>" class="btn-del" onclick="return confirm('Yakin ingin menghapus booking #<?= intval($b['id']) ?>? Semua data pembayaran yang terkait juga akan dihapus.')">Hapus</a>
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