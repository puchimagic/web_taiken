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
$currentUserId = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$spotId = (int)($input['spot_id'] ?? 0);
$type = (string)($input['type'] ?? '');

if ($spotId <= 0 || !in_array($type, ['want_to_go', 'helpful'], true)) {
    http_response_code(400);
    echo json_encode(['error' => 'スポットIDと種別は必須です']);
    exit;
}

$existsStmt = $pdo->prepare(
    'SELECT id FROM reactions WHERE user_id = :user_id AND spot_id = :spot_id AND type = :type'
);
$existsStmt->execute(['user_id' => $currentUserId, 'spot_id' => $spotId, 'type' => $type]);

if ($existsStmt->fetch()) {
    $pdo->prepare(
        'DELETE FROM reactions WHERE user_id = :user_id AND spot_id = :spot_id AND type = :type'
    )->execute(['user_id' => $currentUserId, 'spot_id' => $spotId, 'type' => $type]);
    $active = false;
} else {
    $pdo->prepare(
        'INSERT INTO reactions (user_id, spot_id, type) VALUES (:user_id, :spot_id, :type)'
    )->execute(['user_id' => $currentUserId, 'spot_id' => $spotId, 'type' => $type]);
    $active = true;
}

$countStmt = $pdo->prepare('SELECT COUNT(*) FROM reactions WHERE spot_id = :spot_id AND type = :type');
$countStmt->execute(['spot_id' => $spotId, 'type' => $type]);
$count = (int)$countStmt->fetchColumn();

echo json_encode(['ok' => true, 'active' => $active, 'count' => $count]);
