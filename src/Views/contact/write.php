<?php
declare(strict_types=1);

use App\Lib\Config;
use App\Lib\Countries;
use App\Lib\Csrf;
use App\Lib\I18n;
use App\Lib\Url;
use App\Lib\View;

/** @var array $errors */
/** @var array $old */

$localMax = Config::get('limits')['contact_local_number_max_chars'];
$bodyMax = Config::get('limits')['contact_body_max_chars'];
$selectedDial = $old['country_dial'] ?? '+82';
?>
<h2><?= View::e(I18n::t('contact_title')) ?></h2>
<p class="hint"><?= View::e(I18n::t('contact_intro')) ?></p>

<?php if ($errors): ?>
<div class="errors">
<?php foreach ($errors as $err): ?>
<p><?= View::e($err) ?></p>
<?php endforeach; ?>
</div>
<?php endif; ?>

<form method="post" action="<?= View::e(Url::to('contact/write')) ?>">
<?= Csrf::field() ?>
<div class="honeypot" aria-hidden="true">
<label for="website">Website</label>
<input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
</div>

<p>
<label for="country_dial"><?= View::e(I18n::t('label_country')) ?></label>
<select id="country_dial" name="country_dial">
<?php foreach (Countries::list() as $c): ?>
<option value="<?= View::e($c['dial']) ?>"<?= $selectedDial === $c['dial'] ? ' selected' : '' ?>><?= View::e(I18n::t($c['key'])) ?> (<?= View::e($c['dial']) ?>)</option>
<?php endforeach; ?>
</select>
</p>

<p>
<label for="phone_local"><?= View::e(I18n::t('label_phone')) ?></label>
<input type="text" id="phone_local" name="phone_local" maxlength="<?= (int) $localMax ?>" value="<?= View::e($old['phone_local'] ?? '') ?>" required>
<span class="hint"><?= View::e(I18n::t('phone_hint')) ?></span>
</p>

<p>
<label for="body"><?= View::e(I18n::t('label_body')) ?></label>
<textarea id="body" name="body" maxlength="<?= (int) $bodyMax ?>"><?= View::e($old['body'] ?? '') ?></textarea>
<span class="hint"><?= View::e(I18n::t('char_limit_hint', ['{max}' => $bodyMax])) ?></span>
</p>

<div class="actions">
<button type="submit" class="btn"><?= View::e(I18n::t('btn_submit')) ?></button>
<a class="btn" href="<?= View::e(Url::to('contact')) ?>"><?= View::e(I18n::t('btn_cancel')) ?></a>
</div>
</form>
