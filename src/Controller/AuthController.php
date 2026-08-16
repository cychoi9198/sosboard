<?php
declare(strict_types=1);

namespace App\Controller;

use App\Lib\Auth;
use App\Lib\Config;
use App\Lib\Csrf;
use App\Lib\I18n;
use App\Lib\IpBan;
use App\Lib\RateLimit;
use App\Lib\Session;
use App\Lib\Url;
use App\Lib\Validator;
use App\Lib\View;
use App\Repository\UserRepository;

final class AuthController
{
    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            View::render('auth/login', ['errors' => [], 'old' => []]);
            return;
        }

        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            View::render('auth/login', ['errors' => [I18n::t('error_csrf')], 'old' => []]);
            return;
        }

        $loginId = trim((string) ($_POST['login_id'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $identifier = strtolower($loginId);
        $limits = Config::get('limits');

        if ($identifier !== '' && RateLimit::tooManyLoginAttempts($identifier, $limits['login_max_attempts_per_15min'], 15)) {
            View::render('auth/login', ['errors' => [I18n::t('error_rate_limited')], 'old' => ['login_id' => $loginId]]);
            return;
        }

        $ok = $identifier !== '' && Auth::attempt($loginId, $password);
        if ($identifier !== '') {
            RateLimit::recordLoginAttempt($identifier, $ok);
        }

        if (!$ok) {
            View::render('auth/login', [
                'errors' => [I18n::t('error_login_failed')],
                'old' => ['login_id' => $loginId],
            ]);
            return;
        }

        View::flash(I18n::t('flash_login_success'), 'success');
        View::redirect(Url::to('board'));
    }

    public function register(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            View::render('auth/register', ['errors' => [], 'old' => []]);
            return;
        }

        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            View::render('auth/register', ['errors' => [I18n::t('error_csrf')], 'old' => []]);
            return;
        }

        $limits = Config::get('limits');
        $ip = RateLimit::clientIp();

        // Also blocks a banned IP from just creating a fresh account to route around the ban.
        if (IpBan::isBanned($ip) || RateLimit::tooManyRegistrations($ip, $limits['registration_max_per_15min'], 15)) {
            View::render('auth/register', ['errors' => [I18n::t('error_rate_limited')], 'old' => []]);
            return;
        }
        RateLimit::recordRegistrationAttempt($ip);

        $loginId = trim((string) ($_POST['login_id'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $nickname = trim((string) ($_POST['nickname'] ?? ''));

        $errors = [];
        $repo = new UserRepository();

        if (!Validator::loginId($loginId)) {
            $errors[] = I18n::t('error_invalid_login_id');
        } elseif ($repo->loginIdExists($loginId)) {
            $errors[] = I18n::t('error_login_id_taken');
        }

        if (!Validator::password($password)) {
            $errors[] = I18n::t('error_invalid_password');
        }

        if (!Validator::nickname($nickname)) {
            $errors[] = I18n::t('error_invalid_nickname');
        } elseif (!Validator::noTags($nickname)) {
            $errors[] = I18n::t('error_html_not_allowed');
        } elseif ($repo->nicknameExists($nickname)) {
            $errors[] = I18n::t('error_nickname_taken');
        }

        if ($errors) {
            View::render('auth/register', [
                'errors' => $errors,
                'old' => ['login_id' => $loginId, 'nickname' => $nickname],
            ]);
            return;
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $repo->create($loginId, $hash, $nickname);

        Auth::attempt($loginId, $password);
        View::flash(I18n::t('flash_register_success'), 'success');
        View::redirect(Url::to('board'));
    }

    public function logout(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && Csrf::verify($_POST['csrf_token'] ?? null)) {
            Auth::logout();
        }
        View::redirect(Url::to('board'));
    }
}
