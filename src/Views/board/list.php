<?php
declare(strict_types=1);

use App\Lib\Dates;
use App\Lib\I18n;
use App\Lib\Url;
use App\Lib\View;

/** @var array $posts */
/** @var string|null $category */
/** @var array $categories */
/** @var bool $hasMore */
/** @var int|null $nextBeforeId */
/** @var string $search */
/** @var string|null $searchError */

$catQuery = $search !== '' ? '&q=' . urlencode($search) : '';
?>
<h2><?= View::e(I18n::t('board_title')) ?></h2>

<div>
<a href="<?= View::e(Url::to('board') . ($catQuery !== '' ? '?' . ltrim($catQuery, '&') : '')) ?>"<?= $category === null ? ' class="active-link"' : '' ?>><?= View::e(I18n::t('board_category_all')) ?></a>
<?php foreach ($categories as $cat): ?>
&nbsp;|&nbsp;<a href="<?= View::e(Url::to('board') . '?cat=' . urlencode($cat) . $catQuery) ?>"<?= $category === $cat ? ' class="active-link"' : '' ?>><?= View::e(I18n::t('category_' . $cat)) ?></a>
<?php endforeach; ?>
</div>

<form method="get" action="<?= View::e(Url::to('board')) ?>">
<?php if ($category !== null): ?>
<input type="hidden" name="cat" value="<?= View::e($category) ?>">
<?php endif; ?>
<p>
<label for="board_search"><?= View::e(I18n::t('label_board_search')) ?></label>
<input type="text" id="board_search" name="q" maxlength="100" value="<?= View::e($search) ?>" placeholder="<?= View::e(I18n::t('board_search_hint')) ?>">
<button type="submit" class="btn"><?= View::e(I18n::t('btn_search')) ?></button>
<?php if ($search !== ''): ?>
<a class="btn" href="<?= View::e(Url::to('board') . ($category !== null ? '?cat=' . urlencode($category) : '')) ?>"><?= View::e(I18n::t('btn_cancel')) ?></a>
<?php endif; ?>
</p>
</form>

<?php if ($searchError): ?>
<div class="errors"><p><?= View::e($searchError) ?></p></div>
<?php endif; ?>

<div class="toolbar">
<a class="btn" href="<?= View::e(Url::to('board/write')) ?>"><?= View::e(I18n::t('btn_write')) ?></a>
</div>

<?php if (!$posts): ?>
<p><?= View::e(I18n::t('board_empty')) ?></p>
<?php else: ?>
<table>
<thead>
<tr>
<th><?= View::e(I18n::t('col_category')) ?></th>
<th><?= View::e(I18n::t('col_title')) ?></th>
<th><?= View::e(I18n::t('col_nickname')) ?></th>
<th><?= View::e(I18n::t('col_date')) ?></th>
</tr>
</thead>
<tbody>
<?php foreach ($posts as $post): ?>
<?php
$nickname = $post['user_nickname'] ?? $post['guest_nickname'] ?? '?';
$isGuest = $post['user_id'] === null;
?>
<tr>
<td><span class="cat"><?= View::e(I18n::t('category_' . $post['category'])) ?></span></td>
<td><a href="<?= View::e(Url::to('board/view/' . $post['id'])) ?>"><?= View::e($post['title']) ?></a></td>
<td><?= View::e($nickname) ?><?= $isGuest ? ' <small>' . View::e(I18n::t('guest_marker')) . '</small>' : '' ?></td>
<td><?= View::e(Dates::display((string) $post['created_at'])) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

<?php if ($hasMore): ?>
<div class="pager">
<a href="<?= View::e(Url::to('board') . '?' . http_build_query(array_filter(['cat' => $category, 'q' => $search !== '' ? $search : null, 'before' => $nextBeforeId]))) ?>"><?= View::e(I18n::t('btn_more')) ?></a>
</div>
<?php endif; ?>
