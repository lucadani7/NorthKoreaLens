import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, ExternalLink } from 'lucide-react';
import EditorialNote from '@/components/feed/editorial-note';
import PropagandaBanner from '@/components/feed/propaganda-banner';
import { useTranslation } from '@/hooks/use-translation';
import { timeAgo } from '@/lib/time';
import { cn } from '@/lib/utils';
import { editorialPolicy, home } from '@/routes';

type Props = {
    post: App.Data.PostData;
    /** Full text of a team post; null for articles from other sources. */
    body: string | null;
    /** Server time as ISO 8601. */
    now: string;
};

export default function Post({ post, body, now }: Props) {
    const { t, locale } = useTranslation();
    const author = post.source?.name ?? 'NK Lens';
    // Plain text only: paragraphs are separated by blank lines, nothing is rendered as HTML.
    const paragraphs = (body ?? '')
        .split(/\n{2,}/)
        .filter((paragraph) => paragraph.trim() !== '');

    return (
        <>
            <Head title={post.title}>
                {post.excerpt && (
                    <meta name="description" content={post.excerpt} />
                )}
            </Head>

            <main className="mx-auto flex max-w-3xl flex-col gap-4 px-4 py-6 sm:px-6">
                <Link
                    href={home()}
                    className="inline-flex h-11 w-fit items-center gap-2 rounded-lg px-2 text-sm font-semibold text-nk-blue hover:bg-nk-blue/10 dark:text-sky-300"
                >
                    <ArrowLeft aria-hidden="true" className="size-4" />
                    {t('Back to the feed')}
                </Link>

                <article
                    className={cn(
                        'overflow-hidden rounded-xl bg-white shadow-sm dark:bg-slate-900',
                        post.isPropaganda && 'border-2 border-nk-red-dark',
                    )}
                >
                    {post.isPropaganda && <PropagandaBanner />}

                    <div className="flex flex-col gap-5 p-6 sm:p-8">
                        <div className="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-slate-600 dark:text-slate-400">
                            <span className="font-semibold text-slate-900 dark:text-slate-100">
                                {author}
                            </span>
                            {post.publishedAt && (
                                <time dateTime={post.publishedAt}>
                                    {timeAgo(
                                        post.publishedAt,
                                        locale,
                                        new Date(now),
                                    )}
                                </time>
                            )}
                            {!post.isPropaganda && (
                                <span className="rounded-full bg-nk-blue/10 px-2.5 py-1 text-xs font-semibold text-nk-blue-dark dark:bg-nk-blue/30 dark:text-white">
                                    {post.category.name}
                                </span>
                            )}
                        </div>

                        <h1 className="font-display text-3xl leading-tight font-bold sm:text-4xl">
                            {post.title}
                        </h1>

                        {post.imageUrl && (
                            <img
                                src={post.imageUrl}
                                alt=""
                                referrerPolicy="no-referrer"
                                className="aspect-video w-full rounded-lg bg-slate-200 object-cover dark:bg-slate-800"
                            />
                        )}

                        {paragraphs.length > 0 ? (
                            <div className="flex flex-col gap-4 text-[17px] leading-relaxed text-slate-800 dark:text-slate-200">
                                {paragraphs.map((paragraph, index) => (
                                    <p
                                        key={index}
                                        className="whitespace-pre-line"
                                    >
                                        {paragraph}
                                    </p>
                                ))}
                            </div>
                        ) : (
                            post.excerpt && (
                                <p className="text-[17px] leading-relaxed text-slate-800 dark:text-slate-200">
                                    {post.excerpt}
                                </p>
                            )
                        )}

                        {post.editorialNote && (
                            <EditorialNote note={post.editorialNote} />
                        )}

                        {post.originalUrl && (
                            <a
                                href={post.originalUrl}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="inline-flex h-11 w-fit items-center gap-2 rounded-full border border-nk-blue px-5 text-sm font-semibold text-nk-blue hover:bg-nk-blue/10 dark:border-sky-300 dark:text-sky-300"
                            >
                                {t('Read on :source', { source: author })}
                                <ExternalLink
                                    aria-hidden="true"
                                    className="size-4"
                                />
                            </a>
                        )}

                        {post.linkWithheld && (
                            <p className="text-sm text-slate-600 dark:text-slate-400">
                                {t(
                                    'In South Korea the law restricts access to North Korean websites, so we do not link to the original.',
                                )}{' '}
                                <Link
                                    href={editorialPolicy()}
                                    className="font-semibold text-nk-blue underline underline-offset-4 dark:text-sky-300"
                                >
                                    {t('Learn more')}
                                </Link>
                            </p>
                        )}
                    </div>
                </article>
            </main>
        </>
    );
}
