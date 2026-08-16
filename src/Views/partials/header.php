<?php
declare(strict_types=1);

use App\Lib\Auth;
use App\Lib\Csrf;
use App\Lib\I18n;
use App\Lib\Url;
use App\Lib\View;

$currentPath = $GLOBALS['currentPath'] ?? 'board';
$supportedLangs = \App\Lib\Config::get('app')['supported_langs'];
?>
<!doctype html>
<html lang="<?= View::e(I18n::locale()) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= View::e(I18n::t('site_title')) ?></title>
<style><?= $GLOBALS['inlineCss'] ?></style>
</head>
<body>
<header>
<div class="lang">
<?php foreach ($supportedLangs as $i => $l): ?>
<?= $i > 0 ? '|' : '' ?><a href="<?= View::e(Url::withLocale($l, $currentPath)) ?>"><?= View::e(strtoupper($l)) ?></a>
<?php endforeach; ?>
</div>
<h1><a href="<?= View::e(Url::to('board')) ?>"><?= View::e(I18n::t('site_title')) ?></a></h1>
<nav>
<a href="<?= View::e(Url::to('board')) ?>"><?= View::e(I18n::t('nav_board')) ?></a>
<a href="<?= View::e(Url::to('board/write')) ?>"><?= View::e(I18n::t('nav_write')) ?></a>
<a href="<?= View::e(Url::to('contact')) ?>"><?= View::e(I18n::t('nav_contact')) ?></a>
<?php if (Auth::isAdmin()): ?>
<a href="<?= View::e(Url::to('admin')) ?>"><?= View::e(I18n::t('nav_admin')) ?></a>
<?php endif; ?>
<?php if (Auth::check()): ?>
<?= View::e(I18n::t('nav_welcome', ['{nick}' => Auth::user()['nickname']])) ?>
<form method="post" action="<?= View::e(Url::to('auth/logout')) ?>" class="inline-form">
<?= Csrf::field() ?>
<button type="submit" class="link-btn"><?= View::e(I18n::t('nav_logout')) ?></button>
</form>
<?php else: ?>
<a href="<?= View::e(Url::to('auth/login')) ?>"><?= View::e(I18n::t('nav_login')) ?></a>
<a href="<?= View::e(Url::to('auth/register')) ?>"><?= View::e(I18n::t('nav_register')) ?></a>
<?php endif; ?>
</nav>
</header>
<?php foreach (View::pullFlashes() as $flash): ?>
<div class="flash flash-<?= View::e($flash['type']) ?>"><?= View::e($flash['message']) ?></div>
<?php endforeach; ?>
<main>
