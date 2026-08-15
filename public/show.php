<?php
require __DIR__ . '/../src/db.php';
$pdo = get_db();

session_start();
$loginUsername = $_SESSION['username'] ?? null;

$spotId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT spots.id, spots.title, spots.description, spots.file_name, spots.address,
            spots.latitude, spots.longitude, spots.created_at, users.username
     FROM spots
     JOIN users ON users.id = spots.user_id
     WHERE spots.id = :id'
);
$stmt->execute(['id' => $spotId]);
$spot = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$spot) {
    header('Location: index.php');
    exit;
}

if (isset($_SESSION['user_id'])) {
    $currentUserId = (int)$_SESSION['user_id'];
    $pdo->prepare(
        'INSERT INTO view_history (user_id, spot_id) VALUES (:user_id, :spot_id)
         ON CONFLICT(user_id, spot_id) DO UPDATE SET viewed_at = datetime(\'now\', \'localtime\')'
    )->execute(['user_id' => $currentUserId, 'spot_id' => $spotId]);
}

$reactionCounts = ['want_to_go' => 0, 'helpful' => 0];
$countStmt = $pdo->prepare('SELECT type, COUNT(*) AS cnt FROM reactions WHERE spot_id = :spot_id GROUP BY type');
$countStmt->execute(['spot_id' => $spotId]);
foreach ($countStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $reactionCounts[$row['type']] = (int)$row['cnt'];
}

$myReactions = ['want_to_go' => false, 'helpful' => false];
if (isset($_SESSION['user_id'])) {
    $myStmt = $pdo->prepare('SELECT type FROM reactions WHERE spot_id = :spot_id AND user_id = :user_id');
    $myStmt->execute(['spot_id' => $spotId, 'user_id' => (int)$_SESSION['user_id']]);
    foreach ($myStmt->fetchAll(PDO::FETCH_COLUMN) as $type) {
        $myReactions[$type] = true;
    }
}

$stmt = $pdo->prepare('SELECT tag FROM spot_tags WHERE spot_id = :spot_id ORDER BY id ASC');
$stmt->execute(['spot_id' => $spotId]);
$tags = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->prepare(
    'SELECT comments.id, comments.message, comments.created_at, users.username
     FROM comments
     JOIN users ON users.id = comments.user_id
     WHERE comments.spot_id = :spot_id
     ORDER BY comments.created_at ASC, comments.id ASC'
);
$stmt->execute(['spot_id' => $spotId]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare(
    'SELECT spots.id, spots.title, spots.file_name, users.username
     FROM spots
     JOIN users ON users.id = spots.user_id
     WHERE spots.id != :id
     ORDER BY spots.id DESC
     LIMIT 8'
);
$stmt->execute(['id' => $spotId]);
$relatedSpots = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($spot['title'], ENT_QUOTES, 'UTF-8') ?> - 旅行共有サイト</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
  <?php include __DIR__ . '/partials/topbar.php'; ?>

  <div class="page-shell">
    <?php $active = ''; include __DIR__ . '/partials/sidebar.php'; ?>

    <div class="main-area">
      <div class="watch-layout">
        <div>
          <img class="spot-image" src="uploads/<?= htmlspecialchars($spot['file_name'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($spot['title'], ENT_QUOTES, 'UTF-8') ?>">

          <div class="video-title-large"><?= htmlspecialchars($spot['title'], ENT_QUOTES, 'UTF-8') ?></div>

          <?php if (!empty($tags)): ?>
            <div class="card-tags" style="margin-bottom:14px;">
              <?php foreach ($tags as $tag): ?>
                <a class="tag-badge" href="index.php?tag=<?= urlencode($tag) ?>">#<?= htmlspecialchars($tag, ENT_QUOTES, 'UTF-8') ?></a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <div class="video-actions-row">
            <a class="channel-block" href="user.php?name=<?= urlencode($spot['username']) ?>">
              <div class="channel-avatar"><?= mb_substr(htmlspecialchars($spot['username'], ENT_QUOTES, 'UTF-8'), 0, 1) ?></div>
              <div>
                <div class="channel-name"><?= htmlspecialchars($spot['username'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="channel-sub">投稿者</div>
              </div>
            </a>

            <div class="action-buttons">
              <div class="pill-group">
                <button type="button" class="pill-btn<?= $myReactions['want_to_go'] ? ' active' : '' ?>" id="reaction-want_to_go" data-spot-id="<?= (int)$spot['id'] ?>" data-type="want_to_go" <?= $loginUsername === null ? 'disabled' : '' ?>>
                  🚩 行ってみたい <span class="reaction-count"><?= $reactionCounts['want_to_go'] ?></span>
                </button>
                <button type="button" class="pill-btn<?= $myReactions['helpful'] ? ' active' : '' ?>" id="reaction-helpful" data-spot-id="<?= (int)$spot['id'] ?>" data-type="helpful" <?= $loginUsername === null ? 'disabled' : '' ?>>
                  👏 参考になった <span class="reaction-count"><?= $reactionCounts['helpful'] ?></span>
                </button>
              </div>
              <button type="button" class="icon-btn">↗ 共有</button>
            </div>
          </div>

          <div class="video-meta" style="margin-bottom:10px;">
            <span><?= htmlspecialchars($spot['created_at'], ENT_QUOTES, 'UTF-8') ?> に投稿</span>
          </div>
          <p class="video-description"><?= nl2br(htmlspecialchars($spot['description'], ENT_QUOTES, 'UTF-8')) ?></p>

          <?php if ($spot['address'] !== ''): ?>
            <div class="location-info">
              <span class="location-icon">📍</span>
              <span><?= htmlspecialchars($spot['address'], ENT_QUOTES, 'UTF-8') ?></span>
              <?php if ($spot['latitude'] !== null && $spot['longitude'] !== null): ?>
                <span class="location-coords">（緯度 <?= htmlspecialchars((string)$spot['latitude'], ENT_QUOTES, 'UTF-8') ?> / 経度 <?= htmlspecialchars((string)$spot['longitude'], ENT_QUOTES, 'UTF-8') ?>）</span>
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <?php if ($spot['latitude'] !== null && $spot['longitude'] !== null): ?>
            <?php $latLon = $spot['latitude'] . ',' . $spot['longitude']; ?>
            <div class="spot-map">
              <iframe
                class="spot-map-frame"
                src="https://www.google.com/maps?q=<?= urlencode($latLon) ?>&z=15&hl=ja&output=embed"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                allowfullscreen>
              </iframe>
              <a class="spot-map-link" href="https://www.google.com/maps?q=<?= urlencode($latLon) ?>&hl=ja" target="_blank" rel="noopener">大きな地図で見る ↗</a>
            </div>
          <?php endif; ?>
        </div>

        <div>
          <div class="comment-panel">
            <div class="comment-panel-title">
              コメント
              <span class="count"><?= count($comments) ?></span>
            </div>

            <?php if ($loginUsername !== null): ?>
              <div class="comment-form-row">
                <div class="comment-avatar"><?= mb_substr(htmlspecialchars($loginUsername, ENT_QUOTES, 'UTF-8'), 0, 1) ?></div>
                <form id="comment-form" class="comment-form" data-spot-id="<?= (int)$spot['id'] ?>">
                  <textarea id="comment-message" placeholder="コメントを追加..." maxlength="300" required></textarea>
                  <p id="comment-error" class="auth-error"></p>
                  <button type="submit">コメントする</button>
                </form>
              </div>
            <?php else: ?>
              <p class="empty"><a href="login.php">ログイン</a>するとコメントできます</p>
            <?php endif; ?>

            <ul id="comment-list" class="comment-list">
              <?php if (empty($comments)): ?>
                <li class="empty">まだコメントがありません</li>
              <?php else: ?>
                <?php foreach ($comments as $comment): ?>
                  <li class="comment">
                    <div class="comment-avatar"><?= mb_substr(htmlspecialchars($comment['username'], ENT_QUOTES, 'UTF-8'), 0, 1) ?></div>
                    <div class="comment-body">
                      <div class="comment-meta">
                        <?= htmlspecialchars($comment['username'], ENT_QUOTES, 'UTF-8') ?>さん
                        <span class="comment-time"><?= htmlspecialchars($comment['created_at'], ENT_QUOTES, 'UTF-8') ?></span>
                      </div>
                      <div class="comment-message"><?= htmlspecialchars($comment['message'], ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                  </li>
                <?php endforeach; ?>
              <?php endif; ?>
            </ul>

            <?php if (!empty($relatedSpots)): ?>
              <div class="related-title">他のスポット</div>
              <ul class="related-list">
                <?php foreach ($relatedSpots as $rv): ?>
                  <li class="related-item">
                    <a href="show.php?id=<?= (int)$rv['id'] ?>" style="display:flex; gap:10px; text-decoration:none; color:inherit;">
                      <div class="related-thumb">
                        <img class="thumb-img" src="uploads/<?= htmlspecialchars($rv['file_name'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($rv['title'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
                      </div>
                      <div class="related-info">
                        <div class="video-title"><?= htmlspecialchars($rv['title'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div class="video-meta"><span class="author"><?= htmlspecialchars($rv['username'], ENT_QUOTES, 'UTF-8') ?></span></div>
                      </div>
                    </a>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="show.js"></script>
</body>
</html>
