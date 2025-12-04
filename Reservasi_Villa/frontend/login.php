<?php
require_once __DIR__ . '/../config/functions.php';

$err = '';
$success = '';
$mode = $_GET['mode'] ?? 'login'; // login atau register

if ($mode === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $pass = $_POST['password'];

    $st = $conn->prepare('SELECT id,name,password,role FROM users WHERE email=?');
    $st->bind_param('s', $email);
    $st->execute();
    $u = $st->get_result()->fetch_assoc();

    if ($u && $u['password'] === md5($pass)) {
        $_SESSION['user_id'] = $u['id'];
        $_SESSION['name'] = $u['name'];
        $_SESSION['role'] = $u['role'];

        if ($u['role'] === 'admin') {
            header('Location: ../backend/index.php');
        } else {
            header('Location: list_villas.php'); // CUSTOMER MASUK KE LIST VILLA
        }
        exit;
    } else {
        $err = 'Email atau password salah';
    }
}

if ($mode === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $pass = trim($_POST['password']);
    $confirm = trim($_POST['confirm']);

    if ($pass !== $confirm) {
        $err = 'Password dan konfirmasi tidak sama!';
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE email=?");
        $check->bind_param("s", $email);
        $check->execute();
        $exist = $check->get_result()->fetch_assoc();

        if ($exist) {
            $err = 'Email sudah digunakan!';
        } else {
            $hashed = md5($pass);
            $role = 'customer';

            $ins = $conn->prepare("INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)");
            $ins->bind_param("ssss", $name, $email, $hashed, $role);

            if ($ins->execute()) {
                header("Location: login.php?registered=1");
                exit;
            } else {
                $err = 'Gagal registrasi: ' . $conn->error;
            }
        }
    }
}
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Login / Register</title>
    <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            margin: 0;
            padding: 0;
            height: 100vh;
            background: url('../assets/background.jpeg') no-repeat center center fixed;
            background-size: cover;
            /* biar full layar */
            font-family: 'Segoe UI', Arial, sans-serif;

            /* Tambahan efek gelap (opsional) */
            position: relative;
        }

        /* Overlay gelap agar form lebih jelas */
        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.45);
            backdrop-filter: blur(2px);
            z-index: 0;
        }

        .auth-container {
            max-width: 420px;
            margin: 100px auto;
            position: relative;
            z-index: 1;
        }

        .auth-card {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(12px) saturate(180%);
            -webkit-backdrop-filter: blur(12px) saturate(180%);

            border-radius: 18px;
            padding: 28px 30px;

            border: 1px solid rgba(255, 255, 255, 0.35);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.25);

            animation: fadeIn .4s ease;
        }


        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .auth-title {
            font-family: 'Great Vibes', cursive;
            font-size: 46px;
            font-weight: 400;
            color: #d6e7ff;
            text-shadow: 0 2px 6px rgba(0, 0, 0, 0.45);
            text-align: center;
            margin-bottom: 20px;
            line-height: 1.2;
        }


        input.form-control {
            border-radius: 12px;
            padding: 12px;
        }

        button.btn {
            border-radius: 12px;
            padding: 12px;
            font-weight: 600;
        }

        .switch-link {
            display: block;
            text-align: center;
            margin-top: 12px;
            color: #fff;
            text-shadow: 1px 1px 3px #000;
        }
    </style>

</head>

<body>

    <div class="auth-container">
        <div class="auth-card">

            <h3 class="auth-title mb-3">
                <?php echo ($mode === 'register') ? 'Daftar Akun Customer' : 'Welcome to Alahan Panjang'; ?>
            </h3>

            <?php if (isset($_GET['registered'])): ?>
                <div class="alert alert-success">Registrasi berhasil! Silakan login.</div>
            <?php endif; ?>

            <?php if ($err): ?>
                <div class="alert alert-danger"><?= $err ?></div>
            <?php endif; ?>

            <!-- =============== FORM LOGIN =============== -->
            <?php if ($mode === 'login'): ?>
                <form method="post">
                    <div class="mb-3">
                        <input name="email" type="email" class="form-control"
                            placeholder="Email" required>
                    </div>
                    <div class="mb-3">
                        <input name="password" type="password" class="form-control"
                            placeholder="Password" required>
                    </div>
                    <button class="btn btn-primary w-100">Login</button>
                </form>

                <a class="switch-link" href="login.php?mode=register">Belum punya akun? Daftar sekarang</a>
            <?php endif; ?>

            <!-- =============== FORM REGISTER =============== -->
            <?php if ($mode === 'register'): ?>
                <form method="post">
                    <div class="mb-3">
                        <input name="name" class="form-control" placeholder="Nama lengkap" required>
                    </div>
                    <div class="mb-3">
                        <input name="email" type="email" class="form-control" placeholder="Email" required>
                    </div>
                    <div class="mb-3">
                        <input name="password" type="password" class="form-control" placeholder="Password" required>
                    </div>
                    <div class="mb-3">
                        <input name="confirm" type="password" class="form-control" placeholder="Konfirmasi Password" required>
                    </div>

                    <button class="btn btn-success w-100">Daftar</button>
                </form>

                <a class="switch-link" href="login.php">&larr; Kembali ke Login</a>
            <?php endif; ?>

        </div>
    </div>

</body>

</html>