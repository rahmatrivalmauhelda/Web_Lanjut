<?php
require_once __DIR__ . '/../config/functions.php';
require_admin();

$msg = '';

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($id > 0) {
        $g = $conn->query("SELECT image FROM villas WHERE id=$id")->fetch_assoc();
        if ($g && $g['image'] && file_exists(__DIR__ . '/../uploads/' . $g['image'])) {
            @unlink(__DIR__ . '/../uploads/' . $g['image']);
        }

        $conn->query("DELETE FROM villa_facilities WHERE villa_id=$id");

        $conn->query("DELETE FROM villas WHERE id=$id");

        $msg = "Villa berhasil dihapus!";
    }
}

if (isset($_POST['edit_villa'])) {
    $id = intval($_POST['villa_id']);
    $name = $_POST['name'];
    $price = intval($_POST['price']);
    $location = $_POST['location'];
    $desc = $_POST['description'];

    $old = $conn->query("SELECT image FROM villas WHERE id=$id")->fetch_assoc();
    $imgname = $old['image'];

    if (!empty($_FILES['image']['name'])) {
        $tmp = $_FILES['image']['tmp_name'];
        $newimg = time() . "_" . basename($_FILES['image']['name']);
        move_uploaded_file($tmp, __DIR__ . '/../uploads/' . $newimg);

        if ($imgname && file_exists(__DIR__ . '/../uploads/' . $imgname)) {
            @unlink(__DIR__ . '/../uploads/' . $imgname);
        }

        $imgname = $newimg;
    }

    $st = $conn->prepare("UPDATE villas SET name=?, price=?, location=?, description=?, image=? WHERE id=?");
    $st->bind_param("sisssi", $name, $price, $location, $desc, $imgname, $id);
    $st->execute();

    // update fasilitas: hapus dulu lalu insert ulang
    $conn->query("DELETE FROM villa_facilities WHERE villa_id=$id");

    if (!empty($_POST['facilities']) && is_array($_POST['facilities'])) {
        foreach ($_POST['facilities'] as $fid) {
            $fid = intval($fid);
            if ($fid > 0) {
                $conn->query("INSERT INTO villa_facilities (villa_id, facility_id) VALUES ($id, $fid)");
            }
        }
    }

    $msg = "Villa berhasil diupdate!";
}


if (isset($_POST['add_villa'])) {
    $name = $_POST['name'];
    $price = intval($_POST['price']);
    $location = $_POST['location'];
    $desc = $_POST['description'];

    $imgname = null;
    if (!empty($_FILES['image']['name'])) {
        $tmp = $_FILES['image']['tmp_name'];
        $imgname = time() . "_" . basename($_FILES['image']['name']);
        move_uploaded_file($tmp, __DIR__ . '/../uploads/' . $imgname);
    }

    $st = $conn->prepare("INSERT INTO villas (name,price,location,description,image) VALUES (?,?,?,?,?)");
    $st->bind_param("sisss", $name, $price, $location, $desc, $imgname);
    $st->execute();

    $villa_id = $conn->insert_id;

    // simpan fasilitas
    if (!empty($_POST['facilities']) && is_array($_POST['facilities'])) {
        foreach ($_POST['facilities'] as $fid) {
            $fid = intval($fid);
            if ($fid > 0) {
                $conn->query("INSERT INTO villa_facilities (villa_id, facility_id) VALUES ($villa_id, $fid)");
            }
        }
    }

    $msg = "Villa berhasil ditambahkan!";
}
$res = $conn->query("SELECT * FROM villas ORDER BY id DESC");
$all_facilities = $conn->query("SELECT * FROM facilities ORDER BY name ASC");
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Kelola Villa — Tampilan Card (Diperbarui)</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --bg-1: #001628;
            --bg-2: #003a57;
            --panel: rgba(255, 255, 255, 0.04);
            --muted: rgba(235, 245, 255, 0.75);
            --accent: #0ea5e9;
            --danger: #ef4444;
            --glass: rgba(255, 255, 255, 0.03);
        }

        html,
        body {
            height: 100%;
            margin: 0;
            font-family: 'Segoe UI', Roboto, Arial, sans-serif;
            background: linear-gradient(145deg, var(--bg-1), var(--bg-2));
            color: #e8f6ff;
        }

        .app {
            display: flex;
            min-height: 100vh;
        }

        /* SIDEBAR sederhana */
        .sidebar {
            width: 220px;
            background: linear-gradient(180deg, #122b3a, #0f2030);
            padding: 20px;
            box-shadow: 0 6px 30px rgba(2, 6, 23, 0.6);
        }

        .brand {
            display: flex;
            gap: 12px;
            align-items: center;
            margin-bottom: 18px;
        }

        .logo {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            background: linear-gradient(135deg, #8be3ff, #2563eb);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #05202b;
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
        }

        .nav a.active {
            background: rgba(255, 255, 255, 0.04);
            color: #fff;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.02);
        }

        .nav a:hover {
            transform: translateX(6px);
            transition: .12s ease;
            color: white;
        }

        .content {
            flex: 1;
            padding: 26px;
        }

        .panel {
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.03), rgba(255, 255, 255, 0.02));
            border-radius: 14px;
            padding: 22px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.45);
            border: 1px solid rgba(255, 255, 255, 0.04);
            margin-bottom: 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }

        .panel h2 {
            margin: 0;
            font-size: 20px;
            color: #eaf6ff;
        }

        .panel .muted {
            color: rgba(235, 245, 255, 0.6);
            font-size: 13px;
        }

        .search-wrap .form-control {
            background: rgba(0, 0, 0, 0.28);
            border: 1px solid rgba(255, 255, 255, 0.04);
            color: #eaf6ff;
            padding: 12px 14px;
            border-radius: 10px;
            box-shadow: 0 6px 16px rgba(3, 10, 20, 0.45) inset;
        }

        .panel .btn-primary {
            background: linear-gradient(90deg, #1283d7, #06b6d4);
            border: none;
            box-shadow: 0 8px 22px rgba(6, 28, 56, 0.45);
            padding: 8px 14px;
            border-radius: 8px;
            font-weight: 700;
        }

        .btn-logout {
            display: inline-block;
            padding: 8px 12px;
            background: rgba(255, 255, 255, 0.06);
            color: #fff;
            border-radius: 8px;
            font-weight: 700;
            text-decoration: none;
            border: 1px solid rgba(255, 255, 255, 0.03);
            margin-left: 8px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 18px;
        }

        .villa-card {
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.02), rgba(255, 255, 255, 0.01));
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.03);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .villa-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 22px 60px rgba(0, 0, 0, 0.6);
        }

        .villa-media {
            position: relative;
            height: 150px;
            overflow: hidden;
        }

        .villa-media img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform .45s ease;
        }

        .villa-card:hover .villa-media img {
            transform: scale(1.06);
        }

        .price-badge {
            position: absolute;
            right: 12px;
            top: 12px;
            background: rgba(7, 18, 33, 0.8);
            padding: 7px 10px;
            border-radius: 999px;
            font-weight: 800;
            color: #fff;
            font-size: 13px;
            box-shadow: 0 6px 18px rgba(2, 6, 23, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.04);
        }

        .villa-body {
            padding: 14px;
        }

        .villa-title {
            font-weight: 800;
            margin: 0 0 6px 0;
            color: #eaf6ff;
        }

        .villa-loc {
            color: rgba(235, 245, 255, 0.6);
            font-size: 13px;
            margin-bottom: 8px;
        }

        .villa-desc {
            font-size: 13px;
            color: rgba(235, 245, 255, 0.7);
            min-height: 36px;
            margin-bottom: 10px;
            overflow: hidden;
        }

        .villa-actions {
            display: flex;
            gap: 8px;
        }

        .btn-edit {
            background: linear-gradient(90deg, #14aaf4, #0ea5e9);
            border: none;
            color: #04202b;
            padding: 8px 10px;
            border-radius: 8px;
            font-weight: 700;
            flex: 1;
        }

        .btn-delete {
            background: linear-gradient(90deg, #ff6b6b, #ef4444);
            border: none;
            color: white;
            padding: 8px 10px;
            border-radius: 8px;
            font-weight: 700;
            flex: 1;
        }

        .meta-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }

        .small-muted {
            font-size: 12px;
            color: rgba(235, 245, 255, 0.6);
        }

        .add-card {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px dashed rgba(255, 255, 255, 0.04);
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.01), rgba(255, 255, 255, 0));
            border-radius: 12px;
            color: var(--muted);
            cursor: pointer;
            transition: background .12s ease, transform .12s ease;
        }

        .add-card:hover {
            background: rgba(255, 255, 255, 0.02);
            transform: translateY(-6px);
        }

        .modal-content {
            background: rgba(0, 22, 50, 0.85) !important;
            color: #004479ff !important;
        }

        .modal .form-label {
            color: #044b81ff !important;
        }

        .modal .form-control,
        .modal textarea {
            background: rgba(255, 255, 255, 0.08) !important;
            color: #e9e9e9ff !important;
            border: 1px solid rgba(221, 220, 220, 0.15) !important;
        }

        .modal .form-control::placeholder,
        .modal textarea::placeholder {
            color: rgba(230, 240, 255, 0.55) !important;
        }

        .modal .form-check-label {
            color: #014477ff !important;
        }

        .modal .form-check-input {
            border-color: rgba(2, 0, 136, 0.6) !important;
        }

        .modal .fac-box {
            background: rgba(255, 255, 255, 0.04) !important;
            border: 1px solid rgba(255, 255, 255, 0.06);
            padding: 10px;
            border-radius: 8px;
        }

        .fac-box {
            background: rgba(255, 255, 255, 0.06) !important;
            border: 1px solid rgba(255, 255, 255, 0.10) !important;
            padding: 12px;
            border-radius: 10px;
            color: #013c69ff !important;
        }

        /* Checkbox label warna terang */
        .fac-box .form-check-label {
            color: #024070ff !important;
        }

        /* Checkbox border warna putih */
        .fac-box .form-check-input {
            border-color: rgba(4, 30, 116, 0.7) !important;
            background: transparent !important;
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
                    <div style="font-weight:800">Kelola Villa</div>
                    <div style="font-size:12px;color:rgba(255,255,255,0.6)">Admin Panel</div>
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

            <div style="position:absolute;bottom:18px;left:20px;right:20px;font-size:13px;color:rgba(255,255,255,0.7)">
                Logged as <strong><?php echo htmlspecialchars($_SESSION['user'] ?? 'Admin'); ?></strong>
                <div style="margin-top:8px">
                    <a href="../frontend/logout.php" class="btn-logout" role="button">Logout</a>
                </div>
            </div>
        </aside>

        <!-- CONTENT -->
        <main class="content">
            <div class="panel">
                <div>
                    <h2>🌊 Kelola Villa — Tampilan Card</h2>
                    <div class="muted">Tampilan grid card yang lebih rapi — tambahkan, edit, atau hapus villa dengan mudah.</div>
                </div>

                <div style="display:flex;align-items:center;gap:12px">
                    <div class="search-wrap">
                        <input id="searchInput" class="form-control" placeholder="Cari nama / lokasi..." />
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">Tambah Villa</button>
                    <a href="../frontend/logout.php" class="btn-logout" role="button">Logout</a>
                </div>
            </div>

            <?php if ($msg): ?>
                <div class="alert alert-success"><?= esc($msg) ?></div>
            <?php endif; ?>

            <div class="grid" id="cardGrid">
                <!-- add new card -->
                <div>
                    <div class="add-card" data-bs-toggle="modal" data-bs-target="#addModal">
                        <div style="text-align:center">
                            <div style="font-weight:800;font-size:20px;margin-bottom:6px">+ Tambah Villa</div>
                            <div class="small-muted">Klik untuk menambahkan villa baru</div>
                        </div>
                    </div>
                </div>

                <?php while ($v = $res->fetch_assoc()): ?>
                    <div data-name="<?= strtolower(esc($v['name'])) ?>" data-location="<?= strtolower(esc($v['location'])) ?>">
                        <div class="villa-card">
                            <div class="villa-media">
                                <?php
                                $imgPath = $v['image'] && file_exists(__DIR__ . '/../uploads/' . $v['image']) ? ('../uploads/' . $v['image']) : $placeholder;
                                ?>
                                <img src="<?= esc($imgPath) ?>" alt="<?= esc($v['name']) ?>">
                                <div class="price-badge">Rp <?= number_format($v['price'], 0, ',', '.') ?></div>
                            </div>
                            <div class="villa-body">
                                <h5 class="villa-title"><?= esc($v['name']) ?></h5>
                                <div class="villa-loc"><?= esc($v['location']) ?></div>
                                <div class="villa-desc"><?= esc($v['description']) ?></div>

                                <?php
                                // tampilkan fasilitas singkat
                                $fz = $conn->query("
                                    SELECT f.name FROM villa_facilities vf
                                    JOIN facilities f ON f.id=vf.facility_id
                                    WHERE vf.villa_id=" . intval($v['id']) . " LIMIT 6
                                ");
                                $list = [];
                                while ($r = $fz->fetch_assoc()) {
                                    $list[] = $r['name'];
                                }
                                ?>
                                <?php if ($list): ?>
                                    <div class="fac-badges">
                                        <?php foreach ($list as $fn): ?>
                                            <div class="f"><?= esc($fn) ?></div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="meta-row">
                                    <div class="small-muted">ID #<?= $v['id'] ?></div>
                                    <div class="villa-actions" style="width:55%">
                                        <button class="btn-edit" data-bs-toggle="modal" data-bs-target="#editModal<?= $v['id'] ?>">Edit</button>
                                        <a onclick="return confirm('Yakin ingin menghapus villa ini?')" href="?delete=<?= $v['id'] ?>" class="btn-delete">Hapus</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php
                    ob_start();
                    ?>
                    <div class="modal fade" id="editModal<?= $v['id'] ?>">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <form method="post" enctype="multipart/form-data">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Villa — <?= esc($v['name']) ?></h5>
                                        <button class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>

                                    <div class="modal-body">
                                        <input type="hidden" name="villa_id" value="<?= $v['id'] ?>" />
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <label class="form-label">Nama</label>
                                                <input name="name" class="form-control" value="<?= esc($v['name']) ?>" required>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Harga</label>
                                                <input name="price" type="number" class="form-control" value="<?= $v['price'] ?>" required>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Lokasi</label>
                                                <input name="location" class="form-control" value="<?= esc($v['location']) ?>" required>
                                            </div>

                                            <div class="col-12 mt-2">
                                                <label class="form-label">Gambar (opsional)</label>
                                                <input name="image" type="file" class="form-control">
                                                <?php if ($v['image']): ?>
                                                    <small class="text-muted">Gambar saat ini: <?= esc($v['image']) ?></small>
                                                <?php endif; ?>
                                            </div>

                                            <div class="col-12 mt-2">
                                                <label class="form-label">No Rekening</label>
                                                <textarea name="description" class="form-control"><?= esc($v['description']) ?></textarea>
                                            </div>

                                            <!-- FASILITAS (EDIT) -->
                                            <?php
                                            $sel = [];
                                            $fs2 = $conn->query("SELECT facility_id FROM villa_facilities WHERE villa_id=" . intval($v['id']));
                                            while ($r2 = $fs2->fetch_assoc()) {
                                                $sel[] = $r2['facility_id'];
                                            }
                                            $fsAll = $conn->query("SELECT * FROM facilities ORDER BY name ASC");
                                            ?>
                                            <div class="col-12 mt-3">
                                                <label class="form-label">Fasilitas</label>
                                                <div style="padding:10px;background:#f5f5f5;border-radius:8px;max-height:220px;overflow-y:auto">
                                                    <?php while ($frow = $fsAll->fetch_assoc()): ?>
                                                        <?php $checked = in_array($frow['id'], $sel) ? 'checked' : '' ?>
                                                        <div class="form-check">
                                                            <input class="form-check-input" type="checkbox" <?= $checked ?> name="facilities[]" value="<?= $frow['id'] ?>">
                                                            <label class="form-check-label"><?= esc($frow['name']) ?></label>
                                                        </div>
                                                    <?php endwhile; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal-footer">
                                        <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                        <button name="edit_villa" class="btn btn-primary">Update</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php
                    $modals[] = ob_get_clean();
                    ?>
                <?php endwhile; ?>
            </div>

            <!-- tampilkan semua modals edit yang sudah dikumpulkan -->
            <?php if (!empty($modals)): foreach ($modals as $m) {
                    echo $m;
                }
            endif; ?>

        </main>
    </div>

    <!-- MODAL ADD -->
    <div class="modal fade" id="addModal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="post" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Villa</h5>
                        <button class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-2">
                            <div class="col-md-6"><label class="form-label">Nama</label><input name="name" class="form-control" placeholder="Nama Villa" required></div>
                            <div class="col-md-3"><label class="form-label">Harga</label><input name="price" type="number" class="form-control" placeholder="Harga" required></div>
                            <div class="col-md-3"><label class="form-label">Lokasi</label><input name="location" class="form-control" placeholder="Lokasi" required></div>
                            <div class="col-12 mt-2"><label class="form-label">Gambar</label><input name="image" type="file" class="form-control"></div>
                            <div class="col-12 mt-2"><label class="form-label">No Rekening</label><textarea name="description" class="form-control" placeholder="No Rekening"></textarea></div>

                            <!-- FASILITAS (ADD) -->
                            <div class="col-12 mt-3">
                                <label class="form-label">Fasilitas</label>
                                <div style="padding:10px;background:#f5f5f5;border-radius:8px;max-height:220px;overflow-y:auto">
                                    <?php
                                    // ulang query facilities untuk modal add (fresh)
                                    $fsAdd = $conn->query("SELECT * FROM facilities ORDER BY name ASC");
                                    while ($f = $fsAdd->fetch_assoc()):
                                    ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="facilities[]" value="<?= $f['id'] ?>">
                                            <label class="form-check-label"><?= esc($f['name']) ?></label>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button name="add_villa" class="btn btn-primary">Tambah</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // search filter sederhana
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const q = e.target.value.trim().toLowerCase();
            document.querySelectorAll('#cardGrid > div').forEach(function(col, idx) {
                if (idx === 0) return; // skip tombol tambah
                const name = col.getAttribute('data-name') || '';
                const loc = col.getAttribute('data-location') || '';
                const show = q === '' || name.includes(q) || loc.includes(q);
                col.style.display = show ? '' : 'none';
            });
        });
    </script>
</body>

</html>