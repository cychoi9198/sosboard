<?php
declare(strict_types=1);

namespace App\Lib;

final class Countries
{
    /** Dial code + lang-key pairs, in display order. Korea/Japan/US/UK first, then Europe. */
    private const LIST = [
        ['dial' => '+82', 'key' => 'country_kr'],
        ['dial' => '+81', 'key' => 'country_jp'],
        ['dial' => '+1', 'key' => 'country_us'],
        ['dial' => '+44', 'key' => 'country_gb'],
        ['dial' => '+49', 'key' => 'country_de'],
        ['dial' => '+33', 'key' => 'country_fr'],
        ['dial' => '+39', 'key' => 'country_it'],
        ['dial' => '+34', 'key' => 'country_es'],
        ['dial' => '+31', 'key' => 'country_nl'],
        ['dial' => '+32', 'key' => 'country_be'],
        ['dial' => '+41', 'key' => 'country_ch'],
        ['dial' => '+43', 'key' => 'country_at'],
        ['dial' => '+46', 'key' => 'country_se'],
        ['dial' => '+47', 'key' => 'country_no'],
        ['dial' => '+45', 'key' => 'country_dk'],
        ['dial' => '+358', 'key' => 'country_fi'],
        ['dial' => '+48', 'key' => 'country_pl'],
        ['dial' => '+351', 'key' => 'country_pt'],
        ['dial' => '+353', 'key' => 'country_ie'],
        ['dial' => '+30', 'key' => 'country_gr'],
        ['dial' => '+420', 'key' => 'country_cz'],
        ['dial' => '+36', 'key' => 'country_hu'],
        ['dial' => '+40', 'key' => 'country_ro'],
    ];

    public static function list(): array
    {
        return self::LIST;
    }

    public static function isValidDial(string $dial): bool
    {
        foreach (self::LIST as $c) {
            if ($c['dial'] === $dial) {
                return true;
            }
        }
        return false;
    }
}
