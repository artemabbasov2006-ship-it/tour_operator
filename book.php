<?php
require 'db.php';
require 'auth.php';

require_login();
require_role('client');

$user = current_user();
$client_id = $user['id'];

$dateId = isset($_GET['date_id']) ? (int)$_GET['date_id'] : 0;
if ($dateId <= 0) {
    die('Некорректная дата тура');
}

$stmt = $mysqli->prepare("
    SELECT td.id, td.start_date, td.end_date, td.available_seats, td.price_per_person,
           t.title, d.country, d.city
    FROM tour_date td
    JOIN tour_package t ON t.id = td.tour_id
    JOIN destination d  ON d.id = t.destination_id
    WHERE td.id = ? AND td.status = 'open'
");
$stmt->bind_param('i', $dateId);
$stmt->execute();
$date = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$date) {
    die('Дата тура не найдена или недоступна.');
}

$error = '';
$success = '';

$pm_res = $mysqli->query("SELECT id, name FROM payment_method WHERE is_active = 1");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $participants       = (int)($_POST['participants'] ?? 0);
    $payment_method_id  = (int)($_POST['payment_method_id'] ?? 0);
    $preferences        = trim($_POST['preferences'] ?? '');

    if ($participants <= 0) {
        $error = 'Укажите количество участников.';
    } elseif ($participants > (int)$date['available_seats']) {
        $error = 'Недостаточно доступных мест.';
    } else {
        $total = $participants * (float)$date['price_per_person'];
        $email_to = $user['email'] ?? '';

        $stmt = $mysqli->prepare("
            INSERT INTO orders
              (buyer_user_id, tour_date_id, participants_count, preferences,
               email_to, payment_method_id, status, payment_status, total_amount)
            VALUES (?, ?, ?, ?, ?, ?, 'new', 'pending', ?)
        ");
        $stmt->bind_param(
            'iiissid',
            $client_id,
            $dateId,
            $participants,
            $preferences,
            $email_to,
            $payment_method_id,
            $total
        );

        if ($stmt->execute()) {
            $stmt->close();
            $success = 'Заявка успешно создана.';
        } else {
            $error = 'Ошибка создания заявки: ' . htmlspecialchars($stmt->error);
            $stmt->close();
        }
    }
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Заявка на тур — Туроператор</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="js/script.js" defer></script>
</head>
<body>
<?php include 'header.php'; ?>
<main class="container" role="main">
    <h1>Заявка на тур</h1>

    <p>
        Тур: <strong><?= htmlspecialchars($date['title']) ?></strong><br>
        Направление: <?= htmlspecialchars($date['country']) ?>, <?= htmlspecialchars($date['city']) ?><br>
        Период: <?= htmlspecialchars($date['start_date']) ?> — <?= htmlspecialchars($date['end_date']) ?><br>
        Цена за человека: <?= number_format($date['price_per_person'], 2, ',', ' ') ?> ₽
    </p>

    <div class="form-box">
        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p><?= htmlspecialchars($success) ?> <a href="my_orders.php">Перейти к списку заявок</a></p>
        <?php endif; ?>

        <form method="post">
            <label>Количество участников</label>
            <input type="number" name="participants" min="1"
                   max="<?= (int)$date['available_seats'] ?>" required>

            <label>Предпочтения по размещению</label>
            <textarea name="preferences" placeholder="Тип номера, питание, пожелания..."></textarea>

            <label>Способ оплаты</label>
            <select name="payment_method_id" required>
                <?php while ($pm = $pm_res->fetch_assoc()): ?>
                    <option value="<?= (int)$pm['id'] ?>"><?= htmlspecialchars($pm['name']) ?></option>
                <?php endwhile; ?>
            </select>

            <button type="submit">Отправить заявку</button>
        </form>
    </div>
</main>
</body>
</html>
