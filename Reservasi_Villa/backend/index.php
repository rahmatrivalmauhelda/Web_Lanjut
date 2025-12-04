<?php
require_once __DIR__ . '/../config/functions.php';
require_admin();

// ambil data
$total_v = (int)$conn->query('SELECT COUNT(*) as c FROM villas')->fetch_assoc()['c'];
$total_b = (int)$conn->query('SELECT COUNT(*) as c FROM bookings')->fetch_assoc()['c'];
$pending = (int)$conn->query("SELECT COUNT(*) as c FROM bookings WHERE status='pending'")->fetch_assoc()['c'];
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --bg-1: #001628;
            --bg-2: #003a57;
            --panel: rgba(255, 255, 255, 0.04);
            --muted: rgba(235, 245, 255, 0.75);
        }

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
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            color: var(--muted);
            font-weight: 600;
            border-radius: 8px;
            text-decoration: none;
            margin-bottom: 8px;
            transition: .15s ease;
        }

        .nav a:hover {
            transform: translateX(6px);
            background: rgba(255, 255, 255, 0.04);
            color: white;
        }

        .nav a.active {
            background: rgba(255, 255, 255, 0.05);
            color: white;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.08);
        }

        /* Logout */
        .sidebar .logout {
            position: absolute;
            bottom: 18px;
            left: 20px;
            right: 20px;
            font-size: 13px;
            color: rgba(255, 255, 255, 0.7);
        }

        .sidebar .logout a {
            color: #fff;
            text-decoration: none;
            font-weight: 700;
            display: inline-block;
            padding: 8px 12px;
            background: rgba(255, 255, 255, 0.06);
            border-radius: 8px;
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

        /* METRICS */
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

        /* GRID */
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
            </nav>
            <div class="logout">
                Logged as <strong><?= htmlspecialchars($_SESSION['user'] ?? 'Admin') ?></strong><br><br>
                <a href="../frontend/logout.php">Logout</a>
            </div>
        </aside>

        <main class="content">

            <div class="topbar">
                <h3>Dashboard</h3>
                <div class="sub">Selamat datang, admin</div>
            </div>

            <!-- METRICS -->
            <div class="metrics">
                <div class="metric">
                    <div class="label">Total Villa</div>
                    <div class="value"><?= $total_v ?></div>
                </div>

                <div class="metric">
                    <div class="label">Total Booking</div>
                    <div class="value"><?= $total_b ?></div>
                </div>

                <div class="metric">
                    <div class="label">Pending</div>
                    <div class="value"><?= $pending ?></div>
                </div>
            </div>

            <!-- ROW: PREVIEW + CHART -->
            <div class="main-grid">

                <div class="card">
                    <h6>Preview</h6>
                    <img src="<?= $previewImage ?>" style="width:100%;height:220px;border-radius:10px;object-fit:cover;">
                </div>

                <div class="card">
                    <h6>Statistik</h6>
                    <div style="height:260px;">
                        <canvas id="mainChart"></canvas>
                    </div>
                </div>

            </div>

        </main>
    </div>

    <!-- ======================================================
     CHART JS
====================================================== -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('mainChart').getContext('2d');

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Total Villa', 'Total Booking', 'Pending'],
                datasets: [{
                    data: [<?= $total_v ?>, <?= $total_b ?>, <?= $pending ?>],
                    backgroundColor: ['#2563eb', '#10b981', '#f59e0b'],
                    borderRadius: 8,
                    barThickness: 34
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        bodyColor: "#fff",
                        titleColor: "#fff"
                    },
                },
                scales: {
                    x: {
                        ticks: {
                            color: "#fff"
                        },
                        grid: {
                            color: 'rgba(255,255,255,0.08)'
                        }
                    },
                    y: {
                        ticks: {
                            color: "#fff"
                        },
                        grid: {
                            color: 'rgba(255,255,255,0.08)'
                        }
                    },
                }
            }
        });
    </script>

</body>

</html>