<?php

// シードデータ投入時に、投稿日時が全件同時刻になって不自然にならないよう、
// 過去 $maxDaysAgo 日以内のランダムな日時を 'Y-m-d H:i:s' 形式で返す。
function random_past_datetime(int $maxDaysAgo): string
{
    $secondsAgo = random_int(0, $maxDaysAgo * 24 * 60 * 60);
    return date('Y-m-d H:i:s', time() - $secondsAgo);
}

// $from（'Y-m-d H:i:s'）から $to（同形式、または 'now'）までの間のランダムな日時を返す。
function random_datetime_between(string $from, string $to): string
{
    $fromTs = strtotime($from);
    $toTs = $to === 'now' ? time() : strtotime($to);
    if ($fromTs >= $toTs) {
        return date('Y-m-d H:i:s', $toTs);
    }
    return date('Y-m-d H:i:s', random_int($fromTs, $toTs));
}

function get_db(): PDO
{
    $dbPath = __DIR__ . '/../db/board.sqlite';
    $isNewDatabase = !file_exists($dbPath);

    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = ON');

    // テーブル作成・初期シードは初回アクセス時（DBファイル未作成時）のみ実行する。
    // 毎リクエストで CREATE TABLE IF NOT EXISTS や password_hash（bcrypt）を
    // 繰り返すと無視できない負荷になるため、既存DBがあればスキップする。
    if ($isNewDatabase) {
        init_schema($pdo);
        import_postal_codes_csv($pdo);
        seed_test_users($pdo);
        seed_sample_authors($pdo);
        seed_spots_from_json($pdo);
        seed_comments_and_reactions($pdo);
    } else {
        migrate_schema($pdo);
    }

    // public/uploads/ は .gitignore 対象のため git 管理外で、DBファイル
    // （board.sqlite）だけをcloneした場合は $isNewDatabase が false になり
    // 上のシード処理が丸ごとスキップされる。そのため画像コピーはDBの
    // 新規作成有無と切り離し、毎回（ただしuploads内が空の時だけ）実行する。
    sync_seed_images();

    return $pdo;
}

// 画像/ ディレクトリから public/uploads/ へシード画像をコピーする。
// public/uploads/ に何かファイルがあれば既にコピー済みとみなして即returnする
// ため、通常のリクエストではglob呼び出し1回だけの軽量な判定で済む。
function sync_seed_images(): void
{
    $uploadDir = __DIR__ . '/../public/uploads';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    if (glob($uploadDir . '/*') !== []) {
        return;
    }

    $sourceImageDir = __DIR__ . '/../画像';
    foreach (['seed_spots.json', 'seed_spots_new.json'] as $jsonFile) {
        $jsonPath = __DIR__ . '/../db/' . $jsonFile;
        if (!file_exists($jsonPath)) {
            continue;
        }
        $spots = json_decode(file_get_contents($jsonPath), true);
        if (!is_array($spots)) {
            continue;
        }
        foreach ($spots as $spot) {
            $fileName = $spot['file'] ?? '';
            if ($fileName === '') {
                continue;
            }
            $sourcePath = $sourceImageDir . '/' . $fileName;
            $destPath = $uploadDir . '/' . $fileName;
            if (file_exists($sourcePath) && !file_exists($destPath)) {
                copy($sourcePath, $destPath);
            }
        }
    }
}

// 既存DBに後から追加したカラム・テーブルを補う軽量マイグレーション。
// 対象が既にあれば何もしない（起動のたびに何度呼んでも安全）。
function migrate_schema(PDO $pdo): void
{
    $columns = $pdo->query('PRAGMA table_info(users)')->fetchAll(PDO::FETCH_COLUMN, 1);
    $profileColumns = [
        'postal_code' => "ALTER TABLE users ADD COLUMN postal_code TEXT NOT NULL DEFAULT ''",
        'prefecture' => "ALTER TABLE users ADD COLUMN prefecture TEXT NOT NULL DEFAULT ''",
        'city' => "ALTER TABLE users ADD COLUMN city TEXT NOT NULL DEFAULT ''",
        'address_line' => "ALTER TABLE users ADD COLUMN address_line TEXT NOT NULL DEFAULT ''",
        'phone' => "ALTER TABLE users ADD COLUMN phone TEXT NOT NULL DEFAULT ''",
        'email' => "ALTER TABLE users ADD COLUMN email TEXT NOT NULL DEFAULT ''",
    ];
    foreach ($profileColumns as $column => $sql) {
        if (!in_array($column, $columns, true)) {
            $pdo->exec($sql);
        }
    }

    create_postal_codes_table($pdo);
    import_postal_codes_csv($pdo);
    create_reactions_table($pdo);
    backfill_missing_profiles($pdo);
}

// 既存DBに元々いたユーザー（プロフィール未入力のまま作られたアカウント）に、
// postal_codesと整合する住所・電話番号・メールアドレスを一括で補う。
// email・postal_code等がまだ空文字のユーザーだけが対象なので、既に入力済みの
// プロフィールを上書きすることはない。
function backfill_missing_profiles(PDO $pdo): void
{
    $users = $pdo->query(
        "SELECT id FROM users WHERE email = '' AND postal_code = '' ORDER BY id ASC"
    )->fetchAll(PDO::FETCH_COLUMN);
    if (empty($users)) {
        return;
    }

    $updateStmt = $pdo->prepare(
        'UPDATE users SET email = :email, phone = :phone, postal_code = :postal_code,
            prefecture = :prefecture, city = :city, address_line = :address_line
         WHERE id = :id'
    );

    foreach ($users as $seedIndex => $userId) {
        $profile = build_seed_profile($pdo, (int)$seedIndex);
        $updateStmt->execute(array_merge($profile, ['id' => $userId]));
    }
}

function create_reactions_table(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS reactions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            spot_id INTEGER NOT NULL,
            type TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
            UNIQUE(user_id, spot_id, type),
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (spot_id) REFERENCES spots(id)
        )"
    );
}

// 日本郵便の全国一括CSV（UTF-8版、db/import/utf_ken_all.csv）をpostal_codesテーブルに取り込む。
// CSVが無い場合（開発機でインポート手順を踏んでいない等）は何もせず抜ける。
// postal_codesに既にデータがある場合も何もしない（毎リクエストでの再インポートを避けるため）。
function import_postal_codes_csv(PDO $pdo): void
{
    $existingCount = (int)$pdo->query('SELECT COUNT(*) FROM postal_codes')->fetchColumn();
    if ($existingCount > 0) {
        return;
    }

    // 通常はdb/import/直下。ただし体験授業当日のWebプロセットアップ.batは、
    // db/import/（開発用ファイル）を丸ごと削除する前にCSVだけdb/直下へ退避する
    // ため、そちらも探す。
    $candidatePaths = [
        __DIR__ . '/../db/import/utf_ken_all.csv',
        __DIR__ . '/../db/utf_ken_all.csv',
    ];
    $csvPath = null;
    foreach ($candidatePaths as $candidate) {
        if (file_exists($candidate)) {
            $csvPath = $candidate;
            break;
        }
    }
    if ($csvPath === null) {
        return;
    }

    $fh = fopen($csvPath, 'r');
    if ($fh === false) {
        return;
    }

    $insert = $pdo->prepare(
        'INSERT INTO postal_codes (postal_code, prefecture, city, town) VALUES (:postal_code, :prefecture, :city, :town)'
    );

    $pdo->beginTransaction();
    // 列: 0=団体コード 1=旧郵便番号 2=郵便番号 3-5=カナ 6=都道府県 7=市区町村 8=町域
    while (($row = fgetcsv($fh, 0, ',', '"', '\\')) !== false) {
        $postalCode = $row[2] ?? '';
        $prefecture = $row[6] ?? '';
        $city = $row[7] ?? '';
        $town = $row[8] ?? '';

        if ($postalCode === '' || $prefecture === '') {
            continue;
        }

        // 「以下に掲載がない場合」は町域なしとして扱う
        if ($town === '以下に掲載がない場合') {
            $town = '';
        }

        $insert->execute([
            'postal_code' => $postalCode,
            'prefecture' => $prefecture,
            'city' => $city,
            'town' => $town,
        ]);
    }
    $pdo->commit();
    fclose($fh);
}

// 郵便番号（7桁）から都道府県・市区町村・町域を検索する。
// postal_codesテーブル（124,513件）をpostal_code列のインデックスで検索するため、一瞬で結果が返る。
// 該当する郵便番号がなければnullを返す。
function find_postal_address(PDO $pdo, string $postalCode): ?array
{
    $stmt = $pdo->prepare('SELECT prefecture, city, town FROM postal_codes WHERE postal_code = :code LIMIT 1');
    $stmt->execute(['code' => $postalCode]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result !== false ? $result : null;
}

function create_postal_codes_table(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS postal_codes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            postal_code TEXT NOT NULL,
            prefecture TEXT NOT NULL,
            city TEXT NOT NULL,
            town TEXT NOT NULL
        )"
    );
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_postal_codes_code ON postal_codes(postal_code)');
}

function init_schema(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            postal_code TEXT NOT NULL DEFAULT '',
            prefecture TEXT NOT NULL DEFAULT '',
            city TEXT NOT NULL DEFAULT '',
            address_line TEXT NOT NULL DEFAULT '',
            phone TEXT NOT NULL DEFAULT '',
            email TEXT NOT NULL DEFAULT '',
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

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS view_history (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            spot_id INTEGER NOT NULL,
            viewed_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),
            UNIQUE(user_id, spot_id),
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (spot_id) REFERENCES spots(id)
        )"
    );

    create_reactions_table($pdo);
    create_postal_codes_table($pdo);
}

// 番地・建物名のダミー候補（postal_codesの町域名だけでは番地が無いため別途組み合わせる）
const SEED_PROFILE_ADDRESS_LINES = [
    '1-2-3', '2-15-1', '3-4', '5-6-7 〇〇マンション101', '1-1',
    '4-8-2', '2-3', '6-1-9', '1-14-5 〇〇ビル3F', '3-7',
];

// postal_codesテーブル（郵便番号データ）から実在する郵便番号・都道府県・市区町村の組み合わせを
// $seedIndex 件目としてOFFSET取得し、住所として矛盾がないダミープロフィールを組み立てる。
// postal_codesが未インポート（0件）の場合はダミーの固定値にフォールバックする。
function build_seed_profile(PDO $pdo, int $seedIndex): array
{
    static $totalRows = null;
    if ($totalRows === null) {
        $totalRows = (int)$pdo->query('SELECT COUNT(*) FROM postal_codes')->fetchColumn();
    }

    $phone = sprintf('090%08d', 10000000 + ($seedIndex * 7919) % 90000000);
    $email = sprintf('user%02d@example.com', $seedIndex + 1);
    $addressLine = SEED_PROFILE_ADDRESS_LINES[$seedIndex % count(SEED_PROFILE_ADDRESS_LINES)];

    if ($totalRows === 0) {
        return [
            'postal_code' => '',
            'prefecture' => '',
            'city' => '',
            'address_line' => '',
            'phone' => $phone,
            'email' => $email,
        ];
    }

    $offset = ($seedIndex * 4177) % $totalRows;
    $stmt = $pdo->prepare('SELECT postal_code, prefecture, city, town FROM postal_codes LIMIT 1 OFFSET :offset');
    $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return [
        'postal_code' => $row['postal_code'],
        'prefecture' => $row['prefecture'],
        'city' => $row['city'] . $row['town'],
        'address_line' => $addressLine,
        'phone' => $phone,
        'email' => $email,
    ];
}

function seed_test_users(PDO $pdo): void
{
    $testUsers = ['test' => 'test'];

    $insertUser = $pdo->prepare(
        'INSERT OR IGNORE INTO users (username, password_hash, postal_code, prefecture, city, address_line, phone, email)
         VALUES (:username, :password_hash, :postal_code, :prefecture, :city, :address_line, :phone, :email)'
    );

    // test ユーザーは大阪情報コンピュータ専門学校（OIC）の住所で固定する
    $seedIndex = 0;
    foreach ($testUsers as $username => $password) {
        $profile = build_seed_profile($pdo, $seedIndex++);
        if ($username === 'test') {
            $profile = array_merge($profile, [
                'postal_code' => '5430001',
                'prefecture' => '大阪府',
                'city' => '大阪市天王寺区',
                'address_line' => '上本町6-8-4',
            ]);
        }
        $insertUser->execute(array_merge($profile, [
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ]));
    }
}

// 投稿者に多様性を持たせるためのダミーアカウント（ログイン用ではなく表示用）
const SAMPLE_AUTHOR_USERNAMES = [
    'たびねこ', 'ふらっと旅人', 'やまびこ', 'そらいろ日和',
    'みちくさ部', 'ひとり旅のこじか', 'しおかぜ散歩',
    '乗り鉄まさむね', '秘境駅ハンター', '聖地巡礼部', 'カメラは鞄の中',
    '廃線探検隊', '工場夜景派', '団地マニアックス', '天体観測員',
    '城好き侍', '地質オタク', '路地裏散歩者', '廃墟系ライター',
    '博物館めぐり', '産業遺産部',
];

function seed_sample_authors(PDO $pdo): void
{
    $insertUser = $pdo->prepare(
        'INSERT OR IGNORE INTO users (username, password_hash, postal_code, prefecture, city, address_line, phone, email)
         VALUES (:username, :password_hash, :postal_code, :prefecture, :city, :address_line, :phone, :email)'
    );

    // seedIndex は test ユーザー（0番）の続きの 1 から割り当て、住所・電話番号・メールが重複しないようにする
    $seedIndex = 1;
    foreach (SAMPLE_AUTHOR_USERNAMES as $username) {
        $profile = build_seed_profile($pdo, $seedIndex++);
        $insertUser->execute(array_merge($profile, [
            'username' => $username,
            'password_hash' => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
        ]));
    }
}

// db/seed_spots.json と db/seed_spots_new.json から初期スポットデータを投入する（初回起動時のみ）。
// 画像のコピーは sync_seed_images() が別途担当する。
// 全体の1件目は test ユーザー、残りはサンプル投稿者にラウンドロビンで割り当てる。
function seed_spots_from_json(PDO $pdo): void
{
    $jsonFiles = ['seed_spots.json', 'seed_spots_new.json'];
    $spots = [];
    foreach ($jsonFiles as $jsonFile) {
        $jsonPath = __DIR__ . '/../db/' . $jsonFile;
        if (!file_exists($jsonPath)) {
            continue;
        }
        $decoded = json_decode(file_get_contents($jsonPath), true);
        if (is_array($decoded)) {
            $spots = array_merge($spots, $decoded);
        }
    }
    if (empty($spots)) {
        return;
    }

    $testUser = $pdo->query("SELECT id FROM users WHERE username = 'test'")->fetch(PDO::FETCH_ASSOC);
    if (!$testUser) {
        return;
    }
    $testUserId = (int)$testUser['id'];

    $placeholders = implode(',', array_fill(0, count(SAMPLE_AUTHOR_USERNAMES), '?'));
    $authorStmt = $pdo->prepare("SELECT id FROM users WHERE username IN ($placeholders) ORDER BY id ASC");
    $authorStmt->execute(SAMPLE_AUTHOR_USERNAMES);
    $authorIds = $authorStmt->fetchAll(PDO::FETCH_COLUMN);

    $insertSpot = $pdo->prepare(
        'INSERT INTO spots (user_id, title, description, file_name, address, latitude, longitude, created_at)
         VALUES (:user_id, :title, :description, :file_name, :address, :latitude, :longitude, :created_at)'
    );
    $insertTag = $pdo->prepare('INSERT INTO spot_tags (spot_id, tag) VALUES (:spot_id, :tag)');

    foreach ($spots as $index => $spot) {
        $fileName = $spot['file'] ?? '';
        if ($fileName === '') {
            continue;
        }

        // 1件目は test ユーザー、残りはサンプル投稿者にラウンドロビンで割り当てる
        if ($index === 0 || empty($authorIds)) {
            $authorId = $testUserId;
        } else {
            $authorId = $authorIds[($index - 1) % count($authorIds)];
        }

        // 投稿日時は過去180日以内でランダムに散らし、全件同時刻にならないようにする
        $spotCreatedAt = random_past_datetime(180);

        $insertSpot->execute([
            'user_id' => $authorId,
            'title' => $spot['title'] ?? '',
            'description' => $spot['description'] ?? '',
            'file_name' => $fileName,
            'address' => $spot['address'] ?? '',
            'latitude' => $spot['lat'] ?? null,
            'longitude' => $spot['lon'] ?? null,
            'created_at' => $spotCreatedAt,
        ]);
        $spotId = (int)$pdo->lastInsertId();

        $tagsInput = trim((string)($spot['tags'] ?? ''));
        if ($tagsInput !== '') {
            $tags = preg_split('/[,\s　]+/u', $tagsInput, -1, PREG_SPLIT_NO_EMPTY);
            foreach (array_unique($tags) as $tag) {
                $insertTag->execute(['spot_id' => $spotId, 'tag' => $tag]);
            }
        }
    }
}

// db内の全ユーザー・全スポットに対して、自然な分布でコメントとリアクションを投入する（初回起動時のみ）。
// コメント文面は db/spot_comments.json（file_name をキーにした、投稿内容に即した文面集）から取得する。
function seed_comments_and_reactions(PDO $pdo): void
{
    $userIds = $pdo->query('SELECT id FROM users')->fetchAll(PDO::FETCH_COLUMN);
    $spots = $pdo->query('SELECT id, user_id, file_name, created_at FROM spots')->fetchAll(PDO::FETCH_ASSOC);
    if (empty($userIds) || empty($spots)) {
        return;
    }

    $commentsPath = __DIR__ . '/../db/spot_comments.json';
    $spotComments = file_exists($commentsPath)
        ? json_decode(file_get_contents($commentsPath), true)
        : [];
    if (!is_array($spotComments)) {
        $spotComments = [];
    }

    $insertComment = $pdo->prepare(
        'INSERT INTO comments (spot_id, user_id, message, created_at) VALUES (:spot_id, :user_id, :message, :created_at)'
    );
    $insertReaction = $pdo->prepare(
        'INSERT OR IGNORE INTO reactions (user_id, spot_id, type) VALUES (:user_id, :spot_id, :type)'
    );

    foreach ($spots as $spot) {
        $spotId = (int)$spot['id'];
        $authorId = (int)$spot['user_id'];

        // そのスポット固有のコメント候補（投稿内容に即した文面）から4〜7件を投入する。
        // 候補文面より多い件数を出す場合は、別のユーザーが同じ文面をコメントすることを許容する
        // （実際のSNSでも似た感想が複数つくのは自然なため）。
        // コメント日時は、そのスポットの投稿日時以降〜現在までの間でランダムに散らす。
        $candidates = $spotComments[$spot['file_name']] ?? [];
        if (!empty($candidates)) {
            $commenters = array_values(array_filter($userIds, fn ($id) => (int)$id !== $authorId));
            shuffle($commenters);
            $commentCount = random_int(4, min(7, count($commenters)));
            for ($i = 0; $i < $commentCount; $i++) {
                $insertComment->execute([
                    'spot_id' => $spotId,
                    'user_id' => $commenters[$i],
                    'message' => $candidates[array_rand($candidates)],
                    'created_at' => random_datetime_between($spot['created_at'], 'now'),
                ]);
            }
        }

        // 「行ってみたい」「参考になった」を、投稿者以外のユーザーからランダムな人数に付与
        foreach (['want_to_go', 'helpful'] as $type) {
            $reactors = array_values(array_filter($userIds, fn ($id) => (int)$id !== $authorId));
            shuffle($reactors);
            $reactionCount = random_int(0, count($reactors));
            for ($i = 0; $i < $reactionCount; $i++) {
                $insertReaction->execute([
                    'user_id' => $reactors[$i],
                    'spot_id' => $spotId,
                    'type' => $type,
                ]);
            }
        }
    }
}
