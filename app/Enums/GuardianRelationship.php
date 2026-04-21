<?php

namespace App\Enums;

enum GuardianRelationship: string
{
    case Ayah = 'ayah';
    case Ibu = 'ibu';
    case Wali = 'wali';

    public function label(): string
    {
        return match ($this) {
            self::Ayah => 'Ayah',
            self::Ibu => 'Ibu',
            self::Wali => 'Wali',
        };
    }
}
