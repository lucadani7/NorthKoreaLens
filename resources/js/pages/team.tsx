import MarkdownArticle from '@/components/markdown-article';
import { useTranslation } from '@/hooks/use-translation';

type Props = {
    page: App.Data.PageData;
    members: App.Data.TeamMemberData[];
};

/** The Team page: the introduction, a card for each person, then how to join. */
export default function Team({ page, members }: Props) {
    const { t } = useTranslation();

    const fields = (member: App.Data.TeamMemberData) =>
        [
            { label: 'Country of origin', value: member.country },
            { label: 'Profession', value: member.profession },
            { label: 'Roles', value: member.roles },
        ].filter(
            (field): field is { label: string; value: string } =>
                field.value !== null,
        );

    return (
        <MarkdownArticle
            page={page}
            afterIntro={
                <ul
                    aria-label={t('Team members')}
                    className="mt-8 grid gap-4 sm:grid-cols-2"
                >
                    {members.map((member) => (
                        <li
                            key={member.key}
                            className="rounded-xl border border-slate-200 bg-slate-50 p-5 dark:border-slate-800 dark:bg-slate-800/50"
                        >
                            <h2 className="font-display text-xl font-bold">
                                {member.name}
                            </h2>
                            <dl className="mt-3 grid gap-2 text-[15px]">
                                {fields(member).map(({ label, value }) => (
                                    <div key={label}>
                                        <dt className="text-xs font-semibold tracking-wide text-slate-600 uppercase dark:text-slate-400">
                                            {t(label)}
                                        </dt>
                                        <dd className="text-slate-800 dark:text-slate-200">
                                            {value}
                                        </dd>
                                    </div>
                                ))}
                                {member.notesHtml && (
                                    <div>
                                        <dt className="text-xs font-semibold tracking-wide text-slate-600 uppercase dark:text-slate-400">
                                            {t('Notes')}
                                        </dt>
                                        <dd
                                            className="text-slate-800 dark:text-slate-200 [&_a]:font-semibold [&_a]:text-nk-blue [&_a]:underline [&_a]:underline-offset-4 dark:[&_a]:text-sky-300"
                                            // Our own Markdown (lang/<locale>/team.php), raw HTML stripped on the server.
                                            dangerouslySetInnerHTML={{
                                                __html: member.notesHtml,
                                            }}
                                        />
                                    </div>
                                )}
                            </dl>
                        </li>
                    ))}
                </ul>
            }
        />
    );
}
