<?php
require __DIR__ . '/../src/db.php';
get_db();

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$loginUsername = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>スポットを投稿 - キミの旅</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
  <?php include __DIR__ . '/partials/topbar.php'; ?>

  <div class="page-shell">
    <?php $active = ''; include __DIR__ . '/partials/sidebar.php'; ?>

    <div class="main-area narrow">
      <p class="page-title">スポットを投稿</p>

      <form id="upload-form" class="upload-form">
        <label>タイトル</label>
        <input type="text" id="title" name="title" placeholder="例：地元の人しか知らない絶景カフェ" maxlength="100" required>

        <label>説明</label>
        <textarea id="description" name="description" placeholder="どんな場所か、行き方のコツなど（任意）"></textarea>

        <label>タグ（スペース区切りで自由入力）</label>
        <input type="text" id="tags" name="tags" placeholder="例：隠れ家 夜景 無人駅">

        <label>場所</label>
        <div class="location-box">
          <div class="location-row">
            <button type="button" id="geo-btn" class="btn-accent-outline">📍 現在地を取得</button>
            <span id="geo-status" class="geo-status"></span>
          </div>
          <input type="text" id="address" name="address" placeholder="住所（現在地取得で自動入力、または手入力）">
          <input type="hidden" id="latitude" name="latitude">
          <input type="hidden" id="longitude" name="longitude">
        </div>

        <label>画像ファイル</label>
        <div class="file-drop">
          <input type="file" id="image-file" name="image" accept="image/*" required>
        </div>
        <p id="upload-error" class="auth-error"></p>
        <button type="submit">投稿する</button>
      </form>
    </div>
  </div>

  <script src="upload.js"></script>
</body>
</html>
