import { Head, Link } from '@inertiajs/react';
import PostCard from '@/components/feed/post-card';
import TopicFilter from '@/components/feed/topic-filter';
import SectionsLayout from '@/components/sections-layout';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import { warningBox } from '@/lib/warning-colors';

type Props = {
    posts: App.Data.PostData[];
    pagination: App.Data.PaginationData;
    categories: App.Data.CategoryData[];
    activeCategory: string | null;
    /** Topics with published posts in this section; empty outside a section. */
    topics: App.Data.TopicData[];
    activeTopic: string | null;
    /** Server time as ISO 8601. */
    now: string;
};

type SectionNote = {
    /** One string per paragraph. */
    paragraphs: string[];
    /** Shown in the warning yellow instead of the plain blue note. */
    warning?: boolean;
};

/** A short note at the top of a section, saying what it holds and how often it changes. */
const SECTION_NOTES: Record<string, SectionNote> = {
    'korea-now': {
        paragraphs: [
            'Videos about North Korea from KOREA NOW, the English YouTube channel of the South Korean news agency Yonhap. We check the channel every day; videos about other subjects are left out.',
        ],
    },
    'travel-vlogs': {
        paragraphs: [
            'Governments generally advise you to avoid travel to North Korea altogether. Some YouTubers have visited the country, and this section collects their videos, but that does not mean you should go too. Remember that tourists see only what they are shown: an official guide accompanies them at all times.',
            'A small story of our own: in August 2026, the creator of NK Lens searched Booking for hotels in Pyongyang, just for fun. Booking offered only hotels in Seoul.',
        ],
        warning: true,
    },
};

/** What an empty section says, when it has something more useful to say than the default. */
const EMPTY_NOTES: Record<string, string> = {
    'korea-now':
        'No videos about North Korea yet. We check the channel every day, and new ones will appear here.',
    'travel-vlogs': 'No videos here yet.',
    documentaries: 'No videos here yet.',
};

const pagerLink =
    'flex h-11 items-center rounded-full border border-nk-blue px-5 text-sm font-semibold text-nk-blue hover:bg-nk-blue/10 dark:border-sky-300 dark:text-sky-300';

export default function Feed({
    posts,
    pagination,
    categories,
    activeCategory,
    topics,
    activeTopic,
    now: serverNow,
}: Props) {
    // The server's clock keeps relative times identical in the SSR HTML and after hydration.
    const now = new Date(serverNow);
    const { t } = useTranslation();
    const current = categories.find(
        (category) => category.slug === activeCategory,
    );
    const sectionNote = activeCategory ? SECTION_NOTES[activeCategory] : null;
    const emptyNote =
        (activeCategory && EMPTY_NOTES[activeCategory]) ??
        'Nothing here yet. New articles arrive every hour.';

    return (
        <>
            <Head title={current?.name ?? t('Feed')} />

            <SectionsLayout active={activeCategory}>
                {/* On phones the sections are in the header menu; the current one is named here. */}
                <h1 className="font-display text-2xl font-bold lg:hidden">
                    {current?.name}
                </h1>

                {!activeCategory && (
                    <div className="flex max-w-3xl flex-col gap-3 rounded-xl border border-slate-200 bg-white px-5 py-4 text-[15px] leading-relaxed text-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300">
                        <p>
                            {t(
                                'North Korea is one of the world’s most closed countries. Access to independent information inside the country is severely restricted, while people outside it often encounter official propaganda or scattered headlines. Independent reporting exists, but it is spread across many sites and is usually published in English or Korean.',
                            )}
                        </p>
                        <p>
                            {t(
                                'We could have built a project about any country, but for most it would be unnecessary: local news is more accessible, and people can travel there relatively easily. In North Korea, access to information and the ability to travel are far more restricted. Many governments advise their citizens not to travel to North Korea. NK Lens gives the same advice.',
                            )}
                        </p>
                        <p>
                            {t(
                                'NK Lens brings this reporting together in one place and in thirteen languages. It presents North Korean state media alongside independent journalism, with clear labels that show who published each item and what kind of source it is.',
                            )}
                        </p>
                        <p>
                            {t(
                                'Seeing these accounts side by side makes it easier to compare what is said, notice what is left out, and understand what is known, disputed, or still uncertain.',
                            )}
                        </p>
                    </div>
                )}

                <section
                    aria-label={t('Feed')}
                    className="flex max-w-2xl min-w-0 flex-1 flex-col gap-4"
                >
                    {sectionNote && (
                        <div
                            role={sectionNote.warning ? 'note' : undefined}
                            className={cn(
                                'flex flex-col gap-2 rounded-xl px-5 py-4 text-[15px] leading-relaxed',
                                sectionNote.warning
                                    ? cn('border-2', warningBox)
                                    : 'border-l-4 border-nk-blue bg-white text-slate-700 dark:bg-slate-900 dark:text-slate-300',
                            )}
                        >
                            {sectionNote.paragraphs.map((paragraph) => (
                                <p key={paragraph}>{t(paragraph)}</p>
                            ))}
                        </div>
                    )}

                    {activeCategory && topics.length > 0 && (
                        <TopicFilter
                            category={activeCategory}
                            topics={topics}
                            active={activeTopic}
                        />
                    )}

                    {posts.length === 0 ? (
                        <p className="rounded-xl bg-white p-8 text-center text-slate-600 dark:bg-slate-900 dark:text-slate-400">
                            {t(emptyNote)}
                        </p>
                    ) : (
                        posts.map((post) => (
                            <PostCard key={post.id} post={post} now={now} />
                        ))
                    )}

                    {pagination.lastPage > 1 && (
                        <nav
                            aria-label={t('Pages')}
                            className="flex items-center justify-between gap-4 py-2"
                        >
                            {pagination.previousPageUrl ? (
                                <Link
                                    href={pagination.previousPageUrl}
                                    className={pagerLink}
                                >
                                    {t('Newer')}
                                </Link>
                            ) : (
                                <span />
                            )}
                            <span className="text-sm text-slate-600 dark:text-slate-400">
                                {t('Page :current of :last', {
                                    current: pagination.currentPage,
                                    last: pagination.lastPage,
                                })}
                            </span>
                            {pagination.nextPageUrl ? (
                                <Link
                                    href={pagination.nextPageUrl}
                                    className={pagerLink}
                                >
                                    {t('Older')}
                                </Link>
                            ) : (
                                <span />
                            )}
                        </nav>
                    )}
                </section>
            </SectionsLayout>
        </>
    );
}
