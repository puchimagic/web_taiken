<?php
require __DIR__ . '/../src/db.php';
$pdo = get_db(); // 初回アクセス時にDB/テーブルを自動作成

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$loginUsername = $_SESSION['username'];

$selectedTag = trim((string)($_GET['tag'] ?? ''));
$keyword = trim((string)($_GET['q'] ?? ''));

if ($keyword !== '') {
    $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $keyword) . '%';
    $sql = "SELECT DISTINCT spots.id, spots.title, spots.file_name, spots.address, spots.created_at, users.username
            FROM spots
            JOIN users ON users.id = spots.user_id
            LEFT JOIN spot_tags ON spot_tags.spot_id = spots.id
            WHERE (spots.title LIKE :like ESCAPE '\\'
                OR spots.description LIKE :like2 ESCAPE '\\'
                OR spots.address LIKE :like3 ESCAPE '\\'
                OR spot_tags.tag LIKE :like4 ESCAPE '\\')";
    $params = ['like' => $like, 'like2' => $like, 'like3' => $like, 'like4' => $like];
    if ($selectedTag !== '') {
        $sql .= ' AND EXISTS (SELECT 1 FROM spot_tags st2 WHERE st2.spot_id = spots.id AND st2.tag = :tag)';
        $params['tag'] = $selectedTag;
    }
    $sql .= ' ORDER BY spots.id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
} elseif ($selectedTag !== '') {
    $stmt = $pdo->prepare(
        'SELECT spots.id, spots.title, spots.file_name, spots.address, spots.created_at, users.username
         FROM spots
         JOIN users ON users.id = spots.user_id
         JOIN spot_tags ON spot_tags.spot_id = spots.id
         WHERE spot_tags.tag = :tag
         ORDER BY spots.id DESC'
    );
    $stmt->execute(['tag' => $selectedTag]);
} else {
    $stmt = $pdo->query(
        'SELECT spots.id, spots.title, spots.file_name, spots.address, spots.created_at, users.username
         FROM spots
         JOIN users ON users.id = spots.user_id
         ORDER BY spots.id DESC'
    );
}
$spots = $stmt->fetchAll(PDO::FETCH_ASSOC);

// タグごとのスポット件数を集計し、よく使われているタグをチップとして表示する
$tagStmt = $pdo->query(
    'SELECT tag, COUNT(*) AS cnt FROM spot_tags GROUP BY tag ORDER BY cnt DESC, tag ASC LIMIT 12'
);
$popularTags = $tagStmt->fetchAll(PDO::FETCH_ASSOC);

// 各スポットのタグ一覧をまとめて取得
$spotTags = [];
if (!empty($spots)) {
    $ids = array_column($spots, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $tagsStmt = $pdo->prepare("SELECT spot_id, tag FROM spot_tags WHERE spot_id IN ($placeholders)");
    $tagsStmt->execute($ids);
    foreach ($tagsStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $spotTags[$row['spot_id']][] = $row['tag'];
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>旅行共有サイト</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
  <?php include __DIR__ . '/partials/topbar.php'; ?>

  <div class="page-shell">
    <?php $active = 'home'; include __DIR__ . '/partials/sidebar.php'; ?>

    <div class="main-area">
      <?php $tagQuerySuffix = $keyword !== '' ? '&q=' . urlencode($keyword) : ''; ?>
      <div class="filter-chips">
        <a href="index.php<?= $keyword !== '' ? '?q=' . urlencode($keyword) : '' ?>" class="chip<?= $selectedTag === '' ? ' active' : '' ?>">すべて</a>
        <?php foreach ($popularTags as $t): ?>
          <a href="index.php?tag=<?= urlencode($t['tag']) . $tagQuerySuffix ?>" class="chip<?= $selectedTag === $t['tag'] ? ' active' : '' ?>">
            #<?= htmlspecialchars($t['tag'], ENT_QUOTES, 'UTF-8') ?>
          </a>
        <?php endforeach; ?>
      </div>

      <?php if ($keyword !== '' && $selectedTag !== ''): ?>
        <p class="page-title">「<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?>」× 「#<?= htmlspecialchars($selectedTag, ENT_QUOTES, 'UTF-8') ?>」の検索結果（<?= count($spots) ?>件）</p>
      <?php elseif ($keyword !== ''): ?>
        <p class="page-title">「<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?>」の検索結果（<?= count($spots) ?>件）</p>
      <?php elseif ($selectedTag !== ''): ?>
        <p class="page-title">「#<?= htmlspecialchars($selectedTag, ENT_QUOTES, 'UTF-8') ?>」のスポット</p>
      <?php endif; ?>

      <ul id="spot-list" class="video-list">
        <?php if (empty($spots)): ?>
          <li class="empty"><?= $keyword !== '' || $selectedTag !== '' ? '該当するスポットが見つかりませんでした' : 'まだスポットが投稿されていません' ?></li>
        <?php else: ?>
          <?php foreach ($spots as $spot): ?>
            <li class="video-card">
              <a href="show.php?id=<?= (int)$spot['id'] ?>">
                <div class="video-thumb">
                  <img class="thumb-img" src="uploads/<?= htmlspecialchars($spot['file_name'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($spot['title'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
                </div>
                <div class="video-info">
                  <div class="video-avatar"><?= mb_substr(htmlspecialchars($spot['username'], ENT_QUOTES, 'UTF-8'), 0, 1) ?></div>
                  <div>
                    <div class="video-title"><?= htmlspecialchars($spot['title'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="video-meta">
                      <span class="author"><?= htmlspecialchars($spot['username'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <?php if (!empty($spotTags[$spot['id']])): ?>
                      <div class="card-tags">
                        <?php foreach ($spotTags[$spot['id']] as $tag): ?>
                          <span class="tag-badge">#<?= htmlspecialchars($tag, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              </a>
            </li>
          <?php endforeach; ?>
        <?php endif; ?>
      </ul>
    </div>
  </div>

  <script src="index.js"></script>
</body>
</html>
