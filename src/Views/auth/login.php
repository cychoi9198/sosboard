<?php
declare(strict_types=1);

use App\Lib\Csrf;
use App\Lib\I18n;
use App\Lib\Url;
use App\Lib\View;

/** @var array $errors */
/** @var array $old */
?>
<h2><?= View::e(I18n::t('login_title')) ?></h2>

<?php if ($errors): ?>
<div class="errors">
<?php foreach ($errors as $err): ?>
<p><?= View::e($err) ?></p>
<?php endforeach; ?>
</div>
<?php endif; ?>

<form method="post" action="<?= View::e(Url::to('auth/login')) ?>">
<?= Csrf::field() ?>
<p>
<label for="login_id"><?= View::e(I18n::t('label_login_id')) ?></label>
<input type="text" id="login_id" name="login_id" maxlength="20" value="<?= View::e($old['login_id'] ?? '') ?>" required>
</p>
<p>
<label for="password"><?= View::e(I18n::t('label_password')) ?></label>
<input type="password" id="password" name="password" maxlength="72" required>
</p>
<div class="actions">
<button type="submit" class="btn"><?= View::e(I18n::t('btn_login')) ?></button>
</div>
</form>
<p><a href="<?= View::e(Url::to('auth/register')) ?>"><?= View::e(I18n::t('nav_register')) ?></a></p>
