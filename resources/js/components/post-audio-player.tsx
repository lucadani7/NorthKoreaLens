import { useEffect, useRef } from 'react';

type Props = {
    postId: number;
    audioUrl: string;
    title: string;
    category: string;
    imageUrl: string | null;
    label: string;
};

export default function PostAudioPlayer({
    postId,
    audioUrl,
    title,
    category,
    imageUrl,
    label,
}: Props) {
    const audioRef = useRef<HTMLAudioElement>(null);

    useEffect(() => {
        if (
            typeof navigator === 'undefined' ||
            !('mediaSession' in navigator) ||
            typeof MediaMetadata === 'undefined'
        ) {
            return;
        }

        const mediaSession = navigator.mediaSession;
        const registeredActions: MediaSessionAction[] = [];

        try {
            mediaSession.metadata = new MediaMetadata({
                title,
                artist: 'NK Lens',
                album: category || 'NK Lens',
                artwork: imageUrl ? [{ src: imageUrl }] : [],
            });
        } catch {
            return;
        }

        const handlers: Partial<
            Record<MediaSessionAction, MediaSessionActionHandler>
        > = {
            play: () => {
                void audioRef.current?.play().catch(() => undefined);
            },
            pause: () => audioRef.current?.pause(),
        };

        for (const [action, handler] of Object.entries(handlers) as [
            MediaSessionAction,
            MediaSessionActionHandler,
        ][]) {
            try {
                mediaSession.setActionHandler(action, handler);
                registeredActions.push(action);
            } catch {
                // Older browsers may implement Media Session without every action.
            }
        }

        return () => {
            for (const action of registeredActions) {
                try {
                    mediaSession.setActionHandler(action, null);
                } catch {
                    // Ignore actions that became unsupported during unmount.
                }
            }

            try {
                mediaSession.metadata = null;
            } catch {
                // Older implementations may expose a read-only metadata property.
            }
        };
    }, [audioUrl, category, imageUrl, title]);

    return (
        <div className="flex flex-col gap-2">
            <label
                htmlFor={`post-audio-${postId}`}
                className="text-sm text-slate-600 dark:text-slate-400"
            >
                {label}
            </label>
            <audio
                ref={audioRef}
                id={`post-audio-${postId}`}
                controls
                preload="none"
                src={audioUrl}
                className="w-full"
            />
        </div>
    );
}
