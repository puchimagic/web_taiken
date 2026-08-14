<?php
require __DIR__ . '/../src/db.php';
$pdo = get_db(); // 初回アクセス時にDB/テーブルを自動作成

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$loginUsername = $_SESSION['username'];

$stmt = $pdo->query(
    'SELECT videos.id, videos.title, videos.created_at, users.username
     FROM videos
     JOIN users ON users.id = videos.user_id
     ORDER BY videos.id DESC'
);
$videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>動画共有サイト</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="container">
    <div class="header-bar">
      <h1>動画共有サイト</h1>
      <div class="user-bar">
        <span><?= htmlspecialchars($loginUsername, ENT_QUOTES, 'UTF-8') ?> さんでログイン中</span>
        <a href="upload.php"><button type="button">投稿する</button></a>
        <button type="button" id="logout-btn">ログアウト</button>
      </div>
    </div>

    <ul id="video-list" class="video-list">
      <?php if (empty($videos)): ?>
        <li class="empty">まだ動画が投稿されていません</li>
      <?php else: ?>
        <?php foreach ($videos as $video): ?>
          <li class="video-card">
            <a href="show.php?id=<?= (int)$video['id'] ?>">
              <div class="video-thumb">▶</div>
              <div class="video-info">
                <div class="video-title"><?= htmlspecialchars($video['title'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="video-meta">
                  <span><?= htmlspecialchars($video['username'], ENT_QUOTES, 'UTF-8') ?></span>
                  <span><?= htmlspecialchars($video['created_at'], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
              </div>
            </a>
          </li>
        <?php endforeach; ?>
      <?php endif; ?>
    </ul>
  </div>

  <script src="index.js"></script>
</body>
</html>
