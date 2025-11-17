<?php
require 'db.php';
require 'auth.php';

require_login();
require_role(['manager','admin']);

$user = current_user();
$manager_id = $user['id'];

// смена статуса
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = (int)($_POST['order_id'] ?? 0);
    $status   = $_POST['status'] ?? '';

    $allowed = ['new','confirmed','paid','cancelled'];
    if ($order_id > 0 && in_array($status, $allowed, true)) {
        if ($status === 'paid') {
            $stmt = $mysqli->prepare("
                UPDATE orders
                SET status = ?, payment_status = 'paid', paid_at = NOW(), buyer_user_id = buyer_user_id,
                    tour_date_id = tour_date_id, payment_method_id = payment_method_id,
                    participants_count = participants_count, preferences = preferences, email_to = email_to,
                    total_amount = total_amount,
                    buyer_user_id = buyer_user_id,
                    tour_date_id = tour_date_id,
                    payment_method_id = payment_method_id,
                    participants_count = participants_count
                WHERE id = ?
            ");
            // грязный хак с повторяющимися полями можно заменить простой UPDATE, но главное — логика paid_at
        } else {
            $stmt = $mysqli->prepare("
                UPDATE orders
                SET status = ?, manager_id = ?
                WHERE id = ?
            ");
        }

        if ($status === 'paid') {
            // упростим: для paid просто отдельный запрос:
            $stmt->close();
            $stmt = $mysqli->prepare("
                UPDATE orders
                SET status = ?, payment_status = 'paid', paid_at = NOW(), manager_id = ?
                WHERE id = ?
            ");
        }

        $stmt->bind_param('sii', $status, $manager_id, $order_id);
        $stmt->execute();
        $stmt->close();
    }
}

$sql = "
SELECT 
    o.id, o.created_at, o.status, o.payment_status, o.total_amount,
    buyer.full_name AS client_name,
    td.start_date, td.end_date,
    t.title, d.country, d.city
FROM orders o
JOIN users buyer     ON buyer.id = o.buyer_user_id
JOIN tour_date td    ON td.id = o.tour_date_id
JOIN tour_package t  ON t.id = td.tour_id
JOIN destination d   ON d.id = t.destination_id
ORDER BY o.created_at DESC
";
$res = $mysqli->query($sql);
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Заявки клиентов — Туроператор</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="js/script.js" defer></script>
</head>
<body>
<?php include 'header.php'; ?>
<main class="container" role="main">
    <h1>Заявки клиентов</h1>

    <?php if ($res->num_rows === 0): ?>
        <p>Заявок пока нет.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>№</th>
                <th>Клиент</th>
                <th>Тур</th>
                <th>Направление</th>
                <th>Период</th>
                <th>Статус</th>
                <th>Оплата</th>
                <th>Сумма</th>
                <th>Изменить статус</th>
            </tr>
            <?php while ($row = $res->fetch_assoc()): ?>
                <tr>
                    <td><?= (int)$row['id'] ?></td>
                    <td><?= htmlspecialchars($row['client_name']) ?></td>
                    <td><?= htmlspecialchars($row['title']) ?></td>
                    <td><?= htmlspecialchars($row['country']) ?>, <?= htmlspecialchars($row['city']) ?></td>
                    <td><?= htmlspecialchars($row['start_date']) ?> — <?= htmlspecialchars($row['end_date']) ?></td>
                    <td><?= htmlspecialchars($row['status']) ?></td>
                    <td><?= htmlspecialchars($row['payment_status']) ?></td>
                    <td><?= number_format($row['total_amount'], 2, ',', ' ') ?> ₽</td>
                    <td>
                        <form method="post" style="margin:0;">
                            <input type="hidden" name="order_id" value="<?= (int)$row['id'] ?>">
                            <select name="status">
                                <option value="new"       <?= $row['status']==='new' ? 'selected' : '' ?>>new</option>
                                <option value="confirmed" <?= $row['status']==='confirmed' ? 'selected' : '' ?>>confirmed</option>
                                <option value="paid"      <?= $row['status']==='paid' ? 'selected' : '' ?>>paid</option>
                                <option value="cancelled" <?= $row['status']==='cancelled' ? 'selected' : '' ?>>cancelled</option>
                            </select>
                            <button type="submit">OK</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php endif; ?>
</main>
</body>
</html>
