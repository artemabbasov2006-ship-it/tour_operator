<?php
require 'db.php';
require 'auth.php';

require_login();
require_role('admin');

// смена роли
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id   = (int)($_POST['user_id'] ?? 0);
    $role_code = $_POST['role_code'] ?? '';

    if ($user_id > 0 && in_array($role_code, ['admin','manager','client'], true)) {
        $stmt = $mysqli->prepare("SELECT id FROM roles WHERE code = ? LIMIT 1");
        $stmt->bind_param('s', $role_code);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($res) {
            $role_id = (int)$res['id'];
            $stmt = $mysqli->prepare("UPDATE users SET role_id = ? WHERE id = ?");
            $stmt->bind_param('ii', $role_id, $user_id);
            $stmt->execute();
            $stmt->close();
        }
    }
}

$sql = "
SELECT u.id, u.login, u.full_name, u.email, u.phone, u.is_active,
       r.code AS role_code
FROM users u
JOIN roles r ON r.id = u.role_id
ORDER BY u.id
";
$res = $mysqli->query($sql);
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Админ-панель — Туроператор</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="js/script.js" defer></script>
</head>
<body>
<?php include 'header.php'; ?>
<main class="container" role="main">
    <h1>Админ-панель: пользователи и роли</h1>

    <?php if ($res->num_rows === 0): ?>
        <p>Пользователей пока нет.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>№</th>
                <th>Логин</th>
                <th>ФИО</th>
                <th>Email</th>
                <th>Телефон</th>
                <th>Роль</th>
                <th>Активен</th>
                <th>Изменить роль</th>
            </tr>
            <?php while ($row = $res->fetch_assoc()): ?>
                <tr>
                    <td><?= (int)$row['id'] ?></td>
                    <td><?= htmlspecialchars($row['login']) ?></td>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= htmlspecialchars($row['phone']) ?></td>
                    <td><?= htmlspecialchars($row['role_code']) ?></td>
                    <td><?= $row['is_active'] ? 'да' : 'нет' ?></td>
                    <td>
                        <?php if ($row['login'] === 'admin'): ?>
                            (нельзя изменить)
                        <?php else: ?>
                            <form method="post" style="margin:0;">
                                <input type="hidden" name="user_id" value="<?= (int)$row['id'] ?>">
                                <select name="role_code">
                                    <option value="admin"   <?= $row['role_code']==='admin'   ? 'selected' : '' ?>>admin</option>
                                    <option value="manager" <?= $row['role_code']==='manager' ? 'selected' : '' ?>>manager</option>
                                    <option value="client"  <?= $row['role_code']==='client'  ? 'selected' : '' ?>>client</option>
                                </select>
                                <button type="submit">Сохранить</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php endif; ?>
</main>
</body>
</html>
