<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/src/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

use App\Controller\AuthController;
use App\Controller\BoardController;
use App\Controller\ContactController;
use App\Lib\Config;
use App\Lib\I18n;
use App\Lib\Session;

set_exception_handler(function (\Throwable $e): void {
    error_log($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo Config::get('app')['debug'] ? (string) $e : 'Internal Server Error';
});

// CSS is inlined into every page (no separate stylesheet request) to save a full
// HTTP round-trip on high-latency legacy connections. The CSP allows exactly this
// content via a hash, so it stays strict without needing 'unsafe-inline'.
$inlineCss = file_get_contents(__DIR__ . '/style.css');
$cssHash = base64_encode(hash('sha256', $inlineCss, true));
$GLOBALS['inlineCss'] = $inlineCss;

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; style-src 'sha256-{$cssHash}'; img-src 'self'; form-action 'self'; base-uri 'self'; frame-ancestors 'none'; object-src 'none'");

Session::start();

$notFound = function (): void {
    http_response_code(404);
    require __DIR__ . '/src/Views/partials/header.php';
    require __DIR__ . '/src/Views/board/not_found.php';
    require __DIR__ . '/src/Views/partials/footer.php';
};

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$basePath = rtrim((string) Config::get('app')['base_path'], '/');
if ($basePath !== '' && str_starts_with($path, $basePath)) {
    $path = substr($path, strlen($basePath));
}
$segments = array_values(array_filter(explode('/', trim($path, '/')), fn ($s) => $s !== ''));

$supportedLangs = Config::get('app')['supported_langs'];
$defaultLang = Config::get('app')['default_lang'];

if (count($segments) === 0) {
    $accept = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    $lang = I18n::negotiate($accept, $supportedLangs, $defaultLang);
    header('Location: ' . $basePath . '/' . $lang . '/board', true, 302);
    exit;
}

$lang = array_shift($segments);
if (!in_array($lang, $supportedLangs, true)) {
    I18n::init($defaultLang);
    $notFound();
    exit;
}
I18n::init($lang);
$GLOBALS['currentPath'] = implode('/', $segments);

$method = $_SERVER['REQUEST_METHOD'];
$r1 = $segments[0] ?? '';
$r2 = $segments[1] ?? '';
$r3 = $segments[2] ?? '';

if ($r1 === '' || $r1 === 'board') {
    if ($r1 === '' || $r2 === '') {
        (new BoardController())->list();
    } elseif ($r2 === 'write') {
        $method === 'POST' ? (new BoardController())->writeSubmit() : (new BoardController())->writeForm();
    } elseif ($r2 === 'view' && ctype_digit($r3)) {
        (new BoardController())->view((int) $r3);
    } elseif ($r2 === 'delete' && ctype_digit($r3) && $method === 'POST') {
        (new BoardController())->delete((int) $r3);
    } else {
        $notFound();
    }
} elseif ($r1 === 'contact') {
    if ($r2 === '') {
        (new ContactController())->list();
    } elseif ($r2 === 'write') {
        $method === 'POST' ? (new ContactController())->writeSubmit() : (new ContactController())->writeForm();
    } elseif ($r2 === 'view' && ctype_digit($r3)) {
        (new ContactController())->view((int) $r3);
    } elseif ($r2 === 'delete' && ctype_digit($r3) && $method === 'POST') {
        (new ContactController())->delete((int) $r3);
    } else {
        $notFound();
    }
} elseif ($r1 === 'auth') {
    if ($r2 === 'login') {
        (new AuthController())->login();
    } elseif ($r2 === 'register') {
        (new AuthController())->register();
    } elseif ($r2 === 'logout' && $method === 'POST') {
        (new AuthController())->logout();
    } else {
        $notFound();
    }
} else {
    $notFound();
}
