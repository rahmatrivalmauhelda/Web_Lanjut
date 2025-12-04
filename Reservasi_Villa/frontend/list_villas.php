<?php
require_once __DIR__ . '/../config/functions.php';

$q = $_GET['q'] ?? '';
$sort = $_GET['sort'] ?? '';
$sql = "SELECT * FROM villas WHERE status='available'";
$params = [];

if ($q) {
    $sql .= " AND (name LIKE ? OR location LIKE ? OR description LIKE ?)";
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($sort === 'price_asc') {
    $sql .= ' ORDER BY price ASC';
} elseif ($sort === 'price_desc') {
    $sql .= ' ORDER BY price DESC';
} else {
    $sql .= ' ORDER BY id DESC';
}

$stmt = $conn->prepare($sql);
if ($params) {
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();

// placeholder preview image (local file used in project)
$placeholder = 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTPMtEQjTun0NYGpcUwaovg63H5GQrR-60W3A&s';
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Daftar Villa - Alahan Panjang</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        /* Night Lake layout with sidebar */
        :root {
            --bg1: #001628;
            --bg2: #003a57;
            --panel: rgba(255, 255, 255, 0.03);
            --muted: rgba(235, 245, 255, 0.7);
            --accent: #1283d7;
            --accent-2: #06b6d4;
        }

        html,
        body {
            height: 100%;
            margin: 0;
            font-family: 'Segoe UI', 'Inter', system-ui, -apple-system, Roboto, "Helvetica Neue", Arial;
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
            font-size: 14px;
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
            background: linear-gradient(90deg, rgba(255, 255, 255, 0.04), rgba(255, 255, 255, 0.02));
            box-shadow: 0 8px 22px rgba(0, 0, 0, 0.45);
            color: #fff;
        }

        .sidebar .user {
            position: absolute;
            bottom: 22px;
            left: 16px;
            right: 16px;
            color: rgba(255, 255, 255, 0.7);
            font-size: 13px;
        }

        .sidebar .user a {
            display: inline-block;
            margin-top: 8px;
            padding: 8px 12px;
            background: rgba(255, 255, 255, 0.06);
            border-radius: 8px;
            color: #fff;
            text-decoration: none;
            font-weight: 600;
        }

        /* CONTENT */
        .content {
            flex: 1;
            padding: 28px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
        }

        .topbar .title {
            font-size: 20px;
            font-weight: 800;
            color: #eaf6ff;
        }

        .topbar .sub {
            color: var(--muted);
        }

        .search-panel {
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.02), rgba(255, 255, 255, 0.01));
            border: 1px solid rgba(255, 255, 255, 0.04);
            padding: 14px;
            border-radius: 12px;
            box-shadow: 0 8px 28px rgba(0, 0, 0, 0.6);
            margin-bottom: 20px;
        }

        .form-control,
        .form-select {
            background: rgba(0, 0, 0, 0.30);
            border: 1px solid rgba(255, 255, 255, 0.04);
            color: #eaf6ff;
            border-radius: 10px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 18px;
        }

        .villa-card {
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.02), rgba(255, 255, 255, 0.01));
            border: 1px solid rgba(255, 255, 255, 0.03);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.6);
            transition: transform .18s ease, box-shadow .18s ease;
            display: flex;
            flex-direction: column;
            min-height: 360px;
        }

        .villa-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 22px 60px rgba(0, 0, 0, 0.7);
        }

        .villa-media {
            height: 180px;
            overflow: hidden;
            position: relative;
        }

        .villa-media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform .45s ease;
        }

        .villa-card:hover .villa-media img {
            transform: scale(1.04);
        }

        .price-badge {
            position: absolute;
            right: 12px;
            top: 12px;
            background: rgba(7, 18, 33, 0.8);
            color: #fff;
            font-weight: 800;
            padding: 7px 10px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.04);
        }

        .card-body {
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex: 1;
        }

        .villa-title {
            font-weight: 800;
            font-size: 18px;
            color: #eaf6ff;
            margin: 0;
        }

        .villa-loc {
            color: var(--muted);
            font-size: 13px;
            margin-bottom: 6px;
        }

        .villa-desc {
            color: rgba(235, 245, 255, 0.75);
            font-size: 13px;
            min-height: 56px;
            overflow: hidden;
        }

        .card-footer {
            padding: 14px;
            display: flex;
            gap: 10px;
            align-items: center;
            justify-content: space-between;
        }

        .villa-price {
            font-weight: 800;
            color: #eaf6ff;
            font-size: 16px;
        }

        .btn-primary {
            background: linear-gradient(90deg, var(--accent), var(--accent-2));
            border: none;
            color: #04202b;
            font-weight: 800;
            padding: 10px 14px;
            border-radius: 10px;
        }

        .btn-outline {
            background: transparent;
            color: #eaf6ff;
            border: 1px solid rgba(255, 255, 255, 0.06);
            padding: 10px 12px;
            border-radius: 10px;
            font-weight: 700;
        }

        .empty {
            background: rgba(255, 255, 255, 0.02);
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            color: var(--muted);
        }

        /* responsive */
        @media (max-width: 992px) {
            .sidebar {
                display: none;
            }

            .content {
                padding: 18px;
            }

            .grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
        }
    </style>
</head>

<body>
    <div class="app">
        <!-- SIDEBAR -->
        <aside class="sidebar" aria-label="Main sidebar">
            <div class="brand">
                <div class="logo">C</div>
                <div>
                    <div class="title">Villa Alahan</div>
                    <div class="sub">Explore & Book</div>
                </div>
            </div>

            <nav class="nav" role="navigation">
                <!-- Lihat Villa (Dashboard) -->
                <a href="list_villas.php" class="active">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 3l9 6v12H3V9l9-6z" />
                    </svg>
                    Lihat Villa
                </a>

                <!-- Reservasi (below) -->
                <a href="dashboard.php">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M7 10l5 5 5-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    Reservasi Saya
                </a>
            </nav>

            <div class="user">
                <?php if (is_logged_in()): ?>
                    Logged in as <strong><?php echo htmlspecialchars($_SESSION['user'] ?? 'User'); ?></strong><br>
                    <!-- Logout at the bottom -->
                    <a href="logout.php" style="display:inline-block;margin-top:8px;padding:8px 12px;background:rgba(255,255,255,0.06);border-radius:8px;color:#fff;text-decoration:none;font-weight:700;">Logout</a>
                <?php else: ?>
                    <a href="login.php" style="display:inline-block;padding:8px 12px;background:rgba(255,255,255,0.06);border-radius:8px;color:#fff;text-decoration:none;font-weight:700;">Login</a>
                <?php endif; ?>
            </div>
        </aside>

        <!-- CONTENT -->
        <main class="content">
            <div class="topbar">
                <div>
                    <div class="title">Daftar Villa</div>
                    <div class="sub">Temukan villa terbaik untuk liburanmu</div>
                </div>
            </div>

            <form class="search-panel" method="get" action="">
                <div class="row g-2 align-items-center">
                    <div class="col-md-6">
                        <input name="q" value="<?php echo esc($q); ?>" class="form-control" placeholder="Cari villa, lokasi, atau fasilitas...">
                    </div>

                    <div class="col-md-3">
                        <select name="sort" class="form-select">
                            <option value="">Urutkan</option>
                            <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Harga termurah</option>
                            <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Harga termahal</option>
                        </select>
                    </div>

                    <div class="col-md-3 d-flex gap-2">
                        <button class="btn btn-primary w-100" type="submit">🔍 Cari</button>
                        <a href="list_villas.php" class="btn btn-outline">Reset</a>
                    </div>
                </div>
            </form>

            <?php if ($res->num_rows === 0): ?>
                <div class="empty mt-4">Maaf, tidak ada villa yang sesuai.</div>
            <?php else: ?>
                <div class="grid mt-4">
                    <?php while ($villa = $res->fetch_assoc()):
                        $img = ($villa['image'] && file_exists(__DIR__ . '/../uploads/' . $villa['image'])) ? ('../uploads/' . $villa['image']) : $placeholder;
                    ?>
                        <div class="villa-card">
                            <div class="villa-media">
                                <img src="<?php echo esc($img); ?>" alt="<?php echo esc($villa['name']); ?>">
                                <div class="price-badge">Rp <?php echo number_format($villa['price'], 0, ',', '.'); ?></div>
                            </div>

                            <div class="card-body">
                                <h5 class="villa-title"><?php echo esc($villa['name']); ?></h5>
                                <div class="villa-loc"><?php echo esc($villa['location']); ?></div>
                                <p class="villa-desc"><?php echo esc(mb_strimwidth($villa['description'], 0, 160, '...')); ?></p>
                            </div>

                            <div class="card-footer">
                                <div class="villa-price">Rp <?php echo number_format($villa['price'], 0, ',', '.'); ?>/malam</div>
                                <div style="min-width:140px;display:flex;gap:8px;">
                                    <a href="villa.php?id=<?php echo intval($villa['id']); ?>" class="btn btn-primary">Lihat Detail</a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>

</html>