<?php
require __DIR__ . '/../src/db.php';
$pdo = get_db();

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$loginUsername = $_SESSION['username'];

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

// 緯度経度からタイル座標(z/x/y)とタイル内オフセットを求める（地図表示用）
function latlon_to_tile(float $lat, float $lon, int $zoom): array
{
    $latRad = deg2rad($lat);
    $n = 2 ** $zoom;
    $xFloat = ($lon + 180) / 360 * $n;
    $yFloat = (1 - log(tan($latRad) + 1 / cos($latRad)) / M_PI) / 2 * $n;
    return [
        'x' => (int)floor($xFloat),
        'y' => (int)floor($yFloat),
        'offsetXPercent' => ($xFloat - floor($xFloat)) * 100,
        'offsetYPercent' => ($yFloat - floor($yFloat)) * 100,
        'zoom' => $zoom,
    ];
}

$stmt = $pdo->prepare('SELECT tag FROM spot_tags WHERE spot_id = :spot_id ORDER BY id ASC');
$stmt->execute(['spot_id' => $spotId]);
$tags = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $pdo->prepare(
    'SELECT comments.id, comments.message, comments.created_at, users.username
     FROM comments
     JOIN users ON users.id = comments.user_id
     WHERE comments.spot_id = :spot_id
     ORDER BY comments.id ASC'
);
$stmt->execute(['spot_id' => $spotId]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare(
    'SELECT spots.id, spots.title, users.username
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
            <div class="channel-block">
              <div class="channel-avatar"><?= mb_substr(htmlspecialchars($spot['username'], ENT_QUOTES, 'UTF-8'), 0, 1) ?></div>
              <div>
                <div class="channel-name"><?= htmlspecialchars($spot['username'], ENT_QUOTES, 'UTF-8') ?></div>
                <div class="channel-sub">投稿者</div>
              </div>
            </div>

            <div class="action-buttons">
              <div class="pill-group">
                <button type="button" class="pill-btn">🚩 行ってみたい</button>
                <button type="button" class="pill-btn">👏 参考になった</button>
              </div>
              <button type="button" class="icon-btn">↗ 共有</button>
            </div>
          </div>

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
            <?php $tile = latlon_to_tile((float)$spot['latitude'], (float)$spot['longitude'], 15); ?>
            <div class="spot-map">
              <div class="spot-map-grid">
                <?php for ($dy = -1; $dy <= 1; $dy++): ?>
                  <?php for ($dx = -1; $dx <= 1; $dx++): ?>
                    <img class="spot-map-tile" src="https://tile.openstreetmap.org/<?= $tile['zoom'] ?>/<?= $tile['x'] + $dx ?>/<?= $tile['y'] + $dy ?>.png" alt="" loading="lazy">
                  <?php endfor; ?>
                <?php endfor; ?>
              </div>
              <span class="spot-map-pin" style="left:calc((100% / 3) + (<?= $tile['offsetXPercent'] ?>% / 3)); top:calc((100% / 3) + (<?= $tile['offsetYPercent'] ?>% / 3));">📍</span>
              <a class="spot-map-link" href="https://www.openstreetmap.org/?mlat=<?= urlencode((string)$spot['latitude']) ?>&mlon=<?= urlencode((string)$spot['longitude']) ?>#map=<?= $tile['zoom'] ?>/<?= htmlspecialchars((string)$spot['latitude'], ENT_QUOTES, 'UTF-8') ?>/<?= htmlspecialchars((string)$spot['longitude'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">大きな地図で見る ↗</a>
            </div>
          <?php endif; ?>

          <div class="video-meta" style="margin-bottom:10px;">
            <span><?= htmlspecialchars($spot['created_at'], ENT_QUOTES, 'UTF-8') ?> に投稿</span>
          </div>
          <p class="video-description"><?= nl2br(htmlspecialchars($spot['description'], ENT_QUOTES, 'UTF-8')) ?></p>
        </div>

        <div>
          <div class="comment-panel">
            <div class="comment-panel-title">
              コメント
              <span class="count"><?= count($comments) ?></span>
            </div>

            <div class="comment-form-row">
              <div class="comment-avatar"><?= mb_substr(htmlspecialchars($loginUsername, ENT_QUOTES, 'UTF-8'), 0, 1) ?></div>
              <form id="comment-form" class="comment-form" data-spot-id="<?= (int)$spot['id'] ?>">
                <textarea id="comment-message" placeholder="コメントを追加..." maxlength="300" required></textarea>
                <p id="comment-error" class="auth-error"></p>
                <button type="submit">コメントする</button>
              </form>
            </div>

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
                      <div class="related-thumb">📍</div>
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
