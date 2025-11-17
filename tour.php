<?php
require 'db.php';
require 'auth.php';

$tourId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($tourId <= 0) {
    die('Некорректный идентификатор тура');
}

$stmt = $mysqli->prepare("
    SELECT t.id, t.title, t.description, t.route_description,
           t.duration_days, t.base_price,
           d.country, d.city
    FROM tour_package t
    JOIN destination d ON d.id = t.destination_id
    WHERE t.id = ?
");
$stmt->bind_param('i', $tourId);
$stmt->execute();
$tour = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$tour) {
    die('Тур не найден');
}

$stmt = $mysqli->prepare("
    SELECT id, start_date, end_date, available_seats, price_per_person, status
    FROM tour_date
    WHERE tour_id = ?
    ORDER BY start_date
");
$stmt->bind_param('i', $tourId);
$stmt->execute();
$dates = $stmt->get_result();
$stmt->close();
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($tour['title']) ?> — Туроператор</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="js/script.js" defer></script>
</head>
<body>
<?php include 'header.php'; ?>
<main class="container" role="main">
    <p><a href="index.php">&larr; Назад к каталогу</a></p>

    <h1><?= htmlspecialchars($tour['title']) ?></h1>
    <p><strong>Направление:</strong> <?= htmlspecialchars($tour['country']) ?>, <?= htmlspecialchars($tour['city']) ?></p>
    <p><strong>Длительность:</strong> <?= (int)$tour['duration_days'] ?> дней</p>
    <p><strong>Базовая цена:</strong> <?= number_format($tour['base_price'], 2, ',', ' ') ?> ₽</p>

    <?php if (!empty($tour['description'])): ?>
        <h3>Описание тура</h3>
        <p><?= nl2br(htmlspecialchars($tour['description'])) ?></p>
    <?php endif; ?>

    <?php if (!empty($tour['route_description'])): ?>
        <h3>Маршрут</h3>
        <p><?= nl2br(htmlspecialchars($tour['route_description'])) ?></p>
    <?php endif; ?>

    <h3>Даты выезда</h3>
    <?php if ($dates->num_rows === 0): ?>
        <p>Для этого тура пока нет дат.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>Период</th>
                <th>Доступно мест</th>
                <th>Статус</th>
                <th>Цена за человека</th>
                <th></th>
            </tr>
            <?php while ($d = $dates->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($d['start_date']) ?> — <?= htmlspecialchars($d['end_date']) ?></td>
                    <td><?= (int)$d['available_seats'] ?></td>
                    <td><?= htmlspecialchars($d['status']) ?></td>
                    <td><?= number_format($d['price_per_person'], 2, ',', ' ') ?> ₽</td>
                    <td>
                        <?php if ($d['status'] === 'open'): ?>
                            <a class="btn" href="book.php?date_id=<?= (int)$d['id'] ?>">Забронировать</a>
                        <?php else: ?>
                            недоступно
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php endif; ?>
</main>
</body>
</html>
