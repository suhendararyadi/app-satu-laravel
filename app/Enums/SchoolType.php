<?php

namespace App\Enums;

enum SchoolType: string
{
    case Sma = 'SMA';
    case Smk = 'SMK';
    case Ma = 'MA';

    /**
     * Get the display label in Indonesian for the school type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Sma => 'SMA (Sekolah Menengah Atas)',
            self::Smk => 'SMK (Sekolah Menengah Kejuruan)',
            self::Ma => 'MA (Madrasah Aliyah)',
        };
    }
}
