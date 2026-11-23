import { Link, usePage } from '@inertiajs/react';
import CategoryNav from '@/components/feed/category-nav';
import type { LucideIcon } from 'lucide-react';
import { Check, Menu, Monitor, Moon, Sun } from 'lucide-react';
import { useState } from 'react';
import LanguagePicker from '@/components/language-picker';
import NkLensLogo from '@/components/nk-lens-logo';
import ThemeToggle from '@/components/theme-toggle';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import type { Appearance } from '@/hooks/use-appearance';
import { useAppearance } from '@/hooks/use-appearance';
import { useTranslation } from '@/hooks/use-translation';
import { SITE_LINKS } from '@/lib/site-links';
import { cn } from '@/lib/utils';
import { dashboard, home, login, register } from '@/routes';

/** Thin stripe that recalls the flag without copying it. */
function FlagStripe() {
    return (
        <div aria-hidden="true" className="flex flex-col">
            <div className="h-1 bg-white" />
            <div className="h-1.5 bg-nk-red" />
            <div className="h-1 bg-white" />
            <div className="h-0.5 bg-nk-blue" />
        </div>
    );
}

/** The path of the current page, without the query string. */
function useCurrentPath(): string {
    return usePage().url.split(/[?#]/)[0];
}

/** Dashboard for members; log in and sign up for visitors. */
function AccountLinks({
    stacked = false,
    onNavigate,
}: {
    stacked?: boolean;
    onNavigate?: () => void;
}) {
    const { auth } = usePage().props;
    const { t } = useTranslation();

    const primary = cn(
        'flex h-11 items-center justify-center rounded-full px-4 font-semibold',
        stacked
            ? 'bg-nk-blue text-white hover:bg-nk-blue-dark'
            : 'bg-white text-nk-blue hover:bg-white/90',
    );
    const secondary = cn(
        'flex h-11 items-center justify-center rounded-full px-4 font-semibold',
        stacked
            ? 'border border-border text-foreground hover:bg-muted'
            : 'hover:bg-white/15',
    );

    if (auth.user) {
        return (
            <Link href={dashboard()} className={primary} onClick={onNavigate}>
                {t('Dashboard')}
            </Link>
        );
    }

    return (
        <>
            <Link href={login()} className={secondary} onClick={onNavigate}>
                {t('Log in')}
            </Link>
            <Link href={register()} className={primary} onClick={onNavigate}>
                {t('Join NK Lens')}
            </Link>
        </>
    );
}

const THEMES: { value: Appearance; icon: LucideIcon; label: string }[] = [
    { value: 'light', icon: Sun, label: 'Light mode' },
    { value: 'dark', icon: Moon, label: 'Dark mode' },
    { value: 'system', icon: Monitor, label: 'Use system setting' },
];

/** Phones and tablets: feed sections, account links and the colour theme in a sheet that slides up from the bottom. */
function MobileMenu() {
    const { t } = useTranslation();
    const { appearance, updateAppearance } = useAppearance();
    const { sections, activeCategory } = usePage<{
        activeCategory?: string | null;
    }>().props;
    const [open, setOpen] = useState(false);
    const path = useCurrentPath();

    return (
        <Sheet open={open} onOpenChange={setOpen}>
            <SheetTrigger asChild>
                <button
                    type="button"
                    aria-label={t('Menu')}
                    className="flex size-11 items-center justify-center rounded-full bg-white/15 text-white hover:bg-white/25 focus-visible:ring-2 focus-visible:ring-white focus-visible:outline-none"
                >
                    <Menu aria-hidden="true" className="size-5" />
                </button>
            </SheetTrigger>
            <SheetContent
                side="bottom"
                aria-describedby={undefined}
                className="max-h-[85dvh] gap-0 overflow-y-auto rounded-t-2xl pb-[max(1rem,env(safe-area-inset-bottom))]"
            >
                <div
                    aria-hidden="true"
                    className="mx-auto mt-3 h-1.5 w-10 rounded-full bg-muted-foreground/30"
                />
                <SheetHeader className="px-5 pt-3 pb-2">
                    <SheetTitle className="font-display text-lg font-bold">
                        {t('Menu')}
                    </SheetTitle>
                </SheetHeader>

                <CategoryNav
                    categories={sections}
                    active={activeCategory ?? null}
                    onNavigate={() => setOpen(false)}
                    className="px-2 pb-4"
                />

                <nav
                    aria-label={t('About NK Lens')}
                    className="grid gap-1 border-t border-border px-2 py-3"
                >
                    {SITE_LINKS.map(({ title, href }) => (
                        <Link
                            key={title}
                            href={href}
                            onClick={() => setOpen(false)}
                            aria-current={
                                path === href.url ? 'page' : undefined
                            }
                            className={cn(
                                'flex min-h-11 items-center rounded-lg px-3 text-[15px]',
                                path === href.url
                                    ? 'bg-nk-blue/10 font-bold text-nk-blue-dark dark:bg-nk-blue/30 dark:text-white'
                                    : 'font-medium text-foreground hover:bg-muted',
                            )}
                        >
                            {t(title)}
                        </Link>
                    ))}
                </nav>

                <div className="grid gap-2 border-t border-border px-5 py-4 text-sm">
                    <AccountLinks stacked onNavigate={() => setOpen(false)} />
                </div>

                <section className="border-t border-border px-3 pt-4">
                    <h2 className="px-2 pb-2 text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                        {t('Colour theme')}
                    </h2>
                    <ul>
                        {THEMES.map(({ value, icon: Icon, label }) => {
                            const selected = appearance === value;

                            return (
                                <li key={value}>
                                    <button
                                        type="button"
                                        aria-pressed={selected}
                                        onClick={() => updateAppearance(value)}
                                        className={cn(
                                            'flex min-h-12 w-full items-center gap-3 rounded-xl px-4 text-left text-base transition-colors',
                                            selected
                                                ? 'bg-nk-blue font-semibold text-white'
                                                : 'text-foreground hover:bg-muted active:bg-muted',
                                        )}
                                    >
                                        <Icon
                                            aria-hidden="true"
                                            className="size-5"
                                        />
                                        <span className="flex-1">
                                            {t(label)}
                                        </span>
                                        {selected && (
                                            <Check
                                                aria-hidden="true"
                                                className="size-5"
                                            />
                                        )}
                                    </button>
                                </li>
                            );
                        })}
                    </ul>
                </section>
            </SheetContent>
        </Sheet>
    );
}

/**
 * Phones and tablets: one row with the logo, the language and a menu button.
 * From 1024 px, where the sections sit beside the feed: the logo, then language,
 * theme and account links side by side.
 */
export default function SiteHeader() {
    const { name } = usePage().props;
    const { t } = useTranslation();
    const path = useCurrentPath();

    return (
        <header className="bg-nk-blue text-white">
            <div className="mx-auto flex min-h-16 max-w-7xl items-center gap-2 px-4 py-2 md:gap-6 md:px-6">
                <Link
                    href={home()}
                    className="mr-auto flex min-w-0 items-center gap-2.5 text-white"
                >
                    <NkLensLogo className="size-8 shrink-0" />
                    <span className="truncate font-display text-xl font-bold tracking-tight">
                        {name}
                    </span>
                </Link>

                <LanguagePicker compact />

                <ThemeToggle className="hidden lg:flex" />

                <nav className="hidden items-center gap-2 text-sm lg:flex">
                    <AccountLinks />
                </nav>

                <div className="lg:hidden">
                    <MobileMenu />
                </div>
            </div>
            {/* From 1024 px: the pages about NK Lens, on a second row. On phones they are in the menu. */}
            <nav
                aria-label={t('About NK Lens')}
                className="mx-auto hidden max-w-7xl gap-1 px-4 pb-2 md:px-6 lg:flex"
            >
                {SITE_LINKS.map(({ title, href }) => (
                    <Link
                        key={title}
                        href={href}
                        aria-current={path === href.url ? 'page' : undefined}
                        className={cn(
                            'flex h-9 items-center rounded-full px-3 text-sm font-semibold',
                            path === href.url
                                ? 'bg-white text-nk-blue'
                                : 'text-white/90 hover:bg-white/15 hover:text-white',
                        )}
                    >
                        {t(title)}
                    </Link>
                ))}
            </nav>
            <FlagStripe />
        </header>
    );
}
