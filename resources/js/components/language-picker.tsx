import { router, usePage } from '@inertiajs/react';
import { Globe } from 'lucide-react';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import { update as updateLocale } from '@/routes/locale';

type Props = {
    className?: string;
    /** "header" for the blue site header, "plain" for light or dark page backgrounds. */
    variant?: 'header' | 'plain';
};

/** Language menu. Each language is named in its own language. */
export default function LanguagePicker({
    className,
    variant = 'header',
}: Props) {
    const { locales } = usePage().props;
    const { t, locale } = useTranslation();

    return (
        <label
            className={cn(
                'relative flex h-11 items-center rounded-full',
                variant === 'header'
                    ? 'bg-white/15 text-white'
                    : 'border border-border text-foreground',
                className,
            )}
        >
            <span className="sr-only">{t('Language')}</span>
            <Globe
                aria-hidden="true"
                className="pointer-events-none absolute left-3 size-4"
            />
            <select
                value={locale}
                onChange={(event) =>
                    router.post(
                        updateLocale.url(),
                        { locale: event.target.value },
                        { preserveScroll: true },
                    )
                }
                className={cn(
                    'h-11 cursor-pointer appearance-none rounded-full bg-transparent pr-4 pl-9 text-sm font-semibold focus-visible:ring-2 focus-visible:outline-none',
                    variant === 'header'
                        ? 'text-white focus-visible:ring-white'
                        : 'text-foreground focus-visible:ring-ring',
                )}
            >
                {locales.map(({ code, name }) => (
                    <option
                        key={code}
                        value={code}
                        lang={code}
                        className="text-slate-900"
                    >
                        {name}
                    </option>
                ))}
            </select>
        </label>
    );
}
