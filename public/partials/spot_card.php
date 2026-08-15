<?php
/**
 * スポット1件分のカードを描画する部分テンプレート。
 * 呼び出し側で $spot（id, title, file_name, username を含む連想配列）と
 * $spotTags（spot_id => タグ配列）を用意してから include する。
 *
 * おすすめページなど、根拠を示したい場合は以下も渡せる（任意）。
 * - $rankBadges: 1位から順に表示する絵文字バッジの配列（例: ['🥇', '🥈', '🥉']）。
 *   $spotRank（1始まりの順位）がこの配列の範囲内のときだけバッジを表示する。
 * - $spotRank: このスポットの順位（1始まり）
 * - $spot['comment_count']: 渡されていれば「💬n件の口コミ」を表示する
 */
$rankBadge = (isset($rankBadges, $spotRank) && isset($rankBadges[$spotRank - 1])) ? $rankBadges[$spotRank - 1] : null;
?>
<li class="video-card">
  <a href="show.php?id=<?= (int)$spot['id'] ?>">
    <div class="video-thumb">
      <?php if ($rankBadge !== null): ?>
        <span class="rank-badge"><?= $rankBadge ?></span>
      <?php endif; ?>
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
        <?php if (isset($spot['created_at'])): ?>
          <span class="dot-sep"></span>
          <span class="post-date"><?= htmlspecialchars(substr($spot['created_at'], 0, 10), ENT_QUOTES, 'UTF-8') ?></span>
        <?php endif; ?>
        <?php if (isset($spot['comment_count'])): ?>
          <span class="comment-count-badge">💬<?= (int)$spot['comment_count'] ?>件の口コミ</span>
        <?php endif; ?>
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
