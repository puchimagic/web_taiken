<div class="sidebar">
  <div class="side-group">
    <a class="side-link<?= ($active ?? '') === 'home' ? ' active' : '' ?>" href="index.php">
      <span class="side-icon">🏠</span>ホーム
    </a>
    <a class="side-link" href="index.php">
      <span class="side-icon">⭐</span>おすすめ（デモ）
    </a>
  </div>
  <div class="side-group">
    <div class="side-group-title">マイページ</div>
    <a class="side-link" href="index.php">
      <span class="side-icon">🕒</span>閲覧履歴（デモ）
    </a>
    <a class="side-link" href="upload.php">
      <span class="side-icon">📍</span>投稿したスポット
    </a>
  </div>
  <div class="side-group">
    <div class="side-group-title">タグから探す</div>
    <a class="side-link" href="index.php?tag=隠れ家"><span class="side-icon">🏚️</span>隠れ家</a>
    <a class="side-link" href="index.php?tag=絶景"><span class="side-icon">🌄</span>絶景</a>
    <a class="side-link" href="index.php?tag=グルメ"><span class="side-icon">🍜</span>グルメ</a>
  </div>
</div>
