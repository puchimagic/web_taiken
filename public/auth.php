<?php

require __DIR__ . '/../src/db.php';

session_start();
header('Content-Type: application/json; charset=utf-8');

$pdo = get_db();
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? '';

if ($method === 'POST' && $action === 'register') {
    $username = trim((string)($input['username'] ?? ''));
    $password = (string)($input['password'] ?? '');

    if ($username === '' || $password === '') {
        http_response_code(400);
        echo json_encode(['error' => 'ユーザー名とパスワードは必須です']);
        exit;
    }

    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username');
    $stmt->execute(['username' => $username]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['error' => 'そのユーザー名は既に使われています']);
        exit;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO users (username, password_hash) VALUES (:username, :password_hash)'
    );
    $stmt->execute([
        'username' => $username,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);

    $_SESSION['user_id'] = (int)$pdo->lastInsertId();
    $_SESSION['username'] = $username;

    echo json_encode(['ok' => true, 'username' => $username]);
    exit;
}

if ($method === 'POST' && $action === 'login') {
    $username = trim((string)($input['username'] ?? ''));
    $password = (string)($input['password'] ?? '');

    $stmt = $pdo->prepare('SELECT id, username, password_hash FROM users WHERE username = :username');
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        http_response_code(401);
        echo json_encode(['error' => 'ユーザー名またはパスワードが違います']);
        exit;
    }

    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['username'] = $user['username'];

    echo json_encode(['ok' => true, 'username' => $user['username']]);
    exit;
}

if ($method === 'POST' && $action === 'logout') {
    $_SESSION = [];
    session_destroy();
    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => '不正なリクエストです']);
