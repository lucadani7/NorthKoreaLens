import type { ReactNode } from 'react';
import { Link } from '@inertiajs/react';
import { useEffect } from 'react';
import DisclaimerBanner from '@/components/disclaimer-banner';
import SiteHeader from '@/components/site-header';
import { useTranslation } from '@/hooks/use-translation';
import { editorialPolicy, howToHelp, sources, team } from '@/routes';

const FOOTER_LINKS = [
    { title: 'Team', href: team() },
    { title: 'Editorial policy', href: editorialPolicy() },
    { title: 'Sources', href: sources() },
    { title: 'How you can help', href: howToHelp() },
];

/** Layout for the public pages that guests can see: the feed and, later, the map and documentaries. */
export default function PublicLayout({ children }: { children: ReactNode }) {
    const { t, locale } = useTranslation();

    // The server sets <html lang> on the first load; keep it right after switching language.
    useEffect(() => {
        document.documentElement.lang = locale;
    }, [locale]);

    return (
        <div className="flex min-h-svh flex-col bg-slate-100 text-slate-900 dark:bg-slate-950 dark:text-slate-100">
            <SiteHeader />
            <DisclaimerBanner />

            <div className="flex-1">{children}</div>

            <footer className="border-t border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
                <nav
                    aria-label={t('About NK Lens')}
                    className="mx-auto flex max-w-7xl flex-wrap gap-x-2 gap-y-1 px-4 pt-4 sm:px-6"
                >
                    {FOOTER_LINKS.map(({ title, href }) => (
                        <Link
                            key={title}
                            href={href}
                            className="flex h-11 items-center rounded-lg px-2 text-sm font-semibold text-nk-blue hover:bg-nk-blue/10 dark:text-sky-300"
                        >
                            {t(title)}
                        </Link>
                    ))}
                </nav>
                <p className="mx-auto max-w-7xl px-6 pt-2 pb-5 text-sm text-slate-600 dark:text-slate-400">
                    © NK Lens ·{' '}
                    {t(
                        'We are not affiliated with the government of the DPRK and do not support the regime in Pyongyang.',
                    )}
                </p>
            </footer>
        </div>
    );
}
