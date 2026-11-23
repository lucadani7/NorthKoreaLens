import { Link } from '@inertiajs/react';
import { useTranslation } from '@/hooks/use-translation';
import { isCuriosity } from '@/lib/category-colors';
import { cn } from '@/lib/utils';
import { home } from '@/routes';

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
    onNavigate,
    children,
}: {
    href: ReturnType<typeof home>;
    active: boolean;
    propaganda?: boolean;
    curiosity?: boolean;
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

    return (
        <nav
            aria-label={t('Sections')}
            className={cn('flex flex-col gap-1', className)}
        >
            <NavLink
                href={home()}
                active={active === null}
                onNavigate={onNavigate}
            >
                {t('All')}
            </NavLink>
            {categories.map((category) => (
                <NavLink
                    key={category.id}
                    href={home({ query: { category: category.slug } })}
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
