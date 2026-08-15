<?php

require __DIR__ . '/../src/db.php';

session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'ログインが必要です']);
    exit;
}

$pdo = get_db();

$postalCode = preg_replace('/[^0-9]/', '', (string)($_GET['code'] ?? ''));

if (strlen($postalCode) !== 7) {
    http_response_code(400);
    echo json_encode(['error' => '郵便番号は7桁の数字で指定してください']);
    exit;
}

$stmt = $pdo->prepare('SELECT prefecture, city, town FROM postal_codes WHERE postal_code = :code LIMIT 1');
$stmt->execute(['code' => $postalCode]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$result) {
    http_response_code(404);
    echo json_encode(['error' => '該当する住所が見つかりませんでした']);
    exit;
}

echo json_encode([
    'prefecture' => $result['prefecture'],
    'city' => $result['city'],
    'town' => $result['town'],
]);
