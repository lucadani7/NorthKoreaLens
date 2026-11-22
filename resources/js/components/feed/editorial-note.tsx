import { useTranslation } from '@/hooks/use-translation';

/** The team's context next to a post, above all under propaganda. */
export default function EditorialNote({ note }: { note: string }) {
    const { t } = useTranslation();

    return (
        <aside className="rounded-xl bg-slate-100 px-4 py-3 dark:bg-slate-800">
            <h3 className="mb-1 text-sm font-bold text-nk-blue dark:text-sky-300">
                {t('What the article doesn\'t say')}
            </h3>
            <p className="text-[15px] leading-relaxed whitespace-pre-line text-slate-800 dark:text-slate-200">{note}</p>
        </aside>
    );
}
