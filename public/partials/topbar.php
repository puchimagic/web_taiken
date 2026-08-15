<div class="topbar">
  <a href="index.php" class="brand"><span class="dot"></span>旅行共有サイト</a>
  <form class="search-bar" action="index.php" method="get">
    <div class="search-input-wrap">
      <input type="text" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="スポットを検索（タイトル・住所・タグ）">
      <?php if (trim((string)($_GET['q'] ?? '')) !== ''): ?>
        <a href="index.php" class="search-clear-btn" aria-label="検索条件をクリア" title="検索条件をクリア">×</a>
      <?php endif; ?>
    </div>
    <button type="submit">検索</button>
  </form>
  <div class="topbar-spacer"></div>
  <div class="user-bar">
    <?php if (!empty($loginUsername)): ?>
      <a href="upload.php"><button type="button">＋ 投稿する</button></a>
      <a href="account.php" class="account-link"><?= htmlspecialchars($loginUsername, ENT_QUOTES, 'UTF-8') ?> さん</a>
      <button type="button" id="logout-btn" class="btn-ghost">ログアウト</button>
    <?php else: ?>
      <a href="login.php"><button type="button" class="btn-ghost">ログイン</button></a>
    <?php endif; ?>
  </div>
</div>
