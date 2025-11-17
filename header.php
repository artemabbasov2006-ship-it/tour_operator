<?php
require_once 'auth.php';
$user = current_user();
?>
<header class="site-header">
    <nav class="nav">
        <a href="index.php" class="logo">Туроператор</a>
        <ul class="nav-list">
            <li><a href="index.php">Туры</a></li>

            <?php if ($user): ?>
                <li><a href="my_orders.php">Мои заявки</a></li>

                <?php if (in_array($user['role_code'], ['manager','admin'], true)): ?>
                    <li><a href="manager_panel.php">Заявки клиентов</a></li>
                <?php endif; ?>

                <?php if ($user['role_code'] === 'admin'): ?>
                    <li><a href="admin_panel.php">Админ-панель</a></li>
                <?php endif; ?>

                <li class="nav-user">
                    <?= htmlspecialchars($user['full_name']) ?>
                    (<?= htmlspecialchars($user['role_code']) ?>)
                </li>
                <li><a href="logout.php" class="js-confirm">Выход</a></li>
            <?php else: ?>
                <li><a href="login.php">Вход</a></li>
                <li><a href="register.php">Регистрация</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>
