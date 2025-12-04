<?php
require_once __DIR__ . '/../config/functions.php';
require_admin();

// aman dari error session kosong
$loggedUser = $_SESSION['user'] ?? 'Admin';

$msg = "";

// =================================
//   PROSES TAMBAH METODE BAYAR
// =================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name           = $_POST['name'] ?? '';
    $account_number = $_POST['account_number'] ?? '';
    $account_name   = $_POST['account_name'] ?? '';
    $details        = $_POST['details'] ?? '';
    $is_active      = $_POST['is_active'] ?? '1';

    // security escape
    $name = mysqli_real_escape_string($conn, $name);
    $account_number = mysqli_real_escape_string($conn, $account_number);
    $account_name = mysqli_real_escape_string($conn, $account_name);
    $details = mysqli_real_escape_string($conn, $details);
    $is_active = mysqli_real_escape_string($conn, $is_active);

    $sql = "INSERT INTO payment_methods 
           (name, account_number, account_name, details, is_active)
           VALUES 
           ('$name', '$account_number', '$account_name', '$details', '$is_active')";
}
$logoPath = 'https://media.istockphoto.com/id/954805524/id/vektor/ikon-roda-gigi-vektor-pengguna-pria-profil-simbol-avatar-pada-roda-cog-untuk-pengaturan-dan.jpg?s=612x612&w=0&k=20&c=n7J-leNyfMCA6r3X7ZQ9eAEOCCk7trQ2nbC_Uy_hO-8=';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Tambah Metode Pembayaran</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        /* ============================================================
   COLOR THEME
============================================================ */
        :root {
            --bg-1: #001628;
            --bg-2: #003a57;
            --panel: rgba(255, 255, 255, 0.04);
            --muted: rgba(235, 245, 255, 0.75);
        }

        /* ============================================================
   GLOBAL
============================================================ */
        body {
            background: linear-gradient(145deg, var(--bg-1), var(--bg-2));
            font-family: 'Segoe UI', Arial, sans-serif;
            color: #e8f6ff;
            margin: 0;
        }
        .app {
            display: flex;
            min-height: 100vh;
        }

        /* ============================================================
   SIDEBAR
============================================================ */
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

        .content {
            flex: 1;
            padding: 28px;
        }

        .topbar h3 {
            font-weight: 800;
            margin: 0;
            color: #ffffff;
        }

        .topbar .sub {
            color: var(--muted);
        }

        .metrics {
            display: flex;
            gap: 18px;
            margin-bottom: 22px;
        }

        .metric {
            flex: 1;
            background: var(--panel);
            border-radius: 14px;
            padding: 18px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 10px 28px rgba(0, 0, 0, 0.45);
        }

        .metric .label {
            font-size: 13px;
            color: var(--muted);
        }

        .metric .value {
            font-size: 32px;
            font-weight: 800;
        }

        .main-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 22px;
        }

        .card {
            background: var(--panel);
            padding: 18px;
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, 0.06);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.45);
        }

        .card h6 {
            color: white;
            font-weight: 700;
        }

        .form-control,
        .form-select {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: #e8f6ff;
            border-radius: 10px;
            padding: 10px 12px;
        }

        .form-control:focus,
        .form-select:focus {
            background: rgba(255, 255, 255, 0.07);
            border-color: #6cb8ff;
            color: #fff;
            box-shadow: 0 0 0 1px #6cb8ff;
        }

        label {
            font-weight: 600;
            color: rgba(235, 245, 255, 0.85);
        }

        button.btn-save {
            background: #2563eb;
            border: none;
            padding: 10px 16px;
            border-radius: 10px;
            font-weight: 700;
        }

        button.btn-save:hover {
            background: #1d4ed8;
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.14);
            color: #fff;
            border-radius: 10px;
            font-weight: 700;
        }

        /* file input */
        input[type=file] {
            padding: 10px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            color: #e8f6ff;
        }

        /* Placeholder */
        ::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        /* RESPONSIVE */
        @media (max-width: 992px) {
            .sidebar {
                display: none;
            }

            .metrics {
                flex-direction: column;
            }

            .main-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

</head>

<body>

    <div class="app">

        <!-- SIDEBAR -->
         <aside class="sidebar">
            <div class="brand">
                <div class="logo">V</div>
                <div>
                    <div style="font-weight:800">Admin</div>
                    <div style="font-size:12px;color:rgba(255,255,255,0.7)">Panel</div>
                </div>
            </div>

            <nav class="nav">
                <a href="index.php" class="active">
                    <svg width="16" height="16" viewBox="0 0 24 24">
                        <path fill="currentColor" d="M3 13h8V3H3v10zM21 21h-8V11h8v10zM3 21h8v-6H3v6zM21 3v6h-8V3h8z" />
                    </svg>
                    Dashboard
                </a>

                <a href="villas.php">
                    <svg width="16" height="16" viewBox="0 0 24 24">
                        <path fill="currentColor" d="M12 3l9 6v12H3V9l9-6z" />
                    </svg>
                    Kelola Villa
                </a>

                <a href="facilities.php">
                    <svg width="16" height="16" viewBox="0 0 24 24">
                        <path fill="currentColor" d="M4 6h16v2H4zm0 5h16v2H4zm0 5h10v2H4z" />
                    </svg>
                    Kelola Fasilitas
                </a>

                <a href="bookings.php">
                    <svg width="16" height="16" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-width="2" d="M7 10l5 5 5-7" />
                    </svg>
                    Kelola Booking
                </a>
                <a href="add_payment_method.php">
                    <svg width="16" height="16" viewBox="0 0 24 24">
                        <path fill="currentColor" d="M19 11H13V5h-2v6H5v2h6v6h2v-6h6z" />
                    </svg>
                    Tambah Metode Pembayaran
                </a>
            </nav>
            <div class="logout">
                Logged as <strong><?= htmlspecialchars($_SESSION['user'] ?? 'Admin') ?></strong><br><br>
                <a href="../frontend/logout.php">Logout</a>
            </div>
        </aside>


        <!-- CONTENT -->
        <main class="content">

            <h3>Tambah Metode Pembayaran</h3>
            <?= $msg ?>

            <div class="card mt-3">

                <form method="POST">

                    <label class="form-label">Nama Metode Pembayaran</label>
                    <input type="text" name="name" class="form-control mb-3" required>

                    <label class="form-label">Nomor Rekening / Nomor E-Wallet</label>
                    <input type="text" name="account_number" class="form-control mb-3" required>

                    <label class="form-label">Nama Pemilik Rekening / Akun</label>
                    <input type="text" name="account_name" class="form-control mb-3" required>

                    <label class="form-label">Detail / Instruksi Pembayaran</label>
                    <textarea name="details" class="form-control mb-3" rows="4" required></textarea>

                    <label class="form-label">Status</label>
                    <select name="is_active" class="form-select mb-4">
                        <option value="1">Aktif</option>
                        <option value="0">Nonaktif</option>
                    </select>

                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <a href="payment_methods.php" class="btn btn-secondary">Kembali</a>

                </form>

            </div>


        </main>

    </div>

</body>

</html>