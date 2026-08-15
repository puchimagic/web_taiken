<div class="sidebar">
  <div class="side-group">
    <a class="side-link<?= ($active ?? '') === 'home' ? ' active' : '' ?>" href="index.php">
      <span class="side-icon">🏠</span>ホーム
    </a>
    <a class="side-link<?= ($active ?? '') === 'recommended' ? ' active' : '' ?>" href="recommended.php">
      <span class="side-icon">⭐</span>おすすめ
    </a>
  </div>
  <?php if (!empty($loginUsername)): ?>
    <div class="side-group">
      <div class="side-group-title">マイページ</div>
      <a class="side-link<?= ($active ?? '') === 'history' ? ' active' : '' ?>" href="history.php">
        <span class="side-icon">🕒</span>閲覧履歴
      </a>
      <a class="side-link<?= ($active ?? '') === 'mine' ? ' active' : '' ?>" href="mine.php">
        <span class="side-icon">📍</span>投稿したスポット
      </a>
    </div>
  <?php endif; ?>
</div>
