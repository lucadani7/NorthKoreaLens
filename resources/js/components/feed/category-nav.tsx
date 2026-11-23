import { Link, usePage } from '@inertiajs/react';
import { useTranslation } from '@/hooks/use-translation';
import { isCuriosity } from '@/lib/category-colors';
import { cn } from '@/lib/utils';
import { about, home } from '@/routes';

/** The section the home page shows (Category::DEFAULT). */
export const DEFAULT_SECTION = 'news';

type Props = {
    categories: App.Data.CategoryData[];
    active: string | null;
    /** Called after a section is picked, e.g. to close the menu it sits in. */
    onNavigate?: () => void;
    className?: string;
};

function NavLink({
    href,
    active,
    propaganda,
    curiosity,
    neutral,
    onNavigate,
    children,
}: {
    href: ReturnType<typeof home>;
    active: boolean;
    propaganda?: boolean;
    curiosity?: boolean;
    /** A page about NK Lens rather than a section of the feed: grey dot. */
    neutral?: boolean;
    onNavigate?: () => void;
    children: string;
}) {
    return (
        <Link
            href={href}
            aria-current={active ? 'page' : undefined}
            onClick={onNavigate}
            className={cn(
                'flex min-h-11 items-center gap-3 rounded-lg px-3 text-[15px] whitespace-nowrap',
                active
                    ? 'bg-nk-blue/10 font-bold text-nk-blue-dark dark:bg-nk-blue/30 dark:text-white'
                    : 'font-medium text-slate-800 hover:bg-slate-200/70 dark:text-slate-200 dark:hover:bg-slate-800',
            )}
        >
            <span
                aria-hidden="true"
                className={cn(
                    'size-2.5 shrink-0 rounded-full',
                    propaganda
                        ? 'bg-nk-red-dark'
                        : curiosity
                          ? 'bg-nk-green'
                          : neutral
                            ? 'bg-slate-400'
                            : 'bg-nk-blue',
                )}
            />
            {children}
        </Link>
    );
}

/** Sections of the feed, as a column: beside the feed on large screens, in the menu on phones. */
export default function CategoryNav({
    categories,
    active,
    onNavigate,
    className,
}: Props) {
    const { t } = useTranslation();
    const path = usePage().url.split(/[?#]/)[0];

    return (
        <nav
            aria-label={t('Sections')}
            className={cn('flex flex-col gap-1', className)}
        >
            <NavLink
                href={about()}
                active={path === about().url}
                neutral
                onNavigate={onNavigate}
            >
                {t('Who we are')}
            </NavLink>
            {categories.map((category) => (
                <NavLink
                    key={category.id}
                    href={
                        // The home page is the News section: no "All" view.
                        category.slug === DEFAULT_SECTION
                            ? home()
                            : home({ query: { category: category.slug } })
                    }
                    active={active === category.slug}
                    propaganda={category.isPropaganda}
                    curiosity={isCuriosity(category.slug)}
                    onNavigate={onNavigate}
                >
                    {category.name}
                </NavLink>
            ))}
        </nav>
    );
}
