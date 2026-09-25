import { router, usePage } from '@inertiajs/vue3';
import { computed, watchEffect } from 'vue';
import { en, type TranslationKey } from '../i18n/en';

export type Locale = 'de' | 'en';
type SharedProps = { locale: Locale; supportedLocales: Locale[] };

export function useI18n() {
    const page = usePage<SharedProps>();
    const locale = computed<Locale>(() => page.props.locale ?? 'de');
    const languageTag = computed(() => locale.value === 'de' ? 'de-DE' : 'en-GB');

    watchEffect(() => {
        document.documentElement.lang = locale.value;
    });

    function t(source: TranslationKey, replacements: Record<string, string | number> = {}): string {
        let value: string = locale.value === 'en' ? en[source] : source;
        for (const [key, replacement] of Object.entries(replacements)) {
            value = value.replaceAll(`:${key}`, String(replacement));
        }
        return value;
    }

    function setLocale(nextLocale: Locale) {
        if (nextLocale === locale.value) return;
        router.post('/locale', { locale: nextLocale }, { preserveScroll: true, preserveState: true });
    }

    return { locale, languageTag, supportedLocales: computed(() => page.props.supportedLocales ?? ['de', 'en']), t, setLocale };
}
