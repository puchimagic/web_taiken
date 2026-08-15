<div class="topbar">
  <button type="button" id="sidebar-toggle-btn" class="sidebar-toggle-btn" aria-label="メニューを開く">☰</button>
  <a href="index.php" class="brand"><span class="dot"></span>キミの旅</a>
  <div class="topbar-spacer"></div>
  <div class="user-bar">
    <?php if (!empty($loginUsername)): ?>
      <a href="account.php" class="account-link"><?= htmlspecialchars($loginUsername, ENT_QUOTES, 'UTF-8') ?> さん</a>
      <a href="upload.php"><button type="button">＋ 投稿する</button></a>
      <button type="button" id="logout-btn" class="btn-ghost">ログアウト</button>
    <?php else: ?>
      <a href="login.php"><button type="button" class="btn-ghost">ログイン</button></a>
    <?php endif; ?>
  </div>
</div>
<script src="partials/topbar.js" defer></script>
