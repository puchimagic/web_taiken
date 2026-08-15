<?php
/**
 * 日本郵便の全国一括CSV（UTF-8版）を postal_codes テーブルに取り込むワンショットスクリプト。
 * 実行方法: php db/import/import_postal_codes.php
 *
 * データ出典: 日本郵便 郵便番号データダウンロード
 * https://www.post.japanpost.jp/zipcode/dl/utf-zip.html
 */

require __DIR__ . '/../../src/db.php';

$csvPath = __DIR__ . '/utf_ken_all.csv';
if (!file_exists($csvPath)) {
    fwrite(STDERR, "CSVファイルが見つかりません: $csvPath\n");
    exit(1);
}

$pdo = get_db();

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

$pdo->exec('DELETE FROM postal_codes');

$insert = $pdo->prepare(
    'INSERT INTO postal_codes (postal_code, prefecture, city, town) VALUES (:postal_code, :prefecture, :city, :town)'
);

$fh = fopen($csvPath, 'r');
if ($fh === false) {
    fwrite(STDERR, "CSVファイルを開けませんでした\n");
    exit(1);
}

$count = 0;
$pdo->beginTransaction();

while (($row = fgetcsv($fh, 0, ',', '"', '\\')) !== false) {
    // 列: 0=団体コード 1=旧郵便番号 2=郵便番号 3-5=カナ 6=都道府県 7=市区町村 8=町域
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
    $count++;
}

$pdo->commit();
fclose($fh);

echo "取り込み完了: {$count}件\n";
