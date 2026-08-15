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
    $email = trim((string)($input['email'] ?? ''));
    $phone = trim((string)($input['phone'] ?? ''));
    $postalCode = preg_replace('/[^0-9]/', '', (string)($input['postal_code'] ?? ''));
    $prefecture = trim((string)($input['prefecture'] ?? ''));
    $city = trim((string)($input['city'] ?? ''));
    $addressLine = trim((string)($input['address_line'] ?? ''));

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
        'INSERT INTO users (username, password_hash, email, phone, postal_code, prefecture, city, address_line)
         VALUES (:username, :password_hash, :email, :phone, :postal_code, :prefecture, :city, :address_line)'
    );
    $stmt->execute([
        'username' => $username,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        'email' => $email,
        'phone' => $phone,
        'postal_code' => $postalCode,
        'prefecture' => $prefecture,
        'city' => $city,
        'address_line' => $addressLine,
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

if ($method === 'POST' && $action === 'update_profile') {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'ログインが必要です']);
        exit;
    }

    $currentUserId = (int)$_SESSION['user_id'];
    $newUsername = trim((string)($input['username'] ?? ''));
    $newPassword = (string)($input['new_password'] ?? '');
    $email = trim((string)($input['email'] ?? ''));
    $phone = trim((string)($input['phone'] ?? ''));
    $postalCode = preg_replace('/[^0-9]/', '', (string)($input['postal_code'] ?? ''));
    $prefecture = trim((string)($input['prefecture'] ?? ''));
    $city = trim((string)($input['city'] ?? ''));
    $addressLine = trim((string)($input['address_line'] ?? ''));

    if ($newUsername === '') {
        http_response_code(400);
        echo json_encode(['error' => 'ユーザー名は必須です']);
        exit;
    }

    $stmt = $pdo->prepare('SELECT id, username, password_hash FROM users WHERE id = :id');
    $stmt->execute(['id' => $currentUserId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        http_response_code(401);
        echo json_encode(['error' => 'ログインが必要です']);
        exit;
    }

    if ($newUsername !== $user['username']) {
        $dupStmt = $pdo->prepare('SELECT id FROM users WHERE username = :username AND id != :id');
        $dupStmt->execute(['username' => $newUsername, 'id' => $currentUserId]);
        if ($dupStmt->fetch()) {
            http_response_code(409);
            echo json_encode(['error' => 'そのユーザー名は既に使われています']);
            exit;
        }
    }

    $params = [
        'username' => $newUsername,
        'email' => $email,
        'phone' => $phone,
        'postal_code' => $postalCode,
        'prefecture' => $prefecture,
        'city' => $city,
        'address_line' => $addressLine,
        'id' => $currentUserId,
    ];

    if ($newPassword !== '') {
        $params['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateStmt = $pdo->prepare(
            'UPDATE users SET username = :username, password_hash = :password_hash,
                email = :email, phone = :phone, postal_code = :postal_code, prefecture = :prefecture,
                city = :city, address_line = :address_line
             WHERE id = :id'
        );
    } else {
        $updateStmt = $pdo->prepare(
            'UPDATE users SET username = :username,
                email = :email, phone = :phone, postal_code = :postal_code, prefecture = :prefecture,
                city = :city, address_line = :address_line
             WHERE id = :id'
        );
    }
    $updateStmt->execute($params);

    $_SESSION['username'] = $newUsername;

    echo json_encode(['ok' => true, 'username' => $newUsername]);
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
