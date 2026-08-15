<form class="search-bar" action="index.php" method="get">
  <div class="search-input-wrap">
    <input type="text" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="スポットを検索（タイトル・住所・タグ）">
    <?php if (trim((string)($_GET['q'] ?? '')) !== ''): ?>
      <a href="index.php" class="search-clear-btn" aria-label="検索条件をクリア" title="検索条件をクリア">×</a>
    <?php endif; ?>
  </div>
  <button type="submit" aria-label="検索">🔍</button>
</form>
