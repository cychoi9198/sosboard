<?php
declare(strict_types=1);

use App\Lib\Auth;
use App\Lib\Config;
use App\Lib\Csrf;
use App\Lib\I18n;
use App\Lib\Url;
use App\Lib\View;

/** @var array $categories */
/** @var array $errors */
/** @var array $old */

$maxChars = Config::get('limits')['post_body_max_chars'];
$titleMaxChars = Config::get('limits')['post_title_max_chars'];
?>
<h2><?= View::e(I18n::t('write_title')) ?></h2>

<?php if ($errors): ?>
<div class="errors">
<?php foreach ($errors as $err): ?>
<p><?= View::e($err) ?></p>
<?php endforeach; ?>
</div>
<?php endif; ?>

<form method="post" action="<?= View::e(Url::to('board/write')) ?>">
<?= Csrf::field() ?>
<div class="honeypot" aria-hidden="true">
<label for="website">Website</label>
<input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
</div>

<p>
<label for="category"><?= View::e(I18n::t('label_category')) ?></label>
<select id="category" name="category">
<?php foreach ($categories as $cat): ?>
<?php if ($cat === 'notice' && !Auth::isAdmin()) { continue; } ?>
<option value="<?= View::e($cat) ?>"<?= ($old['category'] ?? '') === $cat ? ' selected' : '' ?>><?= View::e(I18n::t('category_' . $cat)) ?></option>
<?php endforeach; ?>
</select>
</p>

<p>
<label for="title"><?= View::e(I18n::t('label_title')) ?></label>
<input type="text" id="title" name="title" maxlength="<?= (int) $titleMaxChars ?>" value="<?= View::e($old['title'] ?? '') ?>">
</p>

<?php if (!Auth::check()): ?>
<p class="hint"><?= View::e(I18n::t('guest_write_notice')) ?></p>
<p>
<label for="guest_nickname"><?= View::e(I18n::t('label_nickname')) ?></label>
<input type="text" id="guest_nickname" name="guest_nickname" maxlength="30" value="<?= View::e($old['guest_nickname'] ?? '') ?>">
</p>
<p>
<label for="guest_password"><?= View::e(I18n::t('label_guest_password')) ?></label>
<input type="password" id="guest_password" name="guest_password" maxlength="72">
</p>
<?php endif; ?>

<p>
<label for="body"><?= View::e(I18n::t('label_body')) ?></label>
<textarea id="body" name="body" maxlength="<?= (int) $maxChars ?>"><?= View::e($old['body'] ?? '') ?></textarea>
<span class="hint"><?= View::e(I18n::t('char_limit_hint', ['{max}' => $maxChars])) ?></span>
</p>

<div class="actions">
<button type="submit" class="btn"><?= View::e(I18n::t('btn_submit')) ?></button>
<a class="btn" href="<?= View::e(Url::to('board')) ?>"><?= View::e(I18n::t('btn_cancel')) ?></a>
</div>
</form>
