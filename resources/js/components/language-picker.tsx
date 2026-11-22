import { router, usePage } from '@inertiajs/react';
import { Globe } from 'lucide-react';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import { update as updateLocale } from '@/routes/locale';

/** Language menu for the blue site header. Each language is named in its own language. */
export default function LanguagePicker({ className }: { className?: string }) {
    const { locales } = usePage().props;
    const { t, locale } = useTranslation();

    return (
        <label className={cn('relative flex h-11 items-center rounded-full bg-white/15 text-white', className)}>
            <span className="sr-only">{t('Language')}</span>
            <Globe aria-hidden="true" className="pointer-events-none absolute left-3 size-4" />
            <select
                value={locale}
                onChange={(event) =>
                    router.post(updateLocale.url(), { locale: event.target.value }, { preserveScroll: true })
                }
                className="h-11 cursor-pointer appearance-none rounded-full bg-transparent pr-4 pl-9 text-sm font-semibold text-white focus-visible:ring-2 focus-visible:ring-white focus-visible:outline-none"
            >
                {locales.map(({ code, name }) => (
                    <option key={code} value={code} lang={code} className="text-slate-900">
                        {name}
                    </option>
                ))}
            </select>
        </label>
    );
}
