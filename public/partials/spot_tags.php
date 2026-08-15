<?php
/**
 * $spots（id を含む配列）からタグ一覧を一括取得し、$spotTags（spot_id => タグ配列）を作る。
 * 呼び出し側で $pdo と $spots を用意してから include する。
 */
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
