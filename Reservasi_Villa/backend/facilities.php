<?php
require_once __DIR__ . '/../config/functions.php';
require_admin();

$msg = '';

// HAPUS FASILITAS
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($id > 0) {
        $st = $conn->prepare("DELETE FROM facilities WHERE id = ?");
        $st->bind_param("i", $id);
        if ($st->execute()) {
            $msg = 'Fasilitas berhasil dihapus.';
        } else {
            $msg = 'Gagal menghapus fasilitas: ' . $conn->error;
        }
        // redirect supaya URL bersih dan mencegah penghapusan berulang saat refresh
        header('Location: facilities.php?msg=' . urlencode($msg));
        exit;
    }
}

// pesan dari redirect
if (isset($_GET['msg']) && $_GET['msg'] !== '') {
    $msg = $_GET['msg'];
}

// TAMBAH FASILITAS
if (isset($_POST['add_f'])) {
    $name = trim($_POST['name']);
    if ($name !== '') {
        $st = $conn->prepare('INSERT INTO facilities (name) VALUES (?)');
        $st->bind_param('s', $name);
        if ($st->execute()) {
            $msg = 'Fasilitas ditambahkan';
        } else {
            $msg = 'Error: ' . $conn->error;
        }
        // redirect supaya form tidak double submit
        header('Location: facilities.php?msg=' . urlencode($msg));
        exit;
    } else {
        $msg = 'Nama fasilitas tidak boleh kosong';
    }
}

$fac = $conn->query('SELECT * FROM facilities ORDER BY id DESC');
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Kelola Fasilitas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --bg-1: #001628;
            --bg-2: #003a57;
            --panel: rgba(255, 255, 255, 0.04);
            --muted: rgba(235, 245, 255, 0.75);
            --accent: #0ea5e9;
            --danger: #ef4444;
        }

        html, body {
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

        /* SIDEBAR (match villas.php) */
        .sidebar {
            width: 220px;
            background: linear-gradient(180deg,#122b3a,#0f2030);
            padding: 20px;
            box-shadow: 0 6px 30px rgba(2,6,23,0.6);
            border-right: 1px solid rgba(255,255,255,0.06);
            position: relative;
        }

        .brand {
            display:flex;
            gap:12px;
            align-items:center;
            margin-bottom:18px;
        }

        .brand .logo {
            width:44px;
            height:44px;
            border-radius:10px;
            overflow:hidden;
            display:flex;
            align-items:center;
            justify-content:center;
            background: linear-gradient(135deg,#8be3ff,#2563eb);
            box-shadow:0 8px 18px rgba(3,10,20,0.6);
        }
        .brand .logo img { width:100%; height:100%; object-fit:cover; display:block; }

        .brand .title { font-weight:800; color:#fff; }
        .brand .sub { font-size:12px; color:rgba(255,255,255,0.6); }

        .nav { margin-top:6px; }
        .nav a {
            display:block;
            color: var(--muted);
            padding:8px 10px;
            border-radius:8px;
            text-decoration:none;
            margin-bottom:6px;
            font-weight:600;
            transition:.12s ease;
        }
        .nav a:hover { transform: translateX(6px); color:#fff; background: rgba(255,255,255,0.04); }
        .nav a.active { background: rgba(255,255,255,0.04); color:#fff; box-shadow: inset 0 0 0 1px rgba(255,255,255,0.02); }

        .sidebar .logout {
            position:absolute;
            bottom:18px;
            left:20px;
            right:20px;
            font-size:13px;
            color:rgba(255,255,255,0.75);
        }
        .sidebar .logout a {
            display:inline-block;
            margin-top:8px;
            padding:8px 12px;
            background:rgba(255,255,255,0.06);
            border-radius:8px;
            color:#fff;
            text-decoration:none;
            font-weight:700;
        }

        /* MAIN CONTENT */
        .content {
            flex:1;
            padding:26px;
        }

        .card-base {
            background: linear-gradient(180deg, rgba(255,255,255,0.03), rgba(255,255,255,0.02));
            padding: 22px;
            border-radius: 14px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.45);
            border:1px solid rgba(255,255,255,0.04);
            margin-bottom:18px;
        }

        h3 { margin:0 0 8px 0; color:#eaf6ff; }
        a.back { color:#97c1ff; font-weight:600; text-decoration:none; }

        input.form-control {
            background: rgba(0,0,0,0.28);
            border: 1px solid rgba(255,255,255,0.04);
            color: #eaf6ff;
            padding:10px 12px;
            border-radius:10px;
        }

        .btn-primary {
            background: linear-gradient(90deg,#1283d7,#06b6d4);
            border:none;
            padding:10px 14px;
            border-radius:10px;
            font-weight:700;
        }
        .btn-primary:hover { box-shadow:0 8px 22px rgba(6,28,56,0.45); }

        .fac-list {
            background: rgba(0, 25, 60, 0.45);
            padding: 14px;
            border-radius: 12px;
            border:1px solid rgba(255,255,255,0.08);
        }
        .fac-list li {
            padding:10px 8px;
            border-bottom:1px solid rgba(255,255,255,0.03);
            list-style:none;
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:12px;
        }
        .fac-list li:last-child { border-bottom:none; }

        .small-muted { color: rgba(235,245,255,0.65); font-size:13px; }

        /* delete button */
        .btn-del {
            background: linear-gradient(90deg,#ff6b6b,#ef4444);
            border: none;
            color: #fff;
            padding: 6px 10px;
            border-radius: 8px;
            font-weight:700;
            cursor:pointer;
        }
        .btn-del:hover { opacity:0.95; }

        @media (max-width: 992px) {
            .sidebar { display:none; }
            .content { padding:18px; }
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
                <a href="index.php">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zM21 21h-8V11h8v10zM3 21h8v-6H3v6zM21 3v6h-8V3h8z"/></svg>
                    Dashboard
                </a>
                <a href="villas.php">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 3l9 6v12H3V9l9-6z"/></svg>
                    Kelola Villa
                </a>
                <a href="facilities.php" class="active">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M4 6h16v2H4V6zm0 5h16v2H4v-2zm0 5h10v2H4v-2z"/></svg>
                    Kelola Fasilitas
                </a>
                <a href="bookings.php">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M7 10l5 5 5-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Kelola Booking
                </a>
            </nav>

            <div class="logout">
                <div>Logged as <strong><?php echo htmlspecialchars($_SESSION['user'] ?? 'Admin'); ?></strong></div>
                <a href="../frontend/logout.php">Logout</a>
            </div>
        </aside>

        <!-- CONTENT -->
        <main class="content">
            <div class="card-base">
                <h3>🌙 Kelola Fasilitas</h3>
                <a href="index.php" class="back">&larr; Kembali ke Dashboard</a>

                <?php if ($msg): ?>
                    <div class="alert alert-info mt-3"><?= esc($msg) ?></div>
                <?php endif; ?>

                <form method="post" class="row g-2 mt-3">
                    <div class="col-md-8">
                        <input name="name" class="form-control" placeholder="Nama fasilitas" required>
                    </div>
                    <div class="col-md-4">
                        <button name="add_f" class="btn btn-primary w-100">Tambah</button>
                    </div>
                </form>
            </div>

            <div class="card-base">
                <h5 class="mb-3">Daftar Fasilitas</h5>

                <ul class="fac-list">
                    <?php while ($r = $fac->fetch_assoc()): ?>
                        <li>
                            <span><?= esc($r['name']); ?></span>
                            <span>
                                <!-- tombol hapus: gunakan confirm JS -->
                                <a href="facilities.php?delete=<?= (int)$r['id']; ?>" onclick="return confirm('Hapus fasilitas <?= addslashes($r['name']) ?> ?')" class="btn-del">Hapus</a>
                            </span>
                        </li>
                    <?php endwhile; ?>
                </ul>
            </div>
        </main>
    </div>
</body>

</html>
