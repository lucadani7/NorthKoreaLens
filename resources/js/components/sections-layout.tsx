import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import CategoryNav from '@/components/feed/category-nav';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';

type Props = {
    /** Slug of the section to highlight, e.g. the one a post belongs to. */
    active?: string | null;
    children: ReactNode;
    /** Classes for the column beside the sections, e.g. a narrower width for articles. */
    className?: string;
};

/**
 * Every public page: the feed sections in a column on large screens (on phones they are in
 * the header menu), and the page beside them.
 */
export default function SectionsLayout({
    active = null,
    children,
    className,
}: Props) {
    const { t } = useTranslation();
    const { sections } = usePage().props;

    return (
        <main className="mx-auto flex max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 lg:flex-row lg:items-start">
            <aside className="hidden lg:sticky lg:top-6 lg:block lg:w-60 lg:shrink-0">
                <h2 className="mb-2 ml-3 text-xs font-semibold tracking-widest text-slate-600 uppercase dark:text-slate-400">
                    {t('Sections')}
                </h2>
                <CategoryNav categories={sections} active={active} />
            </aside>

            <div
                className={cn('flex min-w-0 flex-1 flex-col gap-4', className)}
            >
                {children}
            </div>
        </main>
    );
}
