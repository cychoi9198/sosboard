<?php
declare(strict_types=1);

namespace App\Controller;

use App\Lib\Auth;
use App\Lib\Config;
use App\Lib\Countries;
use App\Lib\Csrf;
use App\Lib\I18n;
use App\Lib\Phone;
use App\Lib\RateLimit;
use App\Lib\Url;
use App\Lib\Validator;
use App\Lib\View;
use App\Repository\ContactRepository;

final class ContactController
{
    private const PAGE_SIZE = 10;

    public function list(): void
    {
        $before = isset($_GET['before']) && ctype_digit((string) $_GET['before']) ? (int) $_GET['before'] : null;

        $searchDial = isset($_GET['country_dial']) ? trim((string) $_GET['country_dial']) : '';
        $searchLocal = isset($_GET['local']) ? trim((string) $_GET['local']) : '';
        $exactQuery = null;
        $searchError = null;

        if ($searchLocal !== '') {
            $maxChars = Config::get('limits')['contact_local_number_max_chars'];
            if (!Countries::isValidDial($searchDial)) {
                $searchError = I18n::t('error_invalid_phone');
            } elseif (!Validator::localPhoneNumber($searchLocal, $maxChars)) {
                $searchError = I18n::t('error_invalid_phone');
            } else {
                $normalizedLocal = Phone::stripLeadingZero($searchLocal);
                $exactQuery = ContactRepository::normalize($searchDial . $normalizedLocal);
            }
        }

        $repo = new ContactRepository();
        $rows = $repo->listPage($exactQuery, $before, self::PAGE_SIZE + 1);

        $hasMore = count($rows) > self::PAGE_SIZE;
        $contacts = array_slice($rows, 0, self::PAGE_SIZE);
        $nextBeforeId = $hasMore ? (int) end($contacts)['id'] : null;

        View::render('contact/list', [
            'contacts' => $contacts,
            'hasMore' => $hasMore,
            'nextBeforeId' => $nextBeforeId,
            'searchDial' => $searchDial !== '' ? $searchDial : '+82',
            'searchLocal' => $searchLocal,
            'isSearching' => $searchLocal !== '',
            'searchError' => $searchError,
        ]);
    }

    public function writeForm(): void
    {
        $_SESSION['_contact_form_started_at'] = time();
        View::render('contact/write', ['errors' => [], 'old' => []]);
    }

    public function writeSubmit(): void
    {
        if (!Csrf::verify($_POST['csrf_token'] ?? null)) {
            http_response_code(400);
            View::render('contact/write', ['errors' => [I18n::t('error_csrf')], 'old' => $_POST]);
            return;
        }

        // Honeypot: bots fill every field, real users never see or fill this one.
        if (($_POST['website'] ?? '') !== '') {
            View::redirect(Url::to('contact'));
        }

        $limits = Config::get('limits');
        $startedAt = $_SESSION['_contact_form_started_at'] ?? 0;
        unset($_SESSION['_contact_form_started_at']);

        $ipHash = RateLimit::ipHash();
        $errors = [];

        if (time() - (int) $startedAt < $limits['contact_min_seconds']) {
            $errors[] = I18n::t('error_too_fast');
        }
        if (RateLimit::tooManyContacts($ipHash, $limits['contact_max_per_10min'], 10)) {
            $errors[] = I18n::t('error_rate_limited');
        }

        $countryDial = trim((string) ($_POST['country_dial'] ?? ''));
        $localNumber = trim((string) ($_POST['phone_local'] ?? ''));

        if (!Countries::isValidDial($countryDial)) {
            $errors[] = I18n::t('error_invalid_phone');
        }
        if (!Validator::localPhoneNumber($localNumber, $limits['contact_local_number_max_chars'])) {
            $errors[] = I18n::t('error_invalid_phone');
        }

        // Stored in international form: the national trunk prefix "0" is dropped (e.g. "010..." -> "10...").
        $phone = $countryDial . ' ' . Phone::stripLeadingZero($localNumber);
        if (!$errors && !Validator::phone($phone, $limits['contact_phone_max_chars'])) {
            $errors[] = I18n::t('error_invalid_phone');
        }

        $body = (string) ($_POST['body'] ?? '');
        if (!Validator::contactBody($body, $limits['contact_body_max_chars'])) {
            $errors[] = I18n::t('error_invalid_contact_body', ['{max}' => $limits['contact_body_max_chars']]);
        } elseif (!Validator::noTags($body)) {
            $errors[] = I18n::t('error_html_not_allowed');
        }

        if ($errors) {
            http_response_code(422);
            View::render('contact/write', ['errors' => $errors, 'old' => $_POST]);
            return;
        }

        RateLimit::recordContactAttempt($ipHash);

        $repo = new ContactRepository();
        $id = $repo->create($phone, trim($body), $ipHash);

        View::flash(I18n::t('flash_contact_created'), 'success');
        View::redirect(Url::to('contact/view/' . $id));
    }

    public function view(int $id): void
    {
        $repo = new ContactRepository();
        $contact = $repo->find($id);

        if ($contact === null) {
            http_response_code(404);
            View::render('board/not_found');
            return;
        }

        View::render('contact/view', ['contact' => $contact]);
    }

    /** Contact board has no per-entry password — only admins can remove entries. */
    public function delete(int $id): void
    {
        if (!Csrf::verify($_POST['csrf_token'] ?? null) || !Auth::isAdmin()) {
            View::flash(I18n::t('error_delete_denied'), 'error');
            View::redirect(Url::to('contact/view/' . $id));
        }

        $repo = new ContactRepository();
        if ($repo->find($id) === null) {
            http_response_code(404);
            View::render('board/not_found');
            return;
        }

        $repo->softDelete($id);
        View::flash(I18n::t('flash_contact_deleted'), 'success');
        View::redirect(Url::to('contact'));
    }
}
