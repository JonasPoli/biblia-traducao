<?php
error_reporting(E_ERROR | E_PARSE);

$dsn = 'mysql:host=localhost;port=3306;dbname=nepe;charset=utf8mb4';
$user = 'root';
$pass = 'wab12345678';

$book_id = $argv[1] ?? 45;

try {
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("SELECT id, chapter, verse FROM biblia_verse WHERE book_id = ?");
    $stmt->execute([$book_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $map = [];
    foreach ($rows as $row) {
        $key = $row['chapter'] . ':' . $row['verse'];
        $map[$key] = $row['id'];
    }

    echo json_encode($map);

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
    exit(1);
}
