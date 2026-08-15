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

if ($spotId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'スポットIDは必須です']);
    exit;
}

// 体験用：/* と */ を外すと、空欄のコメントを投稿できなくなります
/*
if ($message === '') {
    http_response_code(400);
    echo json_encode(['error' => 'コメントを入力してください']);
    exit;
}
*/

$stmt = $pdo->prepare('INSERT INTO comments (spot_id, user_id, message) VALUES (:spot_id, :user_id, :message)');
$stmt->execute(['spot_id' => $spotId, 'user_id' => $currentUserId, 'message' => $message]);
$commentId = (int)$pdo->lastInsertId();
$createdAt = $pdo->query('SELECT created_at FROM comments WHERE id = ' . $commentId)->fetchColumn();

http_response_code(201);
echo json_encode([
    'ok' => true,
    'username' => $_SESSION['username'],
    'message' => $message,
    'created_at' => $createdAt,
]);
