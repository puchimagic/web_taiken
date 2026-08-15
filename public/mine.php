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
    'SELECT spots.id, spots.title, spots.file_name, spots.address, spots.created_at, users.username
     FROM spots
     JOIN users ON users.id = spots.user_id
     WHERE spots.user_id = :user_id
     ORDER BY spots.id DESC'
);
$stmt->execute(['user_id' => $currentUserId]);
$spots = $stmt->fetchAll(PDO::FETCH_ASSOC);

require __DIR__ . '/partials/spot_tags.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>投稿したスポット - 旅行共有サイト</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
  <?php include __DIR__ . '/partials/topbar.php'; ?>

  <div class="page-shell">
    <?php $active = 'mine'; include __DIR__ . '/partials/sidebar.php'; ?>

    <div class="main-area">
      <p class="page-title">投稿したスポット（<?= count($spots) ?>件）</p>

      <ul id="spot-list" class="video-list">
        <?php if (empty($spots)): ?>
          <li class="empty">まだスポットを投稿していません</li>
        <?php else: ?>
          <?php foreach ($spots as $spot): ?>
            <?php require __DIR__ . '/partials/spot_card.php'; ?>
          <?php endforeach; ?>
        <?php endif; ?>
      </ul>
    </div>
  </div>

  <script src="index.js"></script>
</body>
</html>
