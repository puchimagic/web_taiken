<?php
/**
 * スポット1件分のカードを描画する部分テンプレート。
 * 呼び出し側で $spot（id, title, file_name, username を含む連想配列）と
 * $spotTags（spot_id => タグ配列）を用意してから include する。
 */
?>
<li class="video-card">
  <a href="show.php?id=<?= (int)$spot['id'] ?>">
    <div class="video-thumb">
      <img class="thumb-img" src="uploads/<?= htmlspecialchars($spot['file_name'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($spot['title'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
    </div>
  </a>
  <div class="video-info">
    <a href="user.php?name=<?= urlencode($spot['username']) ?>" class="video-avatar-link">
      <div class="video-avatar"><?= mb_substr(htmlspecialchars($spot['username'], ENT_QUOTES, 'UTF-8'), 0, 1) ?></div>
    </a>
    <div>
      <a href="show.php?id=<?= (int)$spot['id'] ?>" class="video-title-link">
        <div class="video-title"><?= htmlspecialchars($spot['title'], ENT_QUOTES, 'UTF-8') ?></div>
      </a>
      <div class="video-meta">
        <a href="user.php?name=<?= urlencode($spot['username']) ?>" class="author"><?= htmlspecialchars($spot['username'], ENT_QUOTES, 'UTF-8') ?></a>
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
</li>
