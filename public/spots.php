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
$address = trim((string)($_POST['address'] ?? ''));
$latitude = $_POST['latitude'] ?? null;
$longitude = $_POST['longitude'] ?? null;
$tagsInput = trim((string)($_POST['tags'] ?? ''));

// 体験用：/* と */ を外すと、タイトルが空欄のまま投稿できなくなります
/*
if ($title === '') {
    http_response_code(400);
    echo json_encode(['error' => 'タイトルは必須です']);
    exit;
}
*/

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => '画像ファイルを選択してください']);
    exit;
}

$uploadDir = __DIR__ . '/uploads';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
$fileName = uniqid('spot_', true) . ($ext !== '' ? '.' . $ext : '');
$destPath = $uploadDir . '/' . $fileName;

if (!move_uploaded_file($_FILES['image']['tmp_name'], $destPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'アップロードに失敗しました']);
    exit;
}

$stmt = $pdo->prepare(
    'INSERT INTO spots (user_id, title, description, file_name, address, latitude, longitude)
     VALUES (:user_id, :title, :description, :file_name, :address, :latitude, :longitude)'
);
$stmt->execute([
    'user_id' => $currentUserId,
    'title' => $title,
    'description' => $description,
    'file_name' => $fileName,
    'address' => $address,
    'latitude' => $latitude !== null && $latitude !== '' ? (float)$latitude : null,
    'longitude' => $longitude !== null && $longitude !== '' ? (float)$longitude : null,
]);
$spotId = (int)$pdo->lastInsertId();

if ($tagsInput !== '') {
    $tags = preg_split('/[,\s　]+/u', $tagsInput, -1, PREG_SPLIT_NO_EMPTY);
    $insertTag = $pdo->prepare('INSERT INTO spot_tags (spot_id, tag) VALUES (:spot_id, :tag)');
    foreach (array_unique($tags) as $tag) {
        $insertTag->execute(['spot_id' => $spotId, 'tag' => $tag]);
    }
}

http_response_code(201);
echo json_encode(['id' => $spotId]);
