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

$title = trim((string)($_POST['title'] ?? ''));
$description = trim((string)($_POST['description'] ?? ''));

if ($title === '') {
    http_response_code(400);
    echo json_encode(['error' => 'タイトルは必須です']);
    exit;
}

if (!isset($_FILES['video']) || $_FILES['video']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => '動画ファイルを選択してください']);
    exit;
}

$uploadDir = __DIR__ . '/uploads';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$ext = pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION);
$fileName = uniqid('video_', true) . ($ext !== '' ? '.' . $ext : '');
$destPath = $uploadDir . '/' . $fileName;

if (!move_uploaded_file($_FILES['video']['tmp_name'], $destPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'アップロードに失敗しました']);
    exit;
}

$stmt = $pdo->prepare(
    'INSERT INTO videos (user_id, title, description, file_name) VALUES (:user_id, :title, :description, :file_name)'
);
$stmt->execute([
    'user_id' => $currentUserId,
    'title' => $title,
    'description' => $description,
    'file_name' => $fileName,
]);

http_response_code(201);
echo json_encode(['id' => $pdo->lastInsertId()]);
