<?php
require __DIR__ . '/../src/db.php';
$pdo = get_db();

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$loginUsername = $_SESSION['username'];
$currentUserId = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare(
    'SELECT username, postal_code, prefecture, city, address_line, phone FROM users WHERE id = :id'
);
$stmt->execute(['id' => $currentUserId]);
$profile = $stmt->fetch(PDO::FETCH_ASSOC);

// 郵便番号データの都道府県一覧を、登場順（≒北海道→沖縄の一般的な並び）で取得
$prefectures = $pdo->query(
    'SELECT prefecture FROM postal_codes GROUP BY prefecture ORDER BY MIN(id)'
)->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>アカウント設定 - 旅行共有サイト</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
  <?php include __DIR__ . '/partials/topbar.php'; ?>

  <div class="page-shell">
    <?php $active = ''; include __DIR__ . '/partials/sidebar.php'; ?>

    <div class="main-area narrow">
      <p class="page-title">アカウント設定</p>

      <form id="account-form" class="upload-form">
        <label>ユーザー名</label>
        <input type="text" id="account-username" name="username" value="<?= htmlspecialchars($loginUsername, ENT_QUOTES, 'UTF-8') ?>" maxlength="50" required>

        <label>新しいパスワード（変更する場合のみ入力）</label>
        <input type="password" id="account-new-password" name="new_password" placeholder="変更しない場合は空欄のまま">

        <label>電話番号</label>
        <input type="tel" id="account-phone" name="phone" value="<?= htmlspecialchars($profile['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="09012345678" maxlength="20">

        <label>郵便番号</label>
        <div class="location-row">
          <input type="text" id="account-postal-code" name="postal_code" value="<?= htmlspecialchars($profile['postal_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="1000001" maxlength="8" inputmode="numeric" style="flex:1; min-width:160px;">
          <!-- 体験用：この文字を分かりやすい表示に書き換えてみよう（例：「住所を検索」） -->
          <button type="button" id="postal-lookup-btn" class="btn-ghost">ボタン</button>
        </div>
        <p id="postal-lookup-status" class="geo-status"></p>

        <label>都道府県</label>
        <select id="account-prefecture" name="prefecture">
          <option value="">選択してください</option>
          <?php foreach ($prefectures as $pref): ?>
            <option value="<?= htmlspecialchars($pref, ENT_QUOTES, 'UTF-8') ?>" <?= ($profile['prefecture'] ?? '') === $pref ? 'selected' : '' ?>>
              <?= htmlspecialchars($pref, ENT_QUOTES, 'UTF-8') ?>
            </option>
          <?php endforeach; ?>
        </select>

        <label>市区町村・町域</label>
        <input type="text" id="account-city" name="city" value="<?= htmlspecialchars($profile['city'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="例：千代田区千代田" maxlength="100">

        <label>番地・建物名</label>
        <input type="text" id="account-address-line" name="address_line" value="<?= htmlspecialchars($profile['address_line'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="例：1-1 〇〇マンション101" maxlength="200">

        <p id="account-error" class="auth-error"></p>
        <p id="account-success" class="auth-success"></p>
        <button type="submit">保存する</button>
      </form>
    </div>
  </div>

  <script src="account.js"></script>
</body>
</html>
