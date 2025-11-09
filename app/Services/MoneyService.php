<?php

namespace App\Services;

class MoneyService
{
    /**
     * Convert float to integer (cents)
     */
    public static function toInteger(float $value, int $decimals = 2): int
    {
        return (int) round($value * pow(10, $decimals));
    }

    /**
     * Convert integer (cents) to float
     */
    public static function toFloat(int $value, int $decimals = 2): float
    {
        return $value / pow(10, $decimals);
    }

    /**
     * Format money value with currency
     */
    public static function format(int $value, string $currency = 'EUR', int $decimals = 2): string
    {
        $float = self::toFloat($value, $decimals);
        return number_format($float, $decimals, ',', '.') . ' ' . $currency;
    }

    /**
     * Format money value without currency symbol
     */
    public static function formatWithoutSymbol(int $value, int $decimals = 2): string
    {
        $float = self::toFloat($value, $decimals);
        return number_format($float, $decimals, ',', '.');
    }

    /**
     * Calculate VAT amount from base amount
     */
    public static function calculateVat(int $amount, float $vatRate, int $decimals = 2): int
    {
        $vatAmount = ($amount * $vatRate) / 100;
        return (int) round($vatAmount);
    }

    /**
     * Calculate base amount and VAT from total amount (when amount includes VAT)
     * Returns ['base' => int, 'vat' => int]
     */
    public static function calculateFromTotalIncludingVat(int $totalAmount, float $vatRate, int $decimals = 2): array
    {
        // base = total / (1 + vat_rate/100)
        $baseAmount = (int) round($totalAmount / (1 + ($vatRate / 100)));
        $vatAmount = $totalAmount - $baseAmount;

        return [
            'base' => $baseAmount,
            'vat' => $vatAmount,
        ];
    }

    /**
     * Calculate total amount (amount + VAT)
     */
    public static function calculateTotal(int $amount, int $vatAmount): int
    {
        return $amount + $vatAmount;
    }

    /**
     * Parse money string to integer
     */
    public static function parseMoneyString(string $value): int
    {
        // Remove currency symbols and convert to float
        $value = preg_replace('/[^\d.,]/', '', $value);
        $value = str_replace(',', '.', $value);

        return (int) round((float) $value * 100); // Convert to cents
    }

    /**
     * Validate money amount
     */
    public static function isValidAmount($value): bool
    {
        if (is_numeric($value)) {
            return (float) $value >= 0;
        }

        if (is_string($value)) {
            $parsed = self::parseMoneyString($value);
            return $parsed >= 0;
        }

        return false;
    }
}
