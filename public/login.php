<?php
require __DIR__ . '/../src/db.php';
get_db(); // 初回アクセス時にDB/テーブル/テストユーザーを自動作成

session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ログイン - 動画共有サイト</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="container">
    <h1>動画共有サイト</h1>

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
        <button type="submit">新規登録してログイン</button>
        <p id="register-error" class="auth-error"></p>
      </form>

      <p class="test-account-hint">
        テスト用アカウント: <code>taro / taro123</code>、<code>hanako / hanako123</code>
      </p>
    </div>
  </div>

  <script src="login.js"></script>
</body>
</html>
