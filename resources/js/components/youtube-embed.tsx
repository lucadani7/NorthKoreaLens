import { Play } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from '@/hooks/use-translation';

type Props = {
    videoId: string;
    title: string;
    thumbnailUrl: string | null;
};

/**
 * A YouTube video that loads only when the reader presses play. Until then we show the
 * thumbnail, so YouTube is not contacted and sets no cookies; the player then comes from
 * youtube-nocookie.com, YouTube's privacy-enhanced mode.
 */
export default function YouTubeEmbed({ videoId, title, thumbnailUrl }: Props) {
    const { t } = useTranslation();
    const [playing, setPlaying] = useState(false);

    if (playing) {
        return (
            <div className="aspect-video w-full bg-black">
                <iframe
                    src={`https://www.youtube-nocookie.com/embed/${videoId}?autoplay=1&rel=0`}
                    title={title}
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    referrerPolicy="strict-origin-when-cross-origin"
                    allowFullScreen
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
            {thumbnailUrl && (
                <img
                    src={thumbnailUrl}
                    alt=""
                    loading="lazy"
                    referrerPolicy="no-referrer"
                    className="size-full object-cover opacity-90 transition-opacity group-hover:opacity-100"
                />
            )}
            <span className="absolute inset-0 flex items-center justify-center">
                <span className="flex size-16 items-center justify-center rounded-full bg-nk-red-dark shadow-lg transition-transform group-hover:scale-110">
                    <Play
                        aria-hidden="true"
                        className="ml-1 size-7 fill-current"
                    />
                </span>
            </span>
            <span className="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 to-transparent px-4 pt-6 pb-2 text-left text-xs text-white/90">
                {t('The video is played from YouTube, which may set cookies.')}
            </span>
        </button>
    );
}
