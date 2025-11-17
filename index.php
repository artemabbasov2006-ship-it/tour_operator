<?php
require 'db.php';
require 'auth.php';
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>Туроператор: турпакеты</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="js/script.js" defer></script>
</head>
<body>
<?php include 'header.php'; ?>

<main class="container" role="main">
    <h1>Каталог турпакетов</h1>
    <p class="muted">Выберите тур, чтобы посмотреть описание, маршрут и доступные даты выезда.</p>

    <?php
    // Внутренний SELECT считает ближайшую открытую дату, внешний сортирует по ней
    $sql = "
        SELECT *
        FROM (
            SELECT 
                t.id,
                t.title,
                t.description,
                t.base_price,
                t.duration_days,
                d.country,
                d.city,
                MIN(
                    CASE 
                        WHEN td.status = 'open' THEN td.start_date
                        ELSE NULL
                    END
                ) AS nearest_date
            FROM tour_package t
            JOIN destination d ON d.id = t.destination_id
            LEFT JOIN tour_date td ON td.tour_id = t.id
            WHERE t.is_active = 1
            GROUP BY 
                t.id,
                t.title,
                t.description,
                t.base_price,
                t.duration_days,
                d.country,
                d.city
        ) AS t_sorted
        ORDER BY 
            t_sorted.nearest_date IS NULL,
            t_sorted.nearest_date ASC
    ";

    $result = $mysqli->query($sql);

    if (!$result) {
        echo '<p class="error">Ошибка загрузки туров: ' . htmlspecialchars($mysqli->error) . '</p>';
    } elseif ($result->num_rows === 0) {
        echo '<p>Туров пока нет.</p>';
    } else {
        echo '<div class="cards">';
        while ($row = $result->fetch_assoc()) {
            $desc = $row['description'] ?? '';
            if (mb_strlen($desc, 'UTF-8') > 160) {
                $desc = mb_substr($desc, 0, 160, 'UTF-8') . '...';
            }
            ?>
            <article class="card">
                <h2><?= htmlspecialchars($row['title']) ?></h2>
                <div class="muted">
                    Направление: <?= htmlspecialchars($row['country']) ?>, <?= htmlspecialchars($row['city']) ?>
                </div>
                <div>Длительность: <?= (int)$row['duration_days'] ?> дней</div>
                <div>Цена от: <strong><?= number_format($row['base_price'], 2, ',', ' ') ?> ₽</strong></div>
                <div class="muted">
                    <?php if (!empty($row['nearest_date'])): ?>
                        Ближайший выезд: <?= htmlspecialchars($row['nearest_date']) ?>
                    <?php else: ?>
                        Даты выезда уточняются
                    <?php endif; ?>
                </div>
                <?php if ($desc): ?>
                    <p><?= nl2br(htmlspecialchars($desc)) ?></p>
                <?php endif; ?>
                <a class="btn" href="tour.php?id=<?= (int)$row['id'] ?>">Подробнее</a>
            </article>
            <?php
        }
        echo '</div>';
    }
    ?>
</main>
</body>
</html>
