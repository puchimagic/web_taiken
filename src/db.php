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
        "CREATE TABLE IF NOT EXISTS spots (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            title TEXT NOT NULL,
            description TEXT NOT NULL DEFAULT '',
            file_name TEXT NOT NULL,
            address TEXT NOT NULL DEFAULT '',
            latitude REAL,
            longitude REAL,
            created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS spot_tags (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            spot_id INTEGER NOT NULL,
            tag TEXT NOT NULL,
            FOREIGN KEY (spot_id) REFERENCES spots(id)
        )"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS comments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            spot_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            message TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
            FOREIGN KEY (spot_id) REFERENCES spots(id),
            FOREIGN KEY (user_id) REFERENCES users(id)
        )"
    );

    seed_test_users($pdo);

    return $pdo;
}

function seed_test_users(PDO $pdo): void
{
    $testUsers = ['test' => 'test'];

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
