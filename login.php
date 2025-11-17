<?php
require 'db.php';
require 'auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login    = trim($_POST['login'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($login === '' || $password === '') {
        $error = 'Введите логин и пароль.';
    } else {
        $stmt = $mysqli->prepare("
            SELECT u.id, u.login, u.password_hash, u.full_name, u.email, u.is_active,
                   r.code AS role_code
            FROM users u
            JOIN roles r ON r.id = u.role_id
            WHERE u.login = ?
            LIMIT 1
        ");
        $stmt->bind_param('s', $login);
        $stmt->execute();
        $res  = $stmt->get_result();
        $user = $res->fetch_assoc();
        $stmt->close();

        $ip   = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua   = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $log_user_id = $user['id'] ?? null;
        $is_success  = 0;
        $reason      = '';

        if (!$user) {
            $reason = 'no_user';
            $error  = 'Неверный логин или пароль.';
        } elseif (!$user['is_active']) {
            $reason = 'inactive';
            $error  = 'Учётная запись заблокирована.';
        } elseif (!password_verify($password, $user['password_hash'])) {
            $reason = 'bad_password';
            $error  = 'Неверный логин или пароль.';
        } else {
            $reason = 'ok';
            $is_success = 1;

            $_SESSION['user'] = [
                'id'        => $user['id'],
                'login'     => $user['login'],
                'full_name' => $user['full_name'],
                'role_code' => $user['role_code'],
                'email'     => $user['email'],
            ];
            // лог пишем до редиректа
        }

        $stmt = $mysqli->prepare("
            INSERT INTO auth_log (user_id, attempted_login, ip, user_agent, is_success, reason)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            'isssis',
            $log_user_id,
            $login,
            $ip,
            $ua,
            $is_success,
            $reason
        );
        $stmt->execute();
        $stmt->close();

        if ($is_success) {
            header('Location: index.php');
            exit;
        }
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Вход — Туроператор</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="js/script.js" defer></script>
</head>
<body>
<?php include 'header.php'; ?>

<main class="container" role="main">
    <h1>Вход</h1>
    <p class="muted">Введите логин и пароль. При ошибке отобразится сообщение.</p>

    <div class="form-box">
        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="post" novalidate>
            <label>Логин</label>
            <input type="text" name="login" required pattern="^[A-Za-z0-9]{6,}$">

            <label>Пароль</label>
            <input type="password" name="password" required minlength="8">

            <button type="submit">Войти</button>
        </form>
    </div>
</main>
</body>
</html>
