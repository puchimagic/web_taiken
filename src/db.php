<?php

function get_db(): PDO
{
    $dbPath = __DIR__ . '/../db/board.sqlite';

    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = ON');

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
        )"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS videos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            title TEXT NOT NULL,
            description TEXT NOT NULL DEFAULT '',
            file_name TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS comments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            video_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            message TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
            FOREIGN KEY (video_id) REFERENCES videos(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )"
    );

    seed_test_users($pdo);

    return $pdo;
}

function seed_test_users(PDO $pdo): void
{
    $testUsers = ['taro' => 'taro123', 'hanako' => 'hanako123'];

    $insertUser = $pdo->prepare(
        'INSERT OR IGNORE INTO users (username, password_hash) VALUES (:username, :password_hash)'
    );

    foreach ($testUsers as $username => $password) {
        $insertUser->execute([
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ]);
    }
}
