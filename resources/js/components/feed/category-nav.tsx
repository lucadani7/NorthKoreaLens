import { Link } from '@inertiajs/react';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import { home } from '@/routes';

type Props = {
    categories: App.Data.CategoryData[];
    active: string | null;
};

function NavLink({ href, active, propaganda, children }: {
    href: ReturnType<typeof home>;
    active: boolean;
    propaganda?: boolean;
    children: string;
}) {
    return (
        <Link
            href={href}
            aria-current={active ? 'page' : undefined}
            className={cn(
                'flex min-h-11 items-center gap-3 rounded-lg px-3 text-[15px] whitespace-nowrap',
                active
                    ? 'bg-nk-blue/10 font-bold text-nk-blue-dark dark:bg-nk-blue/30 dark:text-white'
                    : 'font-medium text-slate-800 hover:bg-slate-200/70 dark:text-slate-200 dark:hover:bg-slate-800',
            )}
        >
            <span
                aria-hidden="true"
                className={cn('size-2.5 shrink-0 rounded-full', propaganda ? 'bg-nk-red-dark' : 'bg-nk-blue')}
            />
            {children}
        </Link>
    );
}

/** Sections of the feed: a column on large screens, a scrollable row on phones. */
export default function CategoryNav({ categories, active }: Props) {
    const { t } = useTranslation();

    return (
        <nav aria-label={t('Sections')} className="flex gap-1 overflow-x-auto lg:flex-col lg:overflow-visible">
            <NavLink href={home()} active={active === null}>
                {t('All')}
            </NavLink>
            {categories.map((category) => (
                <NavLink
                    key={category.id}
                    href={home({ query: { category: category.slug } })}
                    active={active === category.slug}
                    propaganda={category.isPropaganda}
                >
                    {category.name}
                </NavLink>
            ))}
        </nav>
    );
}
