<?php
require 'db.php';
require 'auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login     = trim($_POST['login'] ?? '');
    $password  = trim($_POST['password'] ?? '');
    $password2 = trim($_POST['password2'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $email     = trim($_POST['email'] ?? '');

    // Базовые проверки
    if ($login === '' || $password === '' || $password2 === '' ||
        $full_name === '' || $phone === '' || $email === '') {
        $error = 'Заполните все поля.';
    } elseif ($password !== $password2) {
        $error = 'Пароли не совпадают.';
    } elseif (!preg_match('/^[A-Za-z0-9]{6,}$/', $login)) {
        $error = 'Логин: только латиница и цифры, минимум 6 символов.';
    } elseif (mb_strlen($password) < 8) {
        $error = 'Пароль должен быть не короче 8 символов.';
    } elseif (!preg_match('/^8\([0-9]{3}\)[0-9]{3}-[0-9]{2}-[0-9]{2}$/', $phone)) {
        // ВАЖНО: та же маска, что в триггере БД
        $error = 'Телефон должен быть в формате 8(XXX)XXX-XX-XX, например 8(900)123-45-67.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Некорректный адрес электронной почты.';
    } else {
        // Проверка уникальности логина
        $stmt = $mysqli->prepare("SELECT id FROM users WHERE login = ?");
        $stmt->bind_param('s', $login);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($exists) {
            $error = 'Такой логин уже существует.';
        } else {
            // Роль client
            $res = $mysqli->query("SELECT id FROM roles WHERE code = 'client' LIMIT 1");
            $role = $res->fetch_assoc();
            $role_id = $role ? (int)$role['id'] : 0;

            $hash = password_hash($password, PASSWORD_DEFAULT);

            try {
                $stmt = $mysqli->prepare("
                    INSERT INTO users (login, password_hash, full_name, phone, email, role_id)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param('sssssi', $login, $hash, $full_name, $phone, $email, $role_id);
                $stmt->execute();
                $user_id = $stmt->insert_id;
                $stmt->close();

                // Сразу авторизуем
                $_SESSION['user'] = [
                    'id'        => $user_id,
                    'login'     => $login,
                    'full_name' => $full_name,
                    'role_code' => 'client',
                    'email'     => $email,
                ];
                header('Location: index.php');
                exit;
            } catch (mysqli_sql_exception $e) {
                // Если вдруг снова сработает триггер — покажем текст сообщения
                $error = 'Ошибка сохранения в БД: ' . htmlspecialchars($e->getMessage());
            }
        }
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Регистрация — Туроператор</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="js/script.js" defer></script>
</head>
<body>
<?php include 'header.php'; ?>

<main class="container" role="main">
    <h1>Регистрация</h1>
    <p class="muted">Все поля обязательны для заполнения. Логин, пароль, телефон и e-mail проверяются по правилам предметной области.</p>

    <div class="form-box">
        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="post" novalidate>
            <label>Логин (латиница и цифры, ≥ 6 символов)</label>
            <input type="text"
                   name="login"
                   required
                   minlength="6"
                   maxlength="64"
                   pattern="^[A-Za-z0-9]{6,}$"
                   title="Только латиница и цифры, не менее 6 символов."
                   value="<?= htmlspecialchars($_POST['login'] ?? '') ?>">

            <label>Пароль (≥ 8 символов)</label>
            <input type="password"
                   name="password"
                   required
                   minlength="8"
                   maxlength="128">

            <label>Повтор пароля</label>
            <input type="password"
                   name="password2"
                   required
                   minlength="8"
                   maxlength="128">

            <label>ФИО (кириллица)</label>
            <input type="text"
                   name="full_name"
                   required
                   maxlength="200"
                   value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">

            <label>Телефон (формат 8(XXX)XXX-XX-XX)</label>
            <input type="tel"
                   name="phone"
                   required
                   placeholder="8(900)123-45-67"
                   pattern="^8\([0-9]{3}\)[0-9]{3}-[0-9]{2}-[0-9]{2}$"
                   title="Введите телефон в формате 8(XXX)XXX-XX-XX"
                   value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">

            <label>E-mail</label>
            <input type="email"
                   name="email"
                   required
                   maxlength="255"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">

            <button type="submit">Создать аккаунт</button>
        </form>
    </div>
</main>
</body>
</html>
