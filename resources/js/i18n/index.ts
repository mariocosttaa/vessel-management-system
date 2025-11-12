import { createI18n } from 'vue-i18n';
import en from './locales/en.json';
import pt from './locales/pt.json';
import es from './locales/es.json';
import fr from './locales/fr.json';

export type SupportedLocale = 'en' | 'pt' | 'es' | 'fr';

export const supportedLocales: SupportedLocale[] = ['en', 'pt', 'es', 'fr'];

export const localeNames: Record<SupportedLocale, string> = {
    en: 'English',
    pt: 'Português',
    es: 'Español',
    fr: 'Français',
};

// Get locale from localStorage or default to 'en'
const getDefaultLocale = (): SupportedLocale => {
    if (typeof window !== 'undefined') {
        const saved = localStorage.getItem('locale') as SupportedLocale;
        if (saved && supportedLocales.includes(saved)) {
            return saved;
        }
    }
    return 'en';
};

const i18n = createI18n({
    legacy: false,
    locale: getDefaultLocale(),
    fallbackLocale: 'en',
    messages: {
        en,
        pt,
        es,
        fr,
    },
});

export default i18n;

