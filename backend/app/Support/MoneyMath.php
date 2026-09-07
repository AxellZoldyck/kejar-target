<?php

namespace App\Support;

use InvalidArgumentException;
use OverflowException;

final class MoneyMath
{
    public const MULTIPLIER_SCALE = 10_000;

    public static function multiplierToScaled(string|int $value): int
    {
        $value = (string) $value;

        if (! preg_match('/^(\d{1,5})(?:\.(\d{1,4}))?$/', $value, $matches)) {
            throw new InvalidArgumentException('Multiplier harus berada pada rentang 0 sampai 99999.9999.');
        }

        return ((int) $matches[1] * self::MULTIPLIER_SCALE)
            + (int) str_pad($matches[2] ?? '', 4, '0');
    }

    public static function multiply(int $amount, string|int $multiplier): int
    {
        if ($amount < 0) {
            throw new InvalidArgumentException('Nilai uang tidak boleh negatif.');
        }

        $scaled = self::multiplierToScaled($multiplier);
        $whole = intdiv($scaled, self::MULTIPLIER_SCALE);
        $fraction = $scaled % self::MULTIPLIER_SCALE;
        $wholeTotal = self::multiplyUnits($amount, $whole);

        // Splitting the amount first avoids an overflowing `$amount * $fraction`.
        $fractionWhole = self::multiplyUnits(
            intdiv($amount, self::MULTIPLIER_SCALE),
            $fraction,
        );
        $remainderNumerator = (($amount % self::MULTIPLIER_SCALE) * $fraction)
            + intdiv(self::MULTIPLIER_SCALE, 2);
        $fractionTotal = self::add(
            $fractionWhole,
            intdiv($remainderNumerator, self::MULTIPLIER_SCALE),
        );

        return self::add($wholeTotal, $fractionTotal);
    }

    public static function normalizeMultiplier(string|int $value): string
    {
        $scaled = self::multiplierToScaled($value);

        return sprintf('%d.%04d', intdiv($scaled, self::MULTIPLIER_SCALE), $scaled % self::MULTIPLIER_SCALE);
    }

    public static function multiplyUnits(int $amount, int $units): int
    {
        if ($amount < 0 || $units < 0) {
            throw new InvalidArgumentException('Nilai uang dan jumlah unit tidak boleh negatif.');
        }

        if ($amount !== 0 && $units > intdiv(PHP_INT_MAX, $amount)) {
            throw new OverflowException('Hasil perhitungan uang melampaui kapasitas integer.');
        }

        return $amount * $units;
    }

    public static function add(int $left, int $right): int
    {
        if ($left < 0 || $right < 0) {
            throw new InvalidArgumentException('Nilai uang tidak boleh negatif.');
        }

        if ($right > PHP_INT_MAX - $left) {
            throw new OverflowException('Hasil perhitungan uang melampaui kapasitas integer.');
        }

        return $left + $right;
    }
}
