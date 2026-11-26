import KeepHyphenated from '@/components/keep-hyphenated';
import { Link } from '@inertiajs/react';
import { ExternalLink, Sparkles } from 'lucide-react';
import EditorialNote from '@/components/feed/editorial-note';
import SourceNotice from '@/components/feed/source-notice';
import YouTubeEmbed from '@/components/youtube-embed';
import PropagandaBanner from '@/components/feed/propaganda-banner';
import { useTranslation } from '@/hooks/use-translation';
import { categoryBadge, isCuriosity } from '@/lib/category-colors';
import { cn } from '@/lib/utils';
import { timeAgo } from '@/lib/time';
import { editorialPolicy } from '@/routes';
import { show } from '@/routes/posts';

type Props = {
    post: App.Data.PostData;
    /** Fixed "now" from the page, so server and browser print the same relative times. */
    now: Date;
    showSourceTypeLabel?: boolean;
};

const SOURCE_TYPE_LABELS: Record<App.Enums.SourceType, string> = {
    independent: 'Independent newsrooms',
    analysis: 'Analysis and research',
    testimony: 'Escapee testimonies',
    travel_vlog: 'Travel vloggers',
    state_media: 'North Korean state media',
};

function initials(name: string): string {
    return name
        .split(/\s+/)
        .map((word) => word[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();
}

export default function PostCard({
    post,
    now,
    showSourceTypeLabel = false,
}: Props) {
    const { t, locale } = useTranslation();
    const author = post.source?.name ?? 'NK Lens';

    return (
        <article
            className={cn(
                'overflow-hidden rounded-xl bg-white shadow-sm dark:bg-slate-900',
                post.isPropaganda && 'border-2 border-nk-red-dark',
                isCuriosity(post.category.slug) && 'border-t-4 border-nk-green',
            )}
        >
            {post.isPropaganda && <PropagandaBanner />}

            <div className="flex items-center gap-3 px-5 pt-4">
                <div
                    aria-hidden="true"
                    className={cn(
                        'flex size-11 shrink-0 items-center justify-center rounded-lg text-sm font-bold',
                        post.isPropaganda
                            ? 'bg-red-50 text-nk-red-dark dark:bg-red-950 dark:text-red-200'
                            : isCuriosity(post.category.slug)
                              ? 'bg-nk-green/10 text-nk-green-dark dark:bg-nk-green/30 dark:text-white'
                              : 'bg-nk-blue/10 text-nk-blue dark:bg-nk-blue/30 dark:text-white',
                    )}
                >
                    {initials(author)}
                </div>
                <div className="min-w-0 flex-1">
                    <div className="truncate text-[15px] font-semibold">
                        {author}
                    </div>
                    {showSourceTypeLabel && post.source && (
                        <div className="text-xs text-slate-600 dark:text-slate-400">
                            {t(SOURCE_TYPE_LABELS[post.source.type])}
                        </div>
                    )}
                    {post.publishedAt && (
                        <time
                            dateTime={post.publishedAt}
                            className="text-[13px] text-slate-600 dark:text-slate-400"
                        >
                            {timeAgo(post.publishedAt, locale, now)}
                        </time>
                    )}
                </div>
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

            <div className="px-5 pt-3 pb-4">
                <h2 className="font-display text-xl leading-snug font-bold">
                    <Link
                        href={show(post.id)}
                        className="hover:text-nk-blue hover:underline dark:hover:text-sky-300"
                    >
                        <KeepHyphenated text={post.title} />
                    </Link>
                </h2>
                {post.excerpt && (
                    <p className="mt-2 text-[15px] leading-relaxed text-slate-700 dark:text-slate-300">
                        <KeepHyphenated text={post.excerpt} />
                    </p>
                )}
                {post.source?.notice && (
                    <div className="mt-4">
                        <SourceNotice notice={post.source.notice} />
                    </div>
                )}
                {post.editorialNote && (
                    <div className="mt-4">
                        <EditorialNote note={post.editorialNote} />
                    </div>
                )}
            </div>

            {post.youtubeId ? (
                <YouTubeEmbed
                    videoId={post.youtubeId}
                    title={post.title}
                    thumbnailUrl={post.imageUrl}
                />
            ) : (
                post.imageUrl && (
                    <figure>
                        <img
                            src={post.imageUrl}
                            alt=""
                            loading="lazy"
                            referrerPolicy="no-referrer"
                            className="aspect-video w-full bg-slate-200 object-cover dark:bg-slate-800"
                        />
                        {post.imageCredit && (
                            <figcaption className="px-5 pt-1.5 text-xs text-slate-600 dark:text-slate-400">
                                {t('Photo:')} {post.imageCredit}
                            </figcaption>
                        )}
                    </figure>
                )
            )}

            {(post.originalUrl || !post.source?.forbidsAi) && (
                <div className="flex flex-wrap items-center gap-1 border-t border-slate-100 px-3 py-2 dark:border-slate-800">
                    {post.originalUrl && (
                        <a
                            href={post.originalUrl}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex h-11 items-center gap-2 rounded-lg px-3 text-sm font-semibold text-nk-blue hover:bg-nk-blue/10 dark:text-sky-300"
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
                    {/* Opens the post at its "Ask an AI assistant" box; not for sources that forbid AI. */}
                    {!post.source?.forbidsAi && (
                        <Link
                            href={`${show.url(post.id)}#ask-ai`}
                            className="inline-flex h-11 items-center gap-2 rounded-lg px-3 text-sm font-semibold text-violet-700 hover:bg-violet-100 dark:text-violet-300 dark:hover:bg-violet-900/40"
                        >
                            <Sparkles aria-hidden="true" className="size-4" />
                            {t('Ask AI')}
                        </Link>
                    )}
                </div>
            )}
            {post.source?.forbidsAi && (
                <p className="border-t border-slate-100 px-5 py-3 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">
                    {t(
                        'At the request of :source, we do not use AI tools on this content.',
                        { source: post.source.name },
                    )}
                </p>
            )}

            {post.linkWithheld && (
                <p className="border-t border-slate-100 px-5 py-3 text-sm text-slate-600 dark:border-slate-800 dark:text-slate-400">
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
        </article>
    );
}
