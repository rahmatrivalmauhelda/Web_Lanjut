<?php
require_once __DIR__ . '/../config/functions.php';

$err = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $pass = trim($_POST['password']);
    $confirm = trim($_POST['confirm']);

    if ($pass !== $confirm) {
        $err = 'Password tidak sama!';
    } else {
        // Cek apakah email sudah terdaftar
        $check = $conn->prepare("SELECT id FROM users WHERE email=?");
        $check->bind_param("s", $email);
        $check->execute();
        $existing = $check->get_result()->fetch_assoc();

        if ($existing) {
            $err = 'Email sudah digunakan!';
        } else {
            // Simpan data customer
            $hash = md5($pass);
            $role = "customer";

            $st = $conn->prepare("INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)");
            $st->bind_param("ssss", $name, $email, $hash, $role);

            if ($st->execute()) {
                header("Location: login.php?registered=1");
                exit;
            } else {
                $err = "Gagal registrasi: " . $conn->error;
            }
        }
    }
}
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Register Customer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container py-4">

        <h3>Registrasi Customer</h3>

        <?php if ($err): ?>
            <div class="alert alert-danger"><?php echo $err; ?></div>
        <?php endif; ?>

        <form method="post" class="mt-3">

            <div class="mb-2">
                <input name="name" class="form-control" placeholder="Nama lengkap" required>
            </div>

            <div class="mb-2">
                <input name="email" type="email" class="form-control" placeholder="Email" required>
            </div>

            <div class="mb-2">
                <input name="password" type="password" class="form-control" placeholder="Password" required>
            </div>

            <div class="mb-2">
                <input name="confirm" type="password" class="form-control" placeholder="Konfirmasi Password" required>
            </div>

            <button class="btn btn-primary w-100 mt-2">Daftar</button>
        </form>

        <div class="mt-3">
            <a href="login.php">&larr; Kembali ke Login</a>
        </div>

    </div>
</body>

</html>
