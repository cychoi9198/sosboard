<?php
declare(strict_types=1);

return [
    'app' => [
        'name' => 'SOSBoard',
        'base_path' => '',
        'default_lang' => 'ko',
        'supported_langs' => ['ko', 'en', 'ja'],
        'categories' => ['sos', 'safety', 'missing', 'info', 'free', 'notice'],
        // Uncaught exceptions are always logged (error_log) regardless of this flag — only the
        // browser-visible detail depends on it. Keep this false; flip it locally only if you
        // need a stack trace in the response while debugging, and flip it back before committing.
        'debug' => false,
    ],
    'db' => [
        'driver' => 'mysql',
        'dsn' => 'mysql:host=127.0.0.1;port=3306;dbname=sosboard;charset=utf8mb4',
        'user' => 'sosboard',
        'pass' => 'sosboard_dev_pw',

        // To run on SQLite instead (e.g. small/low-power hardware where a separate MySQL
        // server isn't practical) — tested and working, see the *.sqlite.sql migrations:
        //   'driver' => 'sqlite',
        //   'dsn' => 'sqlite:' . __DIR__ . '/../var/data/sosboard.sqlite',
        //   'user' => null,
        //   'pass' => null,
    ],
    'session' => [
        'name' => 'sosb_sid',
        'lifetime_seconds' => 60 * 60 * 24 * 7,
    ],
    'security' => [
        // HMAC pepper for hashing IP addresses before storage (abuse-rate limiting only,
        // never store raw IPs). CHANGE THIS before deploying anywhere non-local.
        'ip_pepper' => 'dev-local-pepper-change-me-2f9a7c1e',
    ],
    'limits' => [
        'post_title_max_chars' => 100,
        'post_body_max_chars' => 500,
        'nickname_max_chars' => 30,
        'post_min_seconds' => 3,
        'post_max_per_10min' => 10,
        'login_max_attempts_per_15min' => 10,
        'registration_max_per_15min' => 10,
        'contact_body_max_chars' => 200,
        'contact_phone_max_chars' => 25,
        'contact_local_number_max_chars' => 17,
        'contact_min_seconds' => 3,
        'contact_max_per_10min' => 10,
    ],
];
