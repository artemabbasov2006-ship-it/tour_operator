<?php
// db.php — подключение к БД tour_operator

$host = 'localhost';
$user = 'root';      // для XAMPP по умолчанию
$pass = '';          // если ставил пароль — измени здесь
$db   = 'tour_operator';

$mysqli = new mysqli($host, $user, $pass, $db);

if ($mysqli->connect_errno) {
    die('Ошибка подключения к БД: ' . $mysqli->connect_error);
}

if (!$mysqli->set_charset('utf8mb4')) {
    die('Ошибка установки кодировки: ' . $mysqli->error);
}
