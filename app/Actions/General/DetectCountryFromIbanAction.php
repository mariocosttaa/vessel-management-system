<?php

namespace App\Actions\General;

use App\Models\Country;

class DetectCountryFromIbanAction
{
    /**
     * Detect country from IBAN by extracting the first 2 characters (country code).
     *
     * @param string $iban The IBAN string
     * @return int|null The country ID if found, null otherwise
     */
    public static function execute(string $iban): ?int
    {
        // Remove spaces and convert to uppercase
        $iban = strtoupper(preg_replace('/\s+/', '', trim($iban)));

        // Extract first 2 characters (country code)
        if (strlen($iban) < 2) {
            return null;
        }

        $countryCode = substr($iban, 0, 2);

        // Find country by code (uppercase match)
        $country = Country::where('code', $countryCode)->first();

        return $country ? $country->id : null;
    }
}

