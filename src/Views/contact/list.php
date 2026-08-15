<?php
declare(strict_types=1);

use App\Lib\Auth;
use App\Lib\Countries;
use App\Lib\Csrf;
use App\Lib\Dates;
use App\Lib\I18n;
use App\Lib\Phone;
use App\Lib\Url;
use App\Lib\View;

/** @var array $contacts */
/** @var bool $hasMore */
/** @var int|null $nextBeforeId */
/** @var string $searchDial */
/** @var string $searchLocal */
/** @var bool $isSearching */
/** @var string|null $searchError */
?>
<h2><?= View::e(I18n::t('contact_title')) ?></h2>
<p class="hint"><?= View::e(I18n::t('contact_intro')) ?></p>

<form method="get" action="<?= View::e(Url::to('contact')) ?>">
<p><?= View::e(I18n::t('label_search_phone')) ?></p>
<p>
<label for="search_country_dial"><?= View::e(I18n::t('label_country')) ?></label>
<select id="search_country_dial" name="country_dial">
<?php foreach (Countries::list() as $c): ?>
<option value="<?= View::e($c['dial']) ?>"<?= $searchDial === $c['dial'] ? ' selected' : '' ?>><?= View::e(I18n::t($c['key'])) ?> (<?= View::e($c['dial']) ?>)</option>
<?php endforeach; ?>
</select>
</p>
<p>
<label for="search_local"><?= View::e(I18n::t('label_phone')) ?></label>
<input type="text" id="search_local" name="local" maxlength="17" value="<?= View::e($searchLocal) ?>" placeholder="<?= View::e(I18n::t('phone_hint')) ?>">
<button type="submit" class="btn"><?= View::e(I18n::t('btn_search')) ?></button>
<?php if ($isSearching): ?>
<a class="btn" href="<?= View::e(Url::to('contact')) ?>"><?= View::e(I18n::t('btn_cancel')) ?></a>
<?php endif; ?>
</p>
</form>

<?php if ($searchError): ?>
<div class="errors"><p><?= View::e($searchError) ?></p></div>
<?php endif; ?>

<div class="toolbar">
<a class="btn" href="<?= View::e(Url::to('contact/write')) ?>"><?= View::e(I18n::t('btn_write')) ?></a>
</div>

<?php if (!$contacts): ?>
<p><?= View::e(I18n::t('contact_empty')) ?></p>
<?php else: ?>
<table>
<thead>
<tr>
<th><?= View::e(I18n::t('col_phone')) ?></th>
<th><?= View::e(I18n::t('label_body')) ?></th>
<th><?= View::e(I18n::t('col_date')) ?></th>
<?php if (Auth::isAdmin()): ?><th></th><?php endif; ?>
</tr>
</thead>
<tbody>
<?php foreach ($contacts as $c): ?>
<tr>
<td><a href="<?= View::e(Url::to('contact/view/' . $c['id'])) ?>"><?= View::e(Phone::mask($c['phone'])) ?></a></td>
<td><?= View::e(mb_substr($c['body'], 0, 30)) ?><?= mb_strlen($c['body']) > 30 ? '…' : '' ?></td>
<td><?= View::e(Dates::display((string) $c['created_at'])) ?></td>
<?php if (Auth::isAdmin()): ?>
<td>
<form method="post" action="<?= View::e(Url::to('contact/delete/' . $c['id'])) ?>">
<?= Csrf::field() ?>
<button type="submit" class="btn"><?= View::e(I18n::t('btn_delete')) ?></button>
</form>
</td>
<?php endif; ?>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

<?php if ($hasMore): ?>
<div class="pager">
<?php
$pagerParams = ['before' => $nextBeforeId];
if ($isSearching) {
    $pagerParams['country_dial'] = $searchDial;
    $pagerParams['local'] = $searchLocal;
}
?>
<a href="<?= View::e(Url::to('contact') . '?' . http_build_query($pagerParams)) ?>"><?= View::e(I18n::t('btn_more')) ?></a>
</div>
<?php endif; ?>
