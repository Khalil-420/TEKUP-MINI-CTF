<?php
$host = 'sql8.freemysqlhosting.net';
$dbname = 'sql8692464';
$username = 'sql8692464';
$password = 'VpY4qbyRxx';

$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
