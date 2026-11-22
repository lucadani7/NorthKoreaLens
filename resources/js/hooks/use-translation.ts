import { usePage } from '@inertiajs/react';
import { useCallback } from 'react';

type Replacements = Record<string, string | number>;

/**
 * Interface strings in the visitor's language. Keys are the English text, as in Laravel's
 * lang/{locale}.json, and ":name" placeholders are filled from `replacements`:
 * t('Read on :source', { source: 'Daily NK' }).
 */
export function useTranslation() {
    const { translations, locale } = usePage().props;

    const t = useCallback(
        (key: string, replacements: Replacements = {}): string => {
            let text = translations[key] ?? key;

            for (const [name, value] of Object.entries(replacements)) {
                text = text.replaceAll(`:${name}`, String(value));
            }

            return text;
        },
        [translations],
    );

    return { t, locale };
}
