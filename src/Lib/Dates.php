<?php
declare(strict_types=1);

namespace App\Lib;

use DateTimeImmutable;
use DateTimeZone;

final class Dates
{
    /** Posts are stored as UTC (see Db::conn). Render them in a single site-wide display zone. */
    private const DISPLAY_TZ = 'Asia/Seoul';

    public static function display(string $utcDatetime): string
    {
        $dt = new DateTimeImmutable($utcDatetime, new DateTimeZone('UTC'));
        return $dt->setTimezone(new DateTimeZone(self::DISPLAY_TZ))->format('Y-m-d H:i');
    }
}
