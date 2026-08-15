<?php
require __DIR__ . '/../src/db.php';
$pdo = get_db();

session_start();
$loginUsername = $_SESSION['username'] ?? null;

$targetUsername = trim((string)($_GET['name'] ?? ''));

$userStmt = $pdo->prepare('SELECT id, username FROM users WHERE username = :username');
$userStmt->execute(['username' => $targetUsername]);
$targetUser = $userStmt->fetch(PDO::FETCH_ASSOC);

if (!$targetUser) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare(
    'SELECT spots.id, spots.title, spots.file_name, spots.address, spots.created_at, users.username
     FROM spots
     JOIN users ON users.id = spots.user_id
     WHERE spots.user_id = :user_id
     ORDER BY spots.id DESC'
);
$stmt->execute(['user_id' => $targetUser['id']]);
$spots = $stmt->fetchAll(PDO::FETCH_ASSOC);

require __DIR__ . '/partials/spot_tags.php';
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($targetUser['username'], ENT_QUOTES, 'UTF-8') ?> さんの投稿 - 旅行共有サイト</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
  <?php include __DIR__ . '/partials/topbar.php'; ?>

  <div class="page-shell">
    <?php $active = ''; include __DIR__ . '/partials/sidebar.php'; ?>

    <div class="main-area">
      <div class="channel-block" style="margin-bottom:16px;">
        <div class="channel-avatar"><?= mb_substr(htmlspecialchars($targetUser['username'], ENT_QUOTES, 'UTF-8'), 0, 1) ?></div>
        <div>
          <div class="channel-name"><?= htmlspecialchars($targetUser['username'], ENT_QUOTES, 'UTF-8') ?></div>
          <div class="channel-sub"><?= count($spots) ?>件の投稿</div>
        </div>
      </div>

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
