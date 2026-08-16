<?php
declare(strict_types=1);

use App\Lib\Csrf;
use App\Lib\Dates;
use App\Lib\I18n;
use App\Lib\Url;
use App\Lib\View;

/** @var array $posts */
/** @var array $contacts */
/** @var array $bannedRanges */
?>
<h2><?= View::e(I18n::t('admin_title')) ?></h2>
<p class="hint"><?= View::e(I18n::t('admin_shared_ip_warning')) ?></p>

<h3><?= View::e(I18n::t('admin_ban_form_title')) ?></h3>
<form method="post" action="<?= View::e(Url::to('admin/ban')) ?>">
<?= Csrf::field() ?>
<p>
<label for="start_ip"><?= View::e(I18n::t('label_start_ip')) ?></label>
<input type="text" id="start_ip" name="start_ip" placeholder="1.2.3.0" required>
</p>
<p>
<label for="end_ip"><?= View::e(I18n::t('label_end_ip')) ?></label>
<input type="text" id="end_ip" name="end_ip" placeholder="1.2.3.255" required>
</p>
<p>
<label for="reason"><?= View::e(I18n::t('label_ban_reason')) ?></label>
<input type="text" id="reason" name="reason" maxlength="255">
</p>
<div class="actions">
<button type="submit" class="btn"><?= View::e(I18n::t('btn_ban')) ?></button>
</div>
</form>

<h3><?= View::e(I18n::t('admin_banned_ranges')) ?></h3>
<?php if (!$bannedRanges): ?>
<p><?= View::e(I18n::t('admin_no_banned_ranges')) ?></p>
<?php else: ?>
<table>
<thead>
<tr>
<th><?= View::e(I18n::t('label_start_ip')) ?></th>
<th><?= View::e(I18n::t('label_end_ip')) ?></th>
<th><?= View::e(I18n::t('label_ban_reason')) ?></th>
<th><?= View::e(I18n::t('col_date')) ?></th>
<th></th>
</tr>
</thead>
<tbody>
<?php foreach ($bannedRanges as $r): ?>
<tr>
<td><?= View::e($r['start_ip']) ?></td>
<td><?= View::e($r['end_ip']) ?></td>
<td><?= View::e($r['reason'] ?? '') ?></td>
<td><?= View::e(Dates::display((string) $r['banned_at'])) ?></td>
<td>
<form method="post" action="<?= View::e(Url::to('admin/unban')) ?>">
<?= Csrf::field() ?>
<input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
<button type="submit" class="btn"><?= View::e(I18n::t('btn_unban')) ?></button>
</form>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>

<h3><?= View::e(I18n::t('admin_recent_posts')) ?></h3>
<table>
<thead>
<tr>
<th><?= View::e(I18n::t('col_title')) ?></th>
<th><?= View::e(I18n::t('col_nickname')) ?></th>
<th><?= View::e(I18n::t('label_ip')) ?></th>
<th><?= View::e(I18n::t('col_date')) ?></th>
<th></th>
</tr>
</thead>
<tbody>
<?php foreach ($posts as $p): ?>
<?php $nickname = $p['user_nickname'] ?? $p['guest_nickname'] ?? '?'; ?>
<tr>
<td><a href="<?= View::e(Url::to('board/view/' . $p['id'])) ?>"><?= View::e($p['title']) ?></a></td>
<td><?= View::e($nickname) ?></td>
<td><?= View::e($p['ip'] ?? '') ?></td>
<td><?= View::e(Dates::display((string) $p['created_at'])) ?></td>
<td>
<?php if (!empty($p['ip'])): ?>
<form method="post" action="<?= View::e(Url::to('admin/ban')) ?>">
<?= Csrf::field() ?>
<input type="hidden" name="start_ip" value="<?= View::e($p['ip']) ?>">
<input type="hidden" name="end_ip" value="<?= View::e($p['ip']) ?>">
<button type="submit" class="btn"><?= View::e(I18n::t('btn_ban_and_delete')) ?></button>
</form>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<h3><?= View::e(I18n::t('admin_recent_contacts')) ?></h3>
<table>
<thead>
<tr>
<th><?= View::e(I18n::t('col_phone')) ?></th>
<th><?= View::e(I18n::t('label_ip')) ?></th>
<th><?= View::e(I18n::t('col_date')) ?></th>
<th></th>
</tr>
</thead>
<tbody>
<?php foreach ($contacts as $c): ?>
<tr>
<td><a href="<?= View::e(Url::to('contact/view/' . $c['id'])) ?>"><?= View::e($c['phone']) ?></a></td>
<td><?= View::e($c['ip'] ?? '') ?></td>
<td><?= View::e(Dates::display((string) $c['created_at'])) ?></td>
<td>
<?php if (!empty($c['ip'])): ?>
<form method="post" action="<?= View::e(Url::to('admin/ban')) ?>">
<?= Csrf::field() ?>
<input type="hidden" name="start_ip" value="<?= View::e($c['ip']) ?>">
<input type="hidden" name="end_ip" value="<?= View::e($c['ip']) ?>">
<button type="submit" class="btn"><?= View::e(I18n::t('btn_ban_and_delete')) ?></button>
</form>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
