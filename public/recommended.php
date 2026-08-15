<?php
require __DIR__ . '/../src/db.php';
$pdo = get_db();

session_start();
$loginUsername = $_SESSION['username'] ?? null;

// コメント数が多い順（同数なら新しい投稿順）をおすすめとする
$stmt = $pdo->query(
    'SELECT spots.id, spots.title, spots.file_name, spots.address, spots.created_at, users.username,
            COUNT(comments.id) AS comment_count
     FROM spots
     JOIN users ON users.id = spots.user_id
     LEFT JOIN comments ON comments.spot_id = spots.id
     GROUP BY spots.id
     ORDER BY comment_count DESC, spots.id DESC'
);
$spots = $stmt->fetchAll(PDO::FETCH_ASSOC);

require __DIR__ . '/partials/spot_tags.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>おすすめ - 旅行共有サイト</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
  <?php include __DIR__ . '/partials/topbar.php'; ?>

  <div class="page-shell">
    <?php $active = 'recommended'; include __DIR__ . '/partials/sidebar.php'; ?>

    <div class="main-area">
      <p class="page-title">おすすめのスポット</p>

      <ul id="spot-list" class="video-list">
        <?php if (empty($spots)): ?>
          <li class="empty">まだスポットが投稿されていません</li>
        <?php else: ?>
          <?php foreach ($spots as $spot): ?>
            <?php require __DIR__ . '/partials/spot_card.php'; ?>
          <?php endforeach; ?>
        <?php endif; ?>
      </ul>
    </div>
  </div>

</body>
</html>
