import KeepHyphenated from '@/components/keep-hyphenated';
import { Head, Link } from '@inertiajs/react';
import { ExternalLink } from 'lucide-react';
import AskAi from '@/components/ask-ai';
import EditorialNote from '@/components/feed/editorial-note';
import SourceNotice from '@/components/feed/source-notice';
import YouTubeEmbed from '@/components/youtube-embed';
import PropagandaBanner from '@/components/feed/propaganda-banner';
import SectionsLayout from '@/components/sections-layout';
import { useTranslation } from '@/hooks/use-translation';
import { timeAgo } from '@/lib/time';
import { categoryBadge, isCuriosity } from '@/lib/category-colors';
import { cn } from '@/lib/utils';
import { warningBox } from '@/lib/warning-colors';
import { editorialPolicy, home } from '@/routes';
import { show } from '@/routes/posts';

type Props = {
    post: App.Data.PostData;
    /** Full text of a team post; null for articles from other sources. */
    body: string | null;
    /** Server time as ISO 8601. */
    now: string;
    /** Up to three other posts that share a topic. */
    related: App.Data.PostData[];
};

export default function Post({ post, body, now, related }: Props) {
    const { t, locale } = useTranslation();
    const author = post.source?.name ?? 'NK Lens';
    // Plain text only: paragraphs are separated by blank lines, nothing is rendered as HTML.
    // A paragraph that starts with "## " is shown as a section heading.
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

            <SectionsLayout active={post.category.slug} className="max-w-3xl">
                <article
                    className={cn(
                        'overflow-hidden rounded-xl bg-white shadow-sm dark:bg-slate-900',
                        post.isPropaganda && 'border-2 border-nk-red-dark',
                        isCuriosity(post.category.slug) &&
                            'border-t-4 border-nk-green',
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
                                <span
                                    className={cn(
                                        'rounded-full px-2.5 py-1 text-xs font-semibold',
                                        categoryBadge(post.category.slug),
                                    )}
                                >
                                    {post.category.name}
                                </span>
                            )}
                        </div>

                        {/* Team posts not yet translated into the reader's language. */}
                        {!post.source && post.contentLocale !== locale && (
                            <p
                                className={cn(
                                    'rounded-lg border-2 px-4 py-3 text-sm',
                                    warningBox,
                                )}
                            >
                                {t(
                                    'This post is not yet available in your language, so you are reading it in English.',
                                )}
                            </p>
                        )}

                        <h1
                            lang={post.contentLocale}
                            className="font-display text-3xl leading-tight font-bold sm:text-4xl"
                        >
                            <KeepHyphenated text={post.title} />
                        </h1>

                        {post.youtubeId ? (
                            <div className="overflow-hidden rounded-lg">
                                <YouTubeEmbed
                                    videoId={post.youtubeId}
                                    title={post.title}
                                    thumbnailUrl={post.imageUrl}
                                />
                            </div>
                        ) : (
                            post.imageUrl && (
                                <figure className="flex flex-col gap-1.5">
                                    <img
                                        src={post.imageUrl}
                                        alt=""
                                        referrerPolicy="no-referrer"
                                        className="aspect-video w-full rounded-lg bg-slate-200 object-cover dark:bg-slate-800"
                                    />
                                    {/* Free licences (e.g. CC BY-SA) require the author and licence next to the photo. */}
                                    {post.imageCredit && (
                                        <figcaption className="text-xs text-slate-600 dark:text-slate-400">
                                            {t('Photo:')} {post.imageCredit}
                                        </figcaption>
                                    )}
                                </figure>
                            )
                        )}

                        {paragraphs.length > 0 ? (
                            <div
                                lang={post.contentLocale}
                                className="flex flex-col gap-4 text-[17px] leading-relaxed text-slate-800 dark:text-slate-200"
                            >
                                {post.epigraph && (
                                    <p className="py-2 text-center font-bold whitespace-pre-line italic">
                                        <KeepHyphenated text={post.epigraph} />
                                    </p>
                                )}
                                {paragraphs.map((paragraph, index) =>
                                    paragraph.startsWith('## ') ? (
                                        <h2
                                            key={index}
                                            className="pt-3 text-xl font-bold text-slate-900 dark:text-slate-100"
                                        >
                                            <KeepHyphenated
                                                text={paragraph.slice(3).trim()}
                                            />
                                        </h2>
                                    ) : (
                                        <p
                                            key={index}
                                            className="whitespace-pre-line"
                                        >
                                            <KeepHyphenated text={paragraph} />
                                        </p>
                                    ),
                                )}
                            </div>
                        ) : (
                            post.excerpt && (
                                <p className="text-[17px] leading-relaxed text-slate-800 dark:text-slate-200">
                                    <KeepHyphenated text={post.excerpt} />
                                </p>
                            )
                        )}

                        {post.source?.notice && (
                            <SourceNotice notice={post.source.notice} />
                        )}

                        {post.editorialNote && (
                            <EditorialNote note={post.editorialNote} />
                        )}

                        {/* Not on posts from sources that forbid AI tools (e.g. Yonhap). */}
                        {!post.source?.forbidsAi && <AskAi post={post} />}

                        {post.originalUrl && (
                            <a
                                href={post.originalUrl}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="inline-flex h-11 w-fit items-center gap-2 rounded-full border border-nk-blue px-5 text-sm font-semibold text-nk-blue hover:bg-nk-blue/10 dark:border-sky-300 dark:text-sky-300"
                            >
                                {post.youtubeId
                                    ? t('Watch on YouTube')
                                    : t('Read on :source', { source: author })}
                                <ExternalLink
                                    aria-hidden="true"
                                    className="size-4"
                                />
                            </a>
                        )}

                        {post.source?.forbidsAi && (
                            <p className="text-xs text-slate-500 dark:text-slate-400">
                                {t(
                                    'At the request of :source, we do not use AI tools on this content.',
                                    { source: post.source.name },
                                )}
                            </p>
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

                {post.topics.length > 0 && (
                    <nav
                        aria-label={t('Topics')}
                        className="flex flex-wrap gap-2"
                    >
                        {post.topics.map((topic) => (
                            <Link
                                key={topic.id}
                                href={home({
                                    query: {
                                        category: post.category.slug,
                                        topic: topic.slug,
                                    },
                                })}
                                className="flex min-h-9 items-center rounded-full border border-slate-300 bg-white px-3 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800"
                            >
                                {topic.name}
                            </Link>
                        ))}
                    </nav>
                )}

                {related.length > 0 && (
                    <section
                        aria-label={t('More on this topic')}
                        className="rounded-xl bg-white p-5 shadow-sm dark:bg-slate-900"
                    >
                        <h2 className="font-display text-lg font-bold">
                            {t('More on this topic')}
                        </h2>
                        <ul className="mt-3 flex flex-col divide-y divide-slate-100 dark:divide-slate-800">
                            {related.map((item) => (
                                <li key={item.id}>
                                    <Link
                                        href={show(item.id)}
                                        lang={item.contentLocale}
                                        className="block py-3 font-semibold text-nk-blue hover:underline dark:text-sky-300"
                                    >
                                        {item.title}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </section>
                )}
            </SectionsLayout>
        </>
    );
}
