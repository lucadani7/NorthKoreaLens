import { Link } from '@inertiajs/react';
import { useTranslation } from '@/hooks/use-translation';
import { isCuriosity } from '@/lib/category-colors';
import { cn } from '@/lib/utils';
import { home } from '@/routes';

type Props = {
    category: string;
    topics: App.Data.TopicData[];
    active: string | null;
};

const chip =
    'flex min-h-10 items-center rounded-full border px-4 text-sm whitespace-nowrap transition-colors';

/** The themes of a section (e.g. Curiosities: History, Leaders…) as chips that filter the feed. */
export default function TopicFilter({ category, topics, active }: Props) {
    const { t } = useTranslation();

    const style = (selected: boolean) =>
        cn(
            chip,
            selected
                ? isCuriosity(category)
                    ? 'border-nk-green-dark bg-nk-green-dark font-semibold text-white'
                    : 'border-nk-blue bg-nk-blue font-semibold text-white'
                : 'border-slate-300 bg-white font-medium text-slate-800 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800',
        );

    return (
        <nav aria-label={t('Topics')} className="flex flex-wrap gap-2">
            <Link
                href={home({ query: { category } })}
                aria-current={active === null ? 'page' : undefined}
                preserveScroll
                className={style(active === null)}
            >
                {t('All')}
            </Link>
            {topics.map((topic) => (
                <Link
                    key={topic.id}
                    href={home({ query: { category, topic: topic.slug } })}
                    aria-current={active === topic.slug ? 'page' : undefined}
                    preserveScroll
                    className={style(active === topic.slug)}
                >
                    {topic.name}
                </Link>
            ))}
        </nav>
    );
}
