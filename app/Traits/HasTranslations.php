<?php

namespace App\Traits;

use Illuminate\Support\Facades\App;

trait HasTranslations
{
    /**
     * Get translation for the current user's language preference.
     * Falls back to English if translation is missing.
     *
     * @param string $key The translation key (English text)
     * @param array $replace Replacements for placeholders
     * @param string|null $locale Force a specific locale (optional)
     * @return string
     */
    protected function trans(string $key, array $replace = [], ?string $locale = null): string
    {
        // Get user's language preference if available
        if (!$locale && auth()->check() && auth()->user()->language) {
            $locale = auth()->user()->language;
        }

        // Use specified locale or current app locale
        $originalLocale = App::getLocale();
        if ($locale) {
            App::setLocale($locale);
        }

        // Get translation - Laravel will fallback to key if not found
        $translation = __($key, $replace);

        // Restore original locale
        if ($locale) {
            App::setLocale($originalLocale);
        }

        return $translation;
    }

    /**
     * Get translation from a specific file.
     *
     * @param string $file Translation file name (without .php)
     * @param string $key Translation key
     * @param array $replace Replacements for placeholders
     * @param string|null $locale Force a specific locale (optional)
     * @return string
     */
    protected function transFrom(string $file, string $key, array $replace = [], ?string $locale = null): string
    {
        // Get user's language preference if available
        if (!$locale && auth()->check() && auth()->user()->language) {
            $locale = auth()->user()->language;
        }

        // Use specified locale or current app locale
        $originalLocale = App::getLocale();
        if ($locale) {
            App::setLocale($locale);
        }

        // Get translation from specific file
        $translation = trans("{$file}.{$key}", $replace);

        // Restore original locale
        if ($locale) {
            App::setLocale($originalLocale);
        }

        return $translation;
    }
}

