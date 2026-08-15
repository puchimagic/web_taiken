<?php
require __DIR__ . '/../src/db.php';
$pdo = get_db(); // 初回アクセス時にDB/テーブルを自動作成

session_start();
$loginUsername = $_SESSION['username'] ?? null;

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

// ホーム上部の「ニッチな旅の入口」用に、テーマごとの代表スポット（最新1件＋件数）を取得する
$nicheThemes = [
    ['tag' => '聖地巡礼', 'label' => '聖地巡礼', 'icon' => '🎬'],
    ['tag' => '鉄道', 'label' => '鉄オタの旅', 'icon' => '🚃'],
    ['tag' => '産業遺産', 'label' => '産業遺産', 'icon' => '🏭'],
    ['tag' => '軍事遺構', 'label' => '軍事遺構', 'icon' => '🪖'],
    ['tag' => '天文', 'label' => '天文・宇宙', 'icon' => '🌌'],
    ['tag' => '団地', 'label' => '団地・ニュータウン', 'icon' => '🏘️'],
    ['tag' => '珍スポット', 'label' => '珍スポット', 'icon' => '❓'],
];
$nicheStmt = $pdo->prepare(
    'SELECT spots.id, spots.title, spots.file_name
     FROM spots
     JOIN spot_tags ON spot_tags.spot_id = spots.id
     WHERE spot_tags.tag = :tag
     ORDER BY spots.id DESC
     LIMIT 1'
);
$nicheCountStmt = $pdo->prepare('SELECT COUNT(*) FROM spot_tags WHERE tag = :tag');
foreach ($nicheThemes as &$theme) {
    $nicheStmt->execute(['tag' => $theme['tag']]);
    $rep = $nicheStmt->fetch(PDO::FETCH_ASSOC);
    $nicheCountStmt->execute(['tag' => $theme['tag']]);
    $theme['count'] = (int)$nicheCountStmt->fetchColumn();
    $theme['spot_id'] = $rep['id'] ?? null;
    $theme['file_name'] = $rep['file_name'] ?? null;
}
unset($theme);
$nicheThemes = array_values(array_filter($nicheThemes, fn($t) => $t['spot_id'] !== null));

// 各スポットのタグ一覧をまとめて取得
require __DIR__ . '/partials/spot_tags.php';
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
      <?php if ($keyword === '' && $selectedTag === '' && !empty($nicheThemes)): ?>
        <div class="niche-intro">
          <p class="niche-intro-title">ニッチな旅の入口</p>
          <p class="niche-intro-lead">普段は旅行に行かない人でも、好きなジャンルならきっと気になる。そんなスポットを集めました。</p>
          <div class="niche-theme-list">
            <?php foreach ($nicheThemes as $theme): ?>
              <a class="niche-theme-card" href="index.php?tag=<?= urlencode($theme['tag']) ?>">
                <div class="niche-theme-thumb">
                  <img src="uploads/<?= htmlspecialchars($theme['file_name'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($theme['label'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
                </div>
                <div class="niche-theme-label">
                  <span class="niche-theme-icon"><?= $theme['icon'] ?></span>
                  <?= htmlspecialchars($theme['label'], ENT_QUOTES, 'UTF-8') ?>
                  <span class="niche-theme-count"><?= $theme['count'] ?>件</span>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

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
            <?php require __DIR__ . '/partials/spot_card.php'; ?>
          <?php endforeach; ?>
        <?php endif; ?>
      </ul>
    </div>
  </div>

  <script src="index.js"></script>
</body>
</html>
