import { Link } from '@inertiajs/react';
import { ExternalLink } from 'lucide-react';
import EditorialNote from '@/components/feed/editorial-note';
import PropagandaBanner from '@/components/feed/propaganda-banner';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import { timeAgo } from '@/lib/time';
import { editorialPolicy } from '@/routes';
import { show } from '@/routes/posts';

type Props = {
    post: App.Data.PostData;
    /** Fixed "now" from the page, so server and browser print the same relative times. */
    now: Date;
};

function initials(name: string): string {
    return name
        .split(/\s+/)
        .map((word) => word[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();
}

export default function PostCard({ post, now }: Props) {
    const { t, locale } = useTranslation();
    const author = post.source?.name ?? 'NK Lens';

    return (
        <article
            className={cn(
                'overflow-hidden rounded-xl bg-white shadow-sm dark:bg-slate-900',
                post.isPropaganda && 'border-2 border-nk-red-dark',
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
                            : 'bg-nk-blue/10 text-nk-blue dark:bg-nk-blue/30 dark:text-white',
                    )}
                >
                    {initials(author)}
                </div>
                <div className="min-w-0 flex-1">
                    <div className="truncate text-[15px] font-semibold">
                        {author}
                    </div>
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
                    <span className="rounded-full bg-nk-blue/10 px-2.5 py-1 text-xs font-semibold text-nk-blue-dark dark:bg-nk-blue/30 dark:text-white">
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
                        {post.title}
                    </Link>
                </h2>
                {post.excerpt && (
                    <p className="mt-2 text-[15px] leading-relaxed text-slate-700 dark:text-slate-300">
                        {post.excerpt}
                    </p>
                )}
                {post.editorialNote && (
                    <div className="mt-4">
                        <EditorialNote note={post.editorialNote} />
                    </div>
                )}
            </div>

            {post.imageUrl && (
                <img
                    src={post.imageUrl}
                    alt=""
                    loading="lazy"
                    referrerPolicy="no-referrer"
                    className="aspect-video w-full bg-slate-200 object-cover dark:bg-slate-800"
                />
            )}

            {post.originalUrl && (
                <div className="border-t border-slate-100 px-3 py-2 dark:border-slate-800">
                    <a
                        href={post.originalUrl}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="inline-flex h-11 items-center gap-2 rounded-lg px-3 text-sm font-semibold text-nk-blue hover:bg-nk-blue/10 dark:text-sky-300"
                    >
                        {t('Read on :source', { source: author })}
                        <ExternalLink aria-hidden="true" className="size-4" />
                    </a>
                </div>
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
