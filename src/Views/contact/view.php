<?php
declare(strict_types=1);

use App\Lib\Auth;
use App\Lib\Csrf;
use App\Lib\Dates;
use App\Lib\I18n;
use App\Lib\Url;
use App\Lib\View;

/** @var array $contact */
?>
<h2><?= View::e(I18n::t('contact_title')) ?></h2>

<div class="meta">
<?= View::e(I18n::t('label_phone')) ?>: <strong><?= View::e($contact['phone']) ?></strong>
&nbsp;·&nbsp;<?= View::e(Dates::display((string) $contact['created_at'])) ?>
</div>

<div class="postbody"><?= nl2br(View::e($contact['body']), false) ?></div>

<div class="actions">
<a class="btn" href="<?= View::e(Url::to('contact')) ?>"><?= View::e(I18n::t('view_back_to_list')) ?></a>

<?php if (Auth::isAdmin()): ?>
<form method="post" action="<?= View::e(Url::to('contact/delete/' . $contact['id'])) ?>" class="delete-form">
<?= Csrf::field() ?>
<button type="submit" class="btn"><?= View::e(I18n::t('btn_delete')) ?></button>
</form>
<?php endif; ?>
</div>
