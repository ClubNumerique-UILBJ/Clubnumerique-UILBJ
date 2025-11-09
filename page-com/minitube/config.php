<?php
// config.php
$host = 'mysql-clubnumerique-uilbj.alwaysdata.net';
 // adapte selon ton hôte AlwaysData
$dbname = 'clubnumerique-uilbj_minitube_db';
$user = '420028';
$password = 'CNUIL2025';

$dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $password, $options);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
