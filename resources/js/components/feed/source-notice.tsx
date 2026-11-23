import { Info } from 'lucide-react';
import { useTranslation } from '@/hooks/use-translation';

/**
 * Yellow note on every post from a source that needs one, e.g. who a YouTuber is
 * and what is known about them. Written once per source, not in each post.
 */
export default function SourceNotice({ notice }: { notice: string }) {
    const { t } = useTranslation();

    return (
        <aside
            role="note"
            className="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-900 dark:bg-amber-950"
        >
            <Info
                aria-hidden="true"
                className="mt-0.5 size-5 shrink-0 text-amber-800 dark:text-amber-300"
            />
            <div className="flex flex-col gap-2">
                <h3 className="text-sm font-bold text-amber-950 dark:text-amber-100">
                    {t('About this source')}
                </h3>
                {/* Paragraphs are separated by a blank line in lang/<locale>/source_notices.php. */}
                {notice
                    .split(/\n{2,}/)
                    .filter((paragraph) => paragraph.trim() !== '')
                    .map((paragraph, index) => (
                        <p
                            key={index}
                            className="text-sm leading-relaxed text-amber-950 dark:text-amber-100"
                        >
                            {paragraph}
                        </p>
                    ))}
            </div>
        </aside>
    );
}
