<?php

namespace App\Support;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

/**
 * Helper angka desimal berbasis brick/math. Uang dan jumlah kripto tidak boleh
 * dihitung dengan float karena 0.1 + 0.2 != 0.3.
 */
final class DecimalString
{
    /**
     * Ubah angka dari JSON (int/float/string) menjadi string desimal tanpa notasi ilmiah.
     */
    public static function fromNumber(int|float|string $value): string
    {
        if (is_float($value)) {
            if (! is_finite($value)) {
                throw new \InvalidArgumentException('Angka tidak valid.');
            }

            // json_encode memakai representasi terpendek yang akurat (67000.12, bukan
            // 67000.119999999995). Notasi ilmiah (1.2e-5) tetap bisa dibaca BigDecimal.
            $value = json_encode($value);
        }

        return self::trim((string) $value);
    }

    /**
     * "0.5000000000" → "0.5", "100.00" → "100".
     */
    public static function trim(string $value): string
    {
        // Dinormalisasi dulu lewat BigDecimal (menolak input bukan angka, mengubah 1e-5
        // menjadi 0.00001), lalu nol di belakang koma dibuang secara manual supaya
        // tidak bergantung pada nama method yang berbeda antar versi brick/math.
        $normalized = (string) BigDecimal::of($value);

        if (! str_contains($normalized, '.')) {
            return $normalized;
        }

        $trimmed = rtrim(rtrim($normalized, '0'), '.');

        return $trimmed === '-0' ? '0' : $trimmed;
    }

    /**
     * Format uang 2 angka di belakang koma, dibulatkan half-up: "1234.567" → "1234.57".
     */
    public static function money(BigDecimal $value): string
    {
        return (string) $value->toScale(2, RoundingMode::HalfUp);
    }

    /**
     * Persentase a / b × 100 dengan 2 angka di belakang koma; null kalau b = 0.
     */
    public static function percent(BigDecimal $part, BigDecimal $whole): ?string
    {
        if ($whole->isZero()) {
            return null;
        }

        return (string) $part->multipliedBy(100)->dividedBy($whole, 2, RoundingMode::HalfUp);
    }
}
