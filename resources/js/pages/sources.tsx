import { ExternalLink } from 'lucide-react';
import MarkdownArticle from '@/components/markdown-article';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';

type Props = {
    page: App.Data.PageData;
    sources: App.Data.SourceListingData[];
};

const GROUPS: { type: App.Enums.SourceType; title: string }[] = [
    { type: 'independent', title: 'Independent newsrooms' },
    { type: 'analysis', title: 'Analysis and research' },
    { type: 'testimony', title: 'Escapee testimonies' },
    { type: 'travel_vlog', title: 'Travel vloggers' },
    { type: 'state_media', title: 'North Korean state media' },
];

export default function Sources({ page, sources }: Props) {
    const { t } = useTranslation();

    return (
        <MarkdownArticle page={page}>
            <div className="mt-8 flex flex-col gap-8">
                {GROUPS.map(({ type, title }) => {
                    const group = sources.filter(
                        (source) => source.type === type,
                    );

                    if (group.length === 0) {
                        return null;
                    }

                    const propaganda = type === 'state_media';

                    return (
                        <section key={type}>
                            <h2 className="font-display text-xl font-bold">
                                {t(title)}
                            </h2>
                            {propaganda && (
                                <p className="mt-1 text-sm text-slate-600 dark:text-slate-400">
                                    {t(
                                        'Shown only in the Propaganda section. We do not link to North Korean state media sites.',
                                    )}
                                </p>
                            )}

                            <ul className="mt-3 divide-y divide-slate-200 overflow-hidden rounded-xl border border-slate-200 dark:divide-slate-800 dark:border-slate-800">
                                {group.map((source) => (
                                    <li
                                        key={source.id}
                                        className="flex min-h-14 items-center justify-between gap-4 px-4 py-3"
                                    >
                                        <span className="flex items-center gap-3 font-semibold">
                                            <span
                                                aria-hidden="true"
                                                className={cn(
                                                    'size-2.5 shrink-0 rounded-full',
                                                    propaganda
                                                        ? 'bg-nk-red-dark'
                                                        : 'bg-nk-blue',
                                                )}
                                            />
                                            {source.name}
                                        </span>

                                        {source.websiteUrl && (
                                            <a
                                                href={source.websiteUrl}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="inline-flex h-11 shrink-0 items-center gap-1.5 rounded-lg px-2 text-sm font-semibold text-nk-blue hover:bg-nk-blue/10 dark:text-sky-300"
                                            >
                                                {t('Website')}
                                                <ExternalLink
                                                    aria-hidden="true"
                                                    className="size-4"
                                                />
                                            </a>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        </section>
                    );
                })}
            </div>
        </MarkdownArticle>
    );
}
