<?php

namespace App\Actions;

use App\Models\Currency;
use App\Models\Country;

class MoneyAction
{
    public static function format(
        float|int $amount = 0,
        int|string|null $decimalPlaces = null,
        ?string $currency = null, bool $formatWithSymbol = false): ?string
    {

        // Passo 1: Organiza o número considerando as casas decimais
        $amountAsString = (string) $amount;

        if (isset($currency)) {

            $currencyGet = self::notSearchInDBThisCurrencys($currency);

            // se n tiver, procura na db
            if (! $currencyGet) {
                $currencyGet = Currency::where('code', $currency)->first();
                $decimalPlaces = $currencyGet?->decimal_separator ?? 2;

            } else {
                $decimalPlaces = $currencyGet->decimal_separator;
            }

        }

        // Default to 2 decimal places if not set
        if ($decimalPlaces === null) {
            $decimalPlaces = 2;
        }

        // Adiciona ponto antes das últimas casas decimais
        $amountAsString = substr($amountAsString, 0, -$decimalPlaces).'.'.substr($amountAsString, -$decimalPlaces);

        // Passo 2: Formata o número com separador de milhar (ponto) e decimal (vírgula)
        $formattedAmount = number_format((float) $amountAsString, $decimalPlaces, ',', '.');

        if ($formatWithSymbol == true && ! empty($currency)) {

            $currencyGet = self::notSearchInDBThisCurrencys($currency);
            // se n tiver, procura na db
            if (! $currencyGet) {
                $currencyGet = Currency::where('code', $currency)->first();
            }

            // Only add symbol if currency was found
            if ($currencyGet) {
                $formattedAmount = $currencyGet->symbol.' '.$formattedAmount;
            }

        }

        return $formattedAmount;
    }

    // Método para remover caracteres não numéricos (como pontos, vírgulas) de um valor monetário
    public static function sanitize(string $amount): int
    {
        // Remove todos os caracteres não numéricos, incluindo espaços, vírgulas e pontos
        $sanitizedAmount = preg_replace('/[^0-9]/', '', $amount);

        if (empty($sanitizedAmount)) {
            $sanitizedAmount = 0;
        }

        // Retorna o valor sanitizado como número inteiro
        return (int) $sanitizedAmount;
    }

    private static function notSearchInDBThisCurrencys(?string $currency = null)
    {

        $currency = strtolower($currency);

        $array = [
            'eur' => (object) [
                'code' => 'eur',
                'symbol' => '€',
                'symbol_2' => 'EUR',
                'decimal_separator' => 2,
            ],
            'usd' => (object) [
                'code' => 'usd',
                'symbol' => '$',
                'symbol_2' => 'USD',
                'decimal_separator' => 2,
            ],
            'brl' => (object) [
                'code' => 'brl',
                'symbol' => 'R$',
                'symbol_2' => 'BRL',
                'decimal_separator' => 2,
            ],
            'aoa' => (object) [
                'code' => 'aoa',
                'symbol' => 'Kz',
                'symbol_2' => 'AOA',
                'decimal_separator' => 2,
            ],
        ];

        if (isset($array[$currency])) {
            return $array[$currency];
        } else {
            return false;
        }
    }

    /**
     * Get currency from country code
     */
    public static function getCurrencyFromCountry(string $countryCode): ?string
    {
        $currency = Currency::getByCountryCode($countryCode);
        return $currency ? $currency->code : null;
    }

    /**
     * Get currency from IBAN
     */
    public static function getCurrencyFromIban(string $iban): ?string
    {
        $countryCode = Country::extractCountryCodeFromIban($iban);
        if (!$countryCode) {
            return null;
        }

        return self::getCurrencyFromCountry($countryCode);
    }

    /**
     * Darken a hex color by a percentage
     */
    public static function darkenColor(string $hexColor, int $percent): string
    {
        // Remove # if present
        $hexColor = ltrim($hexColor, '#');

        // Ensure we have a valid hex color
        if (strlen($hexColor) !== 6 || !ctype_xdigit($hexColor)) {
            return '#1e40af'; // Default dark blue
        }

        // Convert to RGB
        $r = hexdec(substr($hexColor, 0, 2));
        $g = hexdec(substr($hexColor, 2, 2));
        $b = hexdec(substr($hexColor, 4, 2));

        // Darken by percentage
        $r = max(0, $r - ($r * $percent / 100));
        $g = max(0, $g - ($g * $percent / 100));
        $b = max(0, $b - ($b * $percent / 100));

        // Convert back to hex
        return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT) .
                    str_pad(dechex($g), 2, '0', STR_PAD_LEFT) .
                    str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
    }

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
     * Format money value with currency (legacy method - use format() instead)
     */
    public static function formatWithCurrency(int $value, string $currency = 'EUR', int $decimals = 2): string
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
