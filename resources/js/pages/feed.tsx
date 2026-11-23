import { Head, Link } from '@inertiajs/react';
import CategoryNav from '@/components/feed/category-nav';
import PostCard from '@/components/feed/post-card';
import TopicFilter from '@/components/feed/topic-filter';
import { useTranslation } from '@/hooks/use-translation';

type Props = {
    posts: App.Data.PostData[];
    pagination: App.Data.PaginationData;
    categories: App.Data.CategoryData[];
    activeCategory: string | null;
    /** Topics with published posts in this section; empty outside a section. */
    topics: App.Data.TopicData[];
    activeTopic: string | null;
    /** Server time as ISO 8601. */
    now: string;
};

/** A short note at the top of a section, saying what it holds and how often it changes. */
const SECTION_NOTES: Record<string, string> = {
    'korea-now':
        'Videos about North Korea from KOREA NOW, the English YouTube channel of the South Korean news agency Yonhap. We check the channel every day; videos about other subjects are left out.',
};

/** What an empty section says, when it has something more useful to say than the default. */
const EMPTY_NOTES: Record<string, string> = {
    'korea-now':
        'No videos about North Korea yet. We check the channel every day, and new ones will appear here.',
};

const pagerLink =
    'flex h-11 items-center rounded-full border border-nk-blue px-5 text-sm font-semibold text-nk-blue hover:bg-nk-blue/10 dark:border-sky-300 dark:text-sky-300';

export default function Feed({
    posts,
    pagination,
    categories,
    activeCategory,
    topics,
    activeTopic,
    now: serverNow,
}: Props) {
    // The server's clock keeps relative times identical in the SSR HTML and after hydration.
    const now = new Date(serverNow);
    const { t } = useTranslation();
    const current = categories.find(
        (category) => category.slug === activeCategory,
    );
    const sectionNote = activeCategory ? SECTION_NOTES[activeCategory] : null;
    const emptyNote =
        (activeCategory && EMPTY_NOTES[activeCategory]) ??
        'Nothing here yet. New articles arrive every hour.';

    return (
        <>
            <Head title={current?.name ?? t('Feed')} />

            <main className="mx-auto flex max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 lg:flex-row lg:items-start">
                {/* On phones the sections are in the header menu; the current one is named here. */}
                <h1 className="font-display text-2xl font-bold lg:hidden">
                    {current?.name ?? t('All')}
                </h1>

                <aside className="hidden lg:block lg:w-60 lg:shrink-0">
                    <h2 className="mb-2 ml-3 text-xs font-semibold tracking-widest text-slate-600 uppercase dark:text-slate-400">
                        {t('Sections')}
                    </h2>
                    <CategoryNav
                        categories={categories}
                        active={activeCategory}
                    />
                </aside>

                <section
                    aria-label={t('Feed')}
                    className="flex max-w-2xl min-w-0 flex-1 flex-col gap-4"
                >
                    {sectionNote && (
                        <p className="rounded-xl border-l-4 border-nk-blue bg-white px-5 py-4 text-[15px] leading-relaxed text-slate-700 dark:bg-slate-900 dark:text-slate-300">
                            {t(sectionNote)}
                        </p>
                    )}

                    {activeCategory && topics.length > 0 && (
                        <TopicFilter
                            category={activeCategory}
                            topics={topics}
                            active={activeTopic}
                        />
                    )}

                    {posts.length === 0 ? (
                        <p className="rounded-xl bg-white p-8 text-center text-slate-600 dark:bg-slate-900 dark:text-slate-400">
                            {t(emptyNote)}
                        </p>
                    ) : (
                        posts.map((post) => (
                            <PostCard key={post.id} post={post} now={now} />
                        ))
                    )}

                    {pagination.lastPage > 1 && (
                        <nav
                            aria-label={t('Pages')}
                            className="flex items-center justify-between gap-4 py-2"
                        >
                            {pagination.previousPageUrl ? (
                                <Link
                                    href={pagination.previousPageUrl}
                                    className={pagerLink}
                                >
                                    {t('Newer')}
                                </Link>
                            ) : (
                                <span />
                            )}
                            <span className="text-sm text-slate-600 dark:text-slate-400">
                                {t('Page :current of :last', {
                                    current: pagination.currentPage,
                                    last: pagination.lastPage,
                                })}
                            </span>
                            {pagination.nextPageUrl ? (
                                <Link
                                    href={pagination.nextPageUrl}
                                    className={pagerLink}
                                >
                                    {t('Older')}
                                </Link>
                            ) : (
                                <span />
                            )}
                        </nav>
                    )}
                </section>
            </main>
        </>
    );
}
