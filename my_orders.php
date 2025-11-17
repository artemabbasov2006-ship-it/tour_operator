<?php
require 'db.php';
require 'auth.php';

require_login();
require_role('client');

$user = current_user();
$client_id = $user['id'];

$sql = "
SELECT 
    o.id, o.created_at, o.status, o.payment_status, o.total_amount,
    td.start_date, td.end_date,
    t.title, d.country, d.city
FROM orders o
JOIN tour_date td   ON td.id = o.tour_date_id
JOIN tour_package t ON t.id = td.tour_id
JOIN destination d  ON d.id = t.destination_id
WHERE o.buyer_user_id = ?
ORDER BY o.created_at DESC
";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('i', $client_id);
$stmt->execute();
$res = $stmt->get_result();
$stmt->close();
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Мои заявки — Туроператор</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="js/script.js" defer></script>
</head>
<body>
<?php include 'header.php'; ?>
<main class="container" role="main">
    <h1>Мои заявки</h1>

    <?php if ($res->num_rows === 0): ?>
        <p>У вас пока нет заявок.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>№</th>
                <th>Тур</th>
                <th>Направление</th>
                <th>Период</th>
                <th>Статус заявки</th>
                <th>Статус оплаты</th>
                <th>Сумма</th>
                <th>Создано</th>
            </tr>
            <?php while ($row = $res->fetch_assoc()): ?>
                <tr>
                    <td><?= (int)$row['id'] ?></td>
                    <td><?= htmlspecialchars($row['title']) ?></td>
                    <td><?= htmlspecialchars($row['country']) ?>, <?= htmlspecialchars($row['city']) ?></td>
                    <td><?= htmlspecialchars($row['start_date']) ?> — <?= htmlspecialchars($row['end_date']) ?></td>
                    <td><?= htmlspecialchars($row['status']) ?></td>
                    <td><?= htmlspecialchars($row['payment_status']) ?></td>
                    <td><?= number_format($row['total_amount'], 2, ',', ' ') ?> ₽</td>
                    <td><?= htmlspecialchars($row['created_at']) ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php endif; ?>
</main>
</body>
</html>
