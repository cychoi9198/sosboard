<?php
declare(strict_types=1);

use App\Lib\Csrf;
use App\Lib\Dates;
use App\Lib\I18n;
use App\Lib\Url;
use App\Lib\View;

/** @var array $post */
/** @var bool $canDelete */
/** @var bool $requiresGuestPassword */

$nickname = $post['user_nickname'] ?? $post['guest_nickname'] ?? '?';
$isGuest = $post['user_id'] === null;
?>
<h2><?= View::e($post['title']) ?></h2>

<div class="meta">
<span class="cat"><?= View::e(I18n::t('category_' . $post['category'])) ?></span>
&nbsp;<?= View::e($nickname) ?><?= $isGuest ? ' <small>' . View::e(I18n::t('guest_marker')) . '</small>' : '' ?>
&nbsp;·&nbsp;<?= View::e(Dates::display((string) $post['created_at'])) ?>
</div>

<div class="postbody"><?= nl2br(View::e($post['body']), false) ?></div>

<div class="actions">
<a class="btn" href="<?= View::e(Url::to('board')) ?>"><?= View::e(I18n::t('view_back_to_list')) ?></a>

<?php if ($canDelete): ?>
<form method="post" action="<?= View::e(Url::to('board/delete/' . $post['id'])) ?>" class="delete-form">
<?= Csrf::field() ?>
<?php if ($requiresGuestPassword): ?>
<input type="password" name="guest_password" placeholder="<?= View::e(I18n::t('label_delete_guest_password')) ?>" required>
<?php endif; ?>
<button type="submit" class="btn"><?= View::e(I18n::t('btn_delete')) ?></button>
</form>
<?php endif; ?>
</div>
