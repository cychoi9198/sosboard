<?php
declare(strict_types=1);

namespace App\Controller;

use App\Lib\Auth;
use App\Lib\Config;
use App\Lib\Csrf;
use App\Lib\I18n;
use App\Lib\IpBan;
use App\Lib\RateLimit;
use App\Lib\Url;
use App\Lib\Validator;
use App\Lib\View;
use App\Repository\PostRepository;

final class BoardController
{
    private const PAGE_SIZE = 10;

    public function list(): void
    {
        $categories = Config::get('app')['categories'];
        $category = $_GET['cat'] ?? null;
        if ($category !== null && !Validator::inList($category, $categories)) {
            $category = null;
        }

        $before = isset($_GET['before']) && ctype_digit((string) $_GET['before']) ? (int) $_GET['before'] : null;

        $rawSearch = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
        $search = null;
        $searchError = null;
        if ($rawSearch !== '') {
            if (Validator::isUtf8($rawSearch) && Validator::mbLenBetween($rawSearch, 2, 100)) {
                $search = $rawSearch;
            } else {
                $searchError = I18n::t('error_invalid_search');
            }
        }

        $repo = new PostRepository();
        $rows = $repo->listPage($category, $search, $before, self::PAGE_SIZE + 1);

        $hasMore = count($rows) > self::PAGE_SIZE;
        $posts = array_slice($rows, 0, self::PAGE_SIZE);
        $nextBeforeId = $hasMore ? (int) end($posts)['id'] : null;

        View::render('board/list', [
            'posts' => $posts,
            'category' => $category,
            'categories' => $categories,
            'hasMore' => $hasMore,
            'nextBeforeId' => $nextBeforeId,
            'search' => $rawSearch,
            'searchError' => $searchError,
        ]);
    }

    public function writeForm(): void
    {
        $_SESSION['_form_started_at'] = time();
        View::render('board/write', [
            'categories' => Config::get('app')['categories'],
            'errors' => [],
            'old' => [],
        ]);
    }

    public function writeSubmit(): void
    {
        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            http_response_code(400);
            View::render('board/write', [
                'categories' => Config::get('app')['categories'],
                'errors' => [I18n::t('error_csrf')],
                'old' => $_POST,
            ]);
            return;
        }

        // Honeypot: bots fill every field, real users never see or fill this one.
        if (($_POST['website'] ?? '') !== '') {
            View::redirect(Url::to('board'));
        }

        $startedAt = $_SESSION['_form_started_at'] ?? null;
        $minSeconds = Config::get('limits')['post_min_seconds'];
        unset($_SESSION['_form_started_at']);

        $limits = Config::get('limits');
        $ip = RateLimit::clientIp();

        $errors = [];

        // Banned IPs get the same generic message as a rate limit, not a distinct "you are
        // banned" — no reason to confirm that to whoever's on the other end of it.
        if (IpBan::isBanned($ip)) {
            $errors[] = I18n::t('error_rate_limited');
        }

        // A missing marker (POST without ever fetching the form) must fail this check, not
        // pass it — treating "no timestamp" as "plenty of time has passed" defeats the point.
        if ($startedAt === null || time() - $startedAt < $minSeconds) {
            $errors[] = I18n::t('error_too_fast');
        }
        if (RateLimit::tooManyPosts($ip, $limits['post_max_per_10min'], 10)) {
            $errors[] = I18n::t('error_rate_limited');
        }

        $categories = Config::get('app')['categories'];
        $category = (string) ($_POST['category'] ?? '');
        if (!Validator::inList($category, $categories)) {
            $errors[] = I18n::t('error_invalid_category');
        } elseif ($category === 'notice' && !Auth::isAdmin()) {
            $errors[] = I18n::t('error_notice_admin_only');
        }

        $title = (string) ($_POST['title'] ?? '');
        if (!Validator::title($title, $limits['post_title_max_chars'])) {
            $errors[] = I18n::t('error_invalid_title', ['{max}' => $limits['post_title_max_chars']]);
        } elseif (!Validator::noTags($title)) {
            $errors[] = I18n::t('error_html_not_allowed');
        }

        $body = (string) ($_POST['body'] ?? '');
        if (!Validator::postBody($body, $limits['post_body_max_chars'])) {
            $errors[] = I18n::t('error_invalid_body', ['{max}' => $limits['post_body_max_chars']]);
        } elseif (!Validator::noTags($body)) {
            $errors[] = I18n::t('error_html_not_allowed');
        }

        $guestNickname = null;
        $guestPasswordHash = null;

        if (!Auth::check()) {
            $nickname = (string) ($_POST['guest_nickname'] ?? '');
            $guestPassword = (string) ($_POST['guest_password'] ?? '');

            if (!Validator::nickname($nickname)) {
                $errors[] = I18n::t('error_invalid_nickname');
            } elseif (!Validator::noTags($nickname)) {
                $errors[] = I18n::t('error_html_not_allowed');
            }
            if (!Validator::mbLenBetween($guestPassword, 4, 72)) {
                $errors[] = I18n::t('error_invalid_guest_password');
            }

            if (!$errors) {
                $guestNickname = $nickname;
                $guestPasswordHash = password_hash($guestPassword, PASSWORD_BCRYPT);
            }
        }

        if ($errors) {
            http_response_code(422);
            View::render('board/write', [
                'categories' => $categories,
                'errors' => $errors,
                'old' => $_POST,
            ]);
            return;
        }

        RateLimit::recordPostAttempt($ip);

        $repo = new PostRepository();
        $id = $repo->create([
            'user_id' => Auth::id(),
            'guest_nickname' => $guestNickname,
            'guest_password_hash' => $guestPasswordHash,
            'category' => $category,
            'title' => trim($title),
            'body' => trim($body),
            'lang' => I18n::locale(),
            'ip' => $ip,
        ]);

        View::flash(I18n::t('flash_post_created'), 'success');
        View::redirect(Url::to('board/view/' . $id));
    }

    public function view(int $id): void
    {
        $repo = new PostRepository();
        $post = $repo->find($id);

        if ($post === null) {
            http_response_code(404);
            View::render('board/not_found');
            return;
        }

        $canDelete = Auth::isAdmin()
            || ($post['user_id'] !== null && Auth::id() === (int) $post['user_id'])
            || ($post['user_id'] === null);

        View::render('board/view', [
            'post' => $post,
            'canDelete' => $canDelete,
            'requiresGuestPassword' => $post['user_id'] === null && !Auth::isAdmin(),
        ]);
    }

    public function delete(int $id): void
    {
        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            View::flash(I18n::t('error_csrf'), 'error');
            View::redirect(Url::to('board/view/' . $id));
        }

        $repo = new PostRepository();
        $post = $repo->find($id);

        if ($post === null) {
            http_response_code(404);
            View::render('board/not_found');
            return;
        }

        $allowed = false;

        if (Auth::isAdmin()) {
            $allowed = true;
        } elseif ($post['user_id'] !== null) {
            $allowed = Auth::check() && Auth::id() === (int) $post['user_id'];
        } else {
            $guestPassword = (string) ($_POST['guest_password'] ?? '');
            $allowed = password_verify($guestPassword, (string) $post['guest_password_hash']);
        }

        if (!$allowed) {
            View::flash(I18n::t('error_delete_denied'), 'error');
            View::redirect(Url::to('board/view/' . $id));
        }

        $repo->softDelete($id);
        View::flash(I18n::t('flash_post_deleted'), 'success');
        View::redirect(Url::to('board'));
    }
}
