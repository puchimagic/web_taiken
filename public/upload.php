<?php
require __DIR__ . '/../src/db.php';
get_db();

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>動画を投稿 - 動画共有サイト</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="container">
    <div class="header-bar">
      <h1>動画を投稿</h1>
      <a href="index.php"><button type="button">一覧にもどる</button></a>
    </div>

    <form id="upload-form" class="upload-form">
      <input type="text" id="title" name="title" placeholder="タイトル" maxlength="100" required>
      <textarea id="description" name="description" placeholder="説明（任意）" maxlength="500"></textarea>
      <input type="file" id="video-file" name="video" accept="video/*" required>
      <p id="upload-error" class="auth-error"></p>
      <button type="submit">投稿する</button>
    </form>
  </div>

  <script src="upload.js"></script>
</body>
</html>
