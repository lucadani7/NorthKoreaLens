import { Play } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { useTranslation } from '@/hooks/use-translation';

type Props = {
    videoId: string;
    title: string;
    thumbnailUrl: string | null;
};

/** Player error codes meaning the owner does not allow the video on other websites. */
const EMBEDDING_DISABLED = [101, 150];

/**
 * A YouTube video that loads only when the reader presses play. Until then we show a
 * thumbnail: ours when the post has an image, otherwise YouTube's own from i.ytimg.com
 * (an image server that sets no cookies). The player itself comes from
 * youtube-nocookie.com, YouTube's privacy-enhanced mode, and only after the click. Some channels do not allow their
 * videos to be played on other websites: then we say so and link to the video on YouTube.
 */
export default function YouTubeEmbed({ videoId, title, thumbnailUrl }: Props) {
    const { t } = useTranslation();
    const [playing, setPlaying] = useState(false);
    const [blocked, setBlocked] = useState(false);
    const frame = useRef<HTMLIFrameElement>(null);

    useEffect(() => {
        if (!playing) {
            return;
        }

        // The player reports errors by postMessage once we say we are listening.
        const onMessage = (event: MessageEvent) => {
            if (
                event.source !== frame.current?.contentWindow ||
                typeof event.data !== 'string'
            ) {
                return;
            }

            try {
                const data = JSON.parse(event.data) as {
                    event?: string;
                    info?: unknown;
                };

                if (
                    data.event === 'onError' &&
                    EMBEDDING_DISABLED.includes(Number(data.info))
                ) {
                    setBlocked(true);
                }
            } catch {
                // Not a message from the player.
            }
        };

        window.addEventListener('message', onMessage);

        return () => window.removeEventListener('message', onMessage);
    }, [playing]);

    const listen = () =>
        frame.current?.contentWindow?.postMessage(
            JSON.stringify({
                event: 'listening',
                id: videoId,
                channel: 'widget',
            }),
            '*',
        );

    if (blocked) {
        return (
            <div className="flex aspect-video w-full flex-col items-center justify-center gap-4 bg-slate-900 px-6 text-center text-white">
                <p className="max-w-md text-sm leading-relaxed">
                    {t(
                        'The author of this video does not allow it to be played on other websites. You can watch it on YouTube.',
                    )}
                </p>
                <a
                    href={`https://www.youtube.com/watch?v=${videoId}`}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="rounded-full bg-nk-red-dark px-5 py-2 text-sm font-semibold hover:opacity-90"
                >
                    {t('Watch on YouTube')}
                </a>
            </div>
        );
    }

    if (playing) {
        const origin = encodeURIComponent(window.location.origin);

        return (
            <div className="aspect-video w-full bg-black">
                <iframe
                    ref={frame}
                    src={`https://www.youtube-nocookie.com/embed/${videoId}?autoplay=1&rel=0&enablejsapi=1&origin=${origin}`}
                    title={title}
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    referrerPolicy="strict-origin-when-cross-origin"
                    allowFullScreen
                    onLoad={listen}
                    className="size-full"
                />
            </div>
        );
    }

    return (
        <button
            type="button"
            onClick={() => setPlaying(true)}
            aria-label={t('Play video: :title', { title })}
            className="group relative block aspect-video w-full overflow-hidden bg-slate-900 text-white"
        >
            <img
                src={
                    thumbnailUrl ??
                    `https://i.ytimg.com/vi/${videoId}/hqdefault.jpg`
                }
                alt=""
                loading="lazy"
                referrerPolicy="no-referrer"
                className="size-full object-cover opacity-90 transition-opacity group-hover:opacity-100"
            />
            <span className="absolute inset-0 flex items-center justify-center">
                <span className="flex size-16 items-center justify-center rounded-full bg-nk-red-dark shadow-lg transition-transform group-hover:scale-110">
                    <Play
                        aria-hidden="true"
                        className="ml-1 size-7 fill-current"
                    />
                </span>
            </span>
            <span className="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 to-transparent px-4 pt-6 pb-2 text-left text-xs text-white/90">
                {t('The video plays from YouTube.')}
            </span>
        </button>
    );
}
