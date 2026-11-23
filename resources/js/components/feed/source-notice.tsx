import { Info } from 'lucide-react';
import { useTranslation } from '@/hooks/use-translation';
import { warningBox, warningIcon } from '@/lib/warning-colors';
import { cn } from '@/lib/utils';

/**
 * Yellow note on every post from a source that needs one, e.g. who a YouTuber is
 * and what is known about them. Written once per source, not in each post.
 */
export default function SourceNotice({ notice }: { notice: string }) {
    const { t } = useTranslation();

    return (
        <aside
            role="note"
            className={cn(
                'flex items-start gap-3 rounded-xl border-2 px-4 py-3',
                warningBox,
            )}
        >
            <Info
                aria-hidden="true"
                className={cn('mt-0.5 size-5 shrink-0', warningIcon)}
            />
            <div className="flex flex-col gap-2">
                <h3 className="text-sm font-bold">{t('About this source')}</h3>
                {/* Paragraphs are separated by a blank line in lang/<locale>/source_notices.php. */}
                {notice
                    .split(/\n{2,}/)
                    .filter((paragraph) => paragraph.trim() !== '')
                    .map((paragraph, index) => (
                        <p key={index} className="text-sm leading-relaxed">
                            {paragraph}
                        </p>
                    ))}
            </div>
        </aside>
    );
}
