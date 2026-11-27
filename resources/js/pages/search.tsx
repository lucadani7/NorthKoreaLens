import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import PostCard from '@/components/feed/post-card';
import { Button } from '@/components/ui/button';
import { useTranslation } from '@/hooks/use-translation';
import { search as searchRoute } from '@/routes';

type Props = {
    query: string;
    filterLabels: {
        title: string;
        category: string;
        allCategories: string;
        sourceType: string;
        allSourceTypes: string;
        period: string;
        anyTime: string;
        last7Days: string;
        last30Days: string;
        last90Days: string;
        lastYear: string;
        customRange: string;
        from: string;
        to: string;
        language: string;
        allLanguages: string;
        clear: string;
        matchingResults: string;
        noMatchingResults: string;
    };
    filters: {
        category: string;
        source_type: string;
        period: string;
        from: string;
        to: string;
        language: string;
    };
    categories: App.Data.CategoryData[];
    sourceTypes: { value: string; label: string }[];
    languages: { code: string; name: string }[];
    posts: App.Data.PostData[];
    pagination: App.Data.PaginationData;
    /** Server time as ISO 8601. */
    now: string;
};

export default function SearchPage({
    query,
    filterLabels,
    filters,
    categories,
    sourceTypes,
    languages,
    posts,
    pagination,
    now: serverNow,
}: Props) {
    const { t } = useTranslation();
    const form = useForm({ q: query, ...filters });
    const now = new Date(serverNow);
    const hasFilters = Boolean(
        form.data.category ||
        form.data.source_type ||
        form.data.period ||
        form.data.language,
    );

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        form.get(searchRoute.url(), { preserveScroll: true });
    }

    return (
        <>
            <Head title={t('Search the archive')} />

            <main className="mx-auto flex w-full max-w-7xl flex-1 flex-col gap-6 px-4 py-8 md:px-6">
                <header className="max-w-3xl">
                    <h1 className="font-display text-3xl font-bold">
                        {t('Search the archive')}
                    </h1>

                    <form
                        id="archive-search-form"
                        role="search"
                        onSubmit={submit}
                        className="mt-5 w-full max-w-3xl"
                    >
                        <label className="sr-only" htmlFor="archive-search">
                            {t('Search published articles and posts')}
                        </label>
                        <div className="flex h-12 min-w-0 flex-1 rounded-lg border border-slate-300 bg-white focus-within:ring-2 focus-within:ring-nk-blue dark:border-slate-700 dark:bg-slate-900">
                            <input
                                id="archive-search"
                                type="search"
                                name="q"
                                maxLength={200}
                                value={form.data.q}
                                onChange={(event) =>
                                    form.setData('q', event.target.value)
                                }
                                placeholder={t(
                                    'Search published articles and posts',
                                )}
                                className="min-w-0 flex-1 bg-transparent px-4 text-base text-slate-900 placeholder:text-slate-500 focus-visible:outline-none dark:text-slate-100"
                            />
                        </div>
                        <fieldset className="mt-4">
                            <legend className="mb-2 text-sm font-semibold">
                                {filterLabels.title}
                            </legend>
                            <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                <label
                                    htmlFor="search-category"
                                    className="flex flex-col gap-1 text-sm font-medium"
                                >
                                    {filterLabels.category}
                                    <select
                                        id="search-category"
                                        name="category"
                                        value={form.data.category}
                                        onChange={(event) =>
                                            form.setData(
                                                'category',
                                                event.target.value,
                                            )
                                        }
                                        className="h-11 rounded-lg border border-slate-300 bg-white px-3 text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                                    >
                                        <option value="">
                                            {filterLabels.allCategories}
                                        </option>
                                        {categories.map((category) => (
                                            <option
                                                key={category.id}
                                                value={category.id}
                                            >
                                                {category.name}
                                            </option>
                                        ))}
                                    </select>
                                </label>

                                <label
                                    htmlFor="search-source-type"
                                    className="flex flex-col gap-1 text-sm font-medium"
                                >
                                    {filterLabels.sourceType}
                                    <select
                                        id="search-source-type"
                                        name="source_type"
                                        value={form.data.source_type}
                                        onChange={(event) =>
                                            form.setData(
                                                'source_type',
                                                event.target.value,
                                            )
                                        }
                                        className="h-11 rounded-lg border border-slate-300 bg-white px-3 text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                                    >
                                        <option value="">
                                            {filterLabels.allSourceTypes}
                                        </option>
                                        {sourceTypes.map((sourceType) => (
                                            <option
                                                key={sourceType.value}
                                                value={sourceType.value}
                                            >
                                                {sourceType.label}
                                            </option>
                                        ))}
                                    </select>
                                </label>

                                <label
                                    htmlFor="search-period"
                                    className="flex flex-col gap-1 text-sm font-medium"
                                >
                                    {filterLabels.period}
                                    <select
                                        id="search-period"
                                        name="period"
                                        value={form.data.period}
                                        onChange={(event) =>
                                            form.setData(
                                                'period',
                                                event.target.value,
                                            )
                                        }
                                        className="h-11 rounded-lg border border-slate-300 bg-white px-3 text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                                    >
                                        <option value="">
                                            {filterLabels.anyTime}
                                        </option>
                                        <option value="7d">
                                            {filterLabels.last7Days}
                                        </option>
                                        <option value="30d">
                                            {filterLabels.last30Days}
                                        </option>
                                        <option value="90d">
                                            {filterLabels.last90Days}
                                        </option>
                                        <option value="365d">
                                            {filterLabels.lastYear}
                                        </option>
                                        <option value="custom">
                                            {filterLabels.customRange}
                                        </option>
                                    </select>
                                </label>

                                <label
                                    htmlFor="search-language"
                                    className="flex flex-col gap-1 text-sm font-medium"
                                >
                                    {filterLabels.language}
                                    <select
                                        id="search-language"
                                        name="language"
                                        value={form.data.language}
                                        onChange={(event) =>
                                            form.setData(
                                                'language',
                                                event.target.value,
                                            )
                                        }
                                        className="h-11 rounded-lg border border-slate-300 bg-white px-3 text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                                    >
                                        <option value="">
                                            {filterLabels.allLanguages}
                                        </option>
                                        {languages.map((language) => (
                                            <option
                                                key={language.code}
                                                value={language.code}
                                            >
                                                {language.name}
                                            </option>
                                        ))}
                                    </select>
                                </label>
                            </div>
                            {form.data.period === 'custom' && (
                                <div className="mt-3 grid gap-3 sm:grid-cols-2">
                                    <label
                                        htmlFor="search-from"
                                        className="flex flex-col gap-1 text-sm font-medium"
                                    >
                                        {filterLabels.from}
                                        <input
                                            id="search-from"
                                            type="date"
                                            name="from"
                                            required
                                            value={form.data.from}
                                            onChange={(event) =>
                                                form.setData(
                                                    'from',
                                                    event.target.value,
                                                )
                                            }
                                            className="h-11 rounded-lg border border-slate-300 bg-white px-3 text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                                        />
                                    </label>
                                    <label
                                        htmlFor="search-to"
                                        className="flex flex-col gap-1 text-sm font-medium"
                                    >
                                        {filterLabels.to}
                                        <input
                                            id="search-to"
                                            type="date"
                                            name="to"
                                            required
                                            min={form.data.from}
                                            value={form.data.to}
                                            onChange={(event) =>
                                                form.setData(
                                                    'to',
                                                    event.target.value,
                                                )
                                            }
                                            className="h-11 rounded-lg border border-slate-300 bg-white px-3 text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100"
                                        />
                                    </label>
                                </div>
                            )}
                        </fieldset>
                        <div className="mt-4 flex flex-wrap items-center gap-3">
                            <Button type="submit" className="h-11">
                                {t('Search')}
                            </Button>
                            {hasFilters && (
                                <Link
                                    href={
                                        form.data.q
                                            ? `${searchRoute.url()}?q=${encodeURIComponent(form.data.q)}`
                                            : searchRoute.url()
                                    }
                                    className="inline-flex min-h-11 items-center text-sm font-semibold text-nk-blue underline-offset-4 hover:underline dark:text-sky-300"
                                >
                                    {filterLabels.clear}
                                </Link>
                            )}
                        </div>
                    </form>
                    {Object.entries(form.errors).map(([field, error]) => (
                        <p
                            key={field}
                            role="alert"
                            className="mt-2 text-sm text-red-700"
                        >
                            {error}
                        </p>
                    ))}
                </header>

                {query || hasFilters ? (
                    <section
                        aria-live="polite"
                        className="flex max-w-2xl flex-col gap-4"
                    >
                        <h2 className="font-display text-xl font-bold">
                            {query
                                ? t('Results for :query', { query })
                                : filterLabels.matchingResults}
                        </h2>
                        {posts.length > 0 ? (
                            <>
                                {posts.map((post) => (
                                    <PostCard
                                        key={post.id}
                                        post={post}
                                        now={now}
                                        showSourceTypeLabel
                                    />
                                ))}
                                <nav
                                    aria-label={t('Search the archive')}
                                    className="flex justify-between gap-4"
                                >
                                    {pagination.previousPageUrl ? (
                                        <Link
                                            href={pagination.previousPageUrl}
                                            preserveScroll
                                            className="flex min-h-11 items-center rounded-full border border-nk-blue px-5 text-sm font-semibold text-nk-blue hover:bg-nk-blue/10 dark:border-sky-300 dark:text-sky-300"
                                        >
                                            {t('Previous page')}
                                        </Link>
                                    ) : (
                                        <span />
                                    )}
                                    {pagination.nextPageUrl && (
                                        <Link
                                            href={pagination.nextPageUrl}
                                            preserveScroll
                                            className="flex min-h-11 items-center rounded-full border border-nk-blue px-5 text-sm font-semibold text-nk-blue hover:bg-nk-blue/10 dark:border-sky-300 dark:text-sky-300"
                                        >
                                            {t('Next page')}
                                        </Link>
                                    )}
                                </nav>
                            </>
                        ) : (
                            <p className="text-slate-700 dark:text-slate-300">
                                {query
                                    ? t('No results found for :query.', {
                                          query,
                                      })
                                    : filterLabels.noMatchingResults}
                            </p>
                        )}
                    </section>
                ) : (
                    <p className="max-w-3xl text-slate-700 dark:text-slate-300">
                        {t('Enter a word or phrase to search the archive.')}
                    </p>
                )}
            </main>
        </>
    );
}
