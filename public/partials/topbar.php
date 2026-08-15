<div class="topbar">
  <a href="index.php" class="brand"><span class="dot"></span>旅行共有サイト</a>
  <form class="search-bar" action="index.php" method="get">
    <input type="text" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="スポットを検索（タイトル・住所・タグ）">
    <button type="submit">検索</button>
  </form>
  <div class="topbar-spacer"></div>
  <div class="user-bar">
    <a href="upload.php"><button type="button">＋ 投稿する</button></a>
    <span><?= htmlspecialchars($loginUsername ?? '', ENT_QUOTES, 'UTF-8') ?> さん</span>
    <button type="button" id="logout-btn" class="btn-ghost">ログアウト</button>
  </div>
</div>
