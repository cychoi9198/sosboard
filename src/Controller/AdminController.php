<?php
declare(strict_types=1);

namespace App\Controller;

use App\Lib\Auth;
use App\Lib\Csrf;
use App\Lib\I18n;
use App\Lib\IpBan;
use App\Lib\Url;
use App\Lib\View;
use App\Repository\ContactRepository;
use App\Repository\PostRepository;

final class AdminController
{
    private const MODERATION_LIST_SIZE = 30;

    public function moderation(): void
    {
        if (!Auth::isAdmin()) {
            View::redirect(Url::to('board'));
        }

        $postRepo = new PostRepository();
        $contactRepo = new ContactRepository();

        View::render('admin/moderation', [
            'posts' => $postRepo->recentForModeration(self::MODERATION_LIST_SIZE),
            'contacts' => $contactRepo->recentForModeration(self::MODERATION_LIST_SIZE),
            'bannedRanges' => IpBan::list(),
            'errors' => [],
        ]);
    }

    /** Bans a single exact IP (start === end) or a manually entered range, and soft-deletes any exact-IP matches. */
    public function ban(): void
    {
        if (!Auth::isAdmin() || !Csrf::verify($_POST['csrf_token'] ?? null)) {
            View::redirect(Url::to('board'));
        }

        $startIp = trim((string) ($_POST['start_ip'] ?? ''));
        $endIp = trim((string) ($_POST['end_ip'] ?? ''));
        $reason = trim((string) ($_POST['reason'] ?? ''));

        if ($startIp === '' || $endIp === '' || !filter_var($startIp, FILTER_VALIDATE_IP) || !filter_var($endIp, FILTER_VALIDATE_IP)) {
            View::flash(I18n::t('error_invalid_ip'), 'error');
            View::redirect(Url::to('admin'));
        }

        IpBan::ban($startIp, $endIp, Auth::id(), $reason !== '' ? $reason : null);

        $deleted = 0;
        if ($startIp === $endIp) {
            $deleted += (new PostRepository())->softDeleteByIp($startIp);
            $deleted += (new ContactRepository())->softDeleteByIp($startIp);
        }

        View::flash(I18n::t('flash_ip_banned', ['{count}' => (string) $deleted]), 'success');
        View::redirect(Url::to('admin'));
    }

    public function unban(): void
    {
        if (!Auth::isAdmin() || !Csrf::verify($_POST['csrf_token'] ?? null)) {
            View::redirect(Url::to('board'));
        }

        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            IpBan::unban($id);
        }

        View::flash(I18n::t('flash_ip_unbanned'), 'success');
        View::redirect(Url::to('admin'));
    }
}
