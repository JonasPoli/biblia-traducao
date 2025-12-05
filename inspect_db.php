<?php

$dsn = 'mysql:host=localhost;port=3306;dbname=nepe;charset=utf8mb4';
$user = 'root';
$pass = 'wab12345678';

try {
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "--- theme ---\n";
    $stmt = $pdo->query("DESCRIBE theme");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo $col['Field'] . " - " . $col['Type'] . "\n";
    }

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
