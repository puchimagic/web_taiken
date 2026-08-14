<?php
require __DIR__ . '/../src/db.php';
$pdo = get_db();

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$videoId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT videos.id, videos.title, videos.description, videos.file_name, videos.created_at, users.username
     FROM videos
     JOIN users ON users.id = videos.user_id
     WHERE videos.id = :id'
);
$stmt->execute(['id' => $videoId]);
$video = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$video) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare(
    'SELECT comments.id, comments.message, comments.created_at, users.username
     FROM comments
     JOIN users ON users.id = comments.user_id
     WHERE comments.video_id = :video_id
     ORDER BY comments.id ASC'
);
$stmt->execute(['video_id' => $videoId]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($video['title'], ENT_QUOTES, 'UTF-8') ?> - 動画共有サイト</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="container">
    <div class="header-bar">
      <h1>動画を見る</h1>
      <a href="index.php"><button type="button">一覧にもどる</button></a>
    </div>

    <video class="video-player" controls src="uploads/<?= htmlspecialchars($video['file_name'], ENT_QUOTES, 'UTF-8') ?>"></video>

    <div class="video-detail">
      <div class="video-title-large"><?= htmlspecialchars($video['title'], ENT_QUOTES, 'UTF-8') ?></div>
      <div class="video-meta">
        <span><?= htmlspecialchars($video['username'], ENT_QUOTES, 'UTF-8') ?></span>
        <span><?= htmlspecialchars($video['created_at'], ENT_QUOTES, 'UTF-8') ?></span>
      </div>
      <p class="video-description"><?= nl2br(htmlspecialchars($video['description'], ENT_QUOTES, 'UTF-8')) ?></p>
    </div>

    <form id="comment-form" class="comment-form" data-video-id="<?= (int)$video['id'] ?>">
      <textarea id="comment-message" placeholder="コメントを入力" maxlength="300" required></textarea>
      <p id="comment-error" class="auth-error"></p>
      <button type="submit">
        <!-- 投稿ボタンにメールアイコンを付ける場合はここにアイコンを追加 -->
        コメントする
      </button>
    </form>

    <ul id="comment-list" class="comment-list">
      <?php if (empty($comments)): ?>
        <li class="empty">まだコメントがありません</li>
      <?php else: ?>
        <?php foreach ($comments as $comment): ?>
          <li class="comment">
            <div class="comment-meta">
              [<?= htmlspecialchars($comment['username'], ENT_QUOTES, 'UTF-8') ?>さん]
            </div>
            <div class="comment-message"><?= htmlspecialchars($comment['message'], ENT_QUOTES, 'UTF-8') ?></div>
          </li>
        <?php endforeach; ?>
      <?php endif; ?>
    </ul>
  </div>

  <script src="show.js"></script>
</body>
</html>
