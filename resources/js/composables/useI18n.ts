import { useI18n as useVueI18n } from 'vue-i18n';
import { supportedLocales, localeNames, type SupportedLocale } from '@/i18n';

export function useI18n() {
    const { t, locale, availableLocales } = useVueI18n();

    const setLocale = (newLocale: SupportedLocale) => {
        if (supportedLocales.includes(newLocale)) {
            locale.value = newLocale;
            if (typeof window !== 'undefined') {
                localStorage.setItem('locale', newLocale);
            }
        }
    };

    const getCurrentLocale = (): SupportedLocale => {
        return locale.value as SupportedLocale;
    };

    const getLocaleName = (loc: SupportedLocale): string => {
        return localeNames[loc] || loc;
    };

    return {
        t,
        locale,
        availableLocales,
        supportedLocales,
        localeNames,
        setLocale,
        getCurrentLocale,
        getLocaleName,
    };
}

