import { Head, Link } from '@inertiajs/react';
import CategoryNav from '@/components/feed/category-nav';
import PostCard from '@/components/feed/post-card';

type Props = {
    posts: App.Data.PostData[];
    pagination: App.Data.PaginationData;
    categories: App.Data.CategoryData[];
    activeCategory: string | null;
    /** Server time as ISO 8601. */
    now: string;
};

const pagerLink =
    'flex h-11 items-center rounded-full border border-nk-blue px-5 text-sm font-semibold text-nk-blue hover:bg-nk-blue/10 dark:border-sky-300 dark:text-sky-300';

export default function Feed({ posts, pagination, categories, activeCategory, now: serverNow }: Props) {
    // The server's clock keeps relative times identical in the SSR HTML and after hydration.
    const now = new Date(serverNow);
    const current = categories.find((category) => category.slug === activeCategory);

    return (
        <>
            <Head title={current?.name ?? 'Feed'} />

            <main className="mx-auto flex max-w-7xl flex-col gap-6 px-4 py-6 sm:px-6 lg:flex-row lg:items-start">
                <aside className="lg:w-60 lg:shrink-0">
                    <h2 className="mb-2 ml-3 hidden text-xs font-semibold tracking-widest text-slate-600 uppercase lg:block dark:text-slate-400">
                        Sections
                    </h2>
                    <CategoryNav categories={categories} active={activeCategory} />
                </aside>

                <section aria-label="Feed" className="flex min-w-0 max-w-2xl flex-1 flex-col gap-4">
                    {posts.length === 0 ? (
                        <p className="rounded-xl bg-white p-8 text-center text-slate-600 dark:bg-slate-900 dark:text-slate-400">
                            Nothing here yet. New articles arrive every hour.
                        </p>
                    ) : (
                        posts.map((post) => <PostCard key={post.id} post={post} now={now} />)
                    )}

                    {pagination.lastPage > 1 && (
                        <nav aria-label="Pages" className="flex items-center justify-between gap-4 py-2">
                            {pagination.previousPageUrl ? (
                                <Link href={pagination.previousPageUrl} className={pagerLink}>
                                    Newer
                                </Link>
                            ) : (
                                <span />
                            )}
                            <span className="text-sm text-slate-600 dark:text-slate-400">
                                Page {pagination.currentPage} of {pagination.lastPage}
                            </span>
                            {pagination.nextPageUrl ? (
                                <Link href={pagination.nextPageUrl} className={pagerLink}>
                                    Older
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
