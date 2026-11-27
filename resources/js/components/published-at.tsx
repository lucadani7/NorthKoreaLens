import { useEffect, useState } from 'react';
import { useTranslation } from '@/hooks/use-translation';
import { timeAgo } from '@/lib/time';

type Props = {
    publishedAt: string;
    locale: string;
    now: Date;
    className?: string;
};

export default function PublishedAt({
    publishedAt,
    locale,
    now,
    className,
}: Props) {
    const [localDateTime, setLocalDateTime] = useState<string | null>(null);
    const { t } = useTranslation();

    useEffect(() => {
        setLocalDateTime(
            new Intl.DateTimeFormat(locale, {
                dateStyle: 'medium',
                timeStyle: 'short',
            }).format(new Date(publishedAt)),
        );
    }, [locale, publishedAt]);

    return (
        <time dateTime={publishedAt} className={className}>
            {localDateTime && (
                <>
                    <span>{localDateTime}</span>
                    <span aria-hidden="true"> · </span>
                </>
            )}
            {timeAgo(publishedAt, locale, now, t)}
        </time>
    );
}
