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
$message = trim((string)($input['message'] ?? ''));

if ($spotId <= 0 || $message === '') {
    http_response_code(400);
    echo json_encode(['error' => 'スポットIDとコメントは必須です']);
    exit;
}

// この2行のコメントを外すとコメント投稿が有効になります
// $stmt = $pdo->prepare('INSERT INTO comments (spot_id, user_id, message) VALUES (:spot_id, :user_id, :message)');
// $stmt->execute(['spot_id' => $spotId, 'user_id' => $currentUserId, 'message' => $message]);

http_response_code(201);
echo json_encode(['ok' => true]);
