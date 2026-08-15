<?php
require __DIR__ . '/../src/db.php';
$pdo = get_db(); // 初回アクセス時にDB/テーブル/テストユーザーを自動作成

session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

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
<title>ログイン - 旅行共有サイト</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="container">
    <div class="login-hero">
      <a href="index.php" class="brand"><span class="dot"></span>旅行共有サイト</a>
      <p class="tagline">知る人ぞ知る旅行スポットを見て、投稿しよう</p>
    </div>

    <div class="login-box">
      <div class="tabs">
        <button type="button" class="tab-btn active" data-tab="login">ログイン</button>
        <button type="button" class="tab-btn" data-tab="register">新規登録</button>
      </div>

      <form id="login-form" class="auth-form">
        <input type="text" id="login-username" placeholder="ユーザー名" required>
        <input type="password" id="login-password" placeholder="パスワード" required>
        <button type="submit">ログイン</button>
        <p id="login-error" class="auth-error"></p>
      </form>

      <form id="register-form" class="auth-form hidden">
        <input type="text" id="register-username" placeholder="ユーザー名" required>
        <input type="password" id="register-password" placeholder="パスワード" required>
        <input type="email" id="register-email" placeholder="メールアドレス（例：taro@example.com）">
        <input type="tel" id="register-phone" placeholder="電話番号（例：09012345678）">

        <div class="location-row">
          <input type="text" id="register-postal-code" placeholder="郵便番号（例：1000001）" maxlength="8" inputmode="numeric" style="flex:1; min-width:160px;">
          <button type="button" id="register-postal-lookup-btn" class="btn-ghost">住所を検索</button>
        </div>
        <p id="register-postal-lookup-status" class="geo-status"></p>

        <select id="register-prefecture">
          <option value="">都道府県を選択</option>
          <?php foreach ($prefectures as $pref): ?>
            <option value="<?= htmlspecialchars($pref, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($pref, ENT_QUOTES, 'UTF-8') ?></option>
          <?php endforeach; ?>
        </select>
        <input type="text" id="register-city" placeholder="市区町村・町域（例：千代田区千代田）">
        <input type="text" id="register-address-line" placeholder="番地・建物名（例：1-1 〇〇マンション101）">

        <button type="submit">新規登録してログイン</button>
        <p id="register-error" class="auth-error"></p>
      </form>

      <p class="test-account-hint">
        テスト用アカウント: <code>test / test</code>
      </p>
    </div>
  </div>

  <script src="login.js"></script>
</body>
</html>
