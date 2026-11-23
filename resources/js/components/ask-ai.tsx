import { usePage } from '@inertiajs/react';
import { Check, Copy, ExternalLink, Sparkles } from 'lucide-react';
import { useState } from 'react';
import { useTranslation } from '@/hooks/use-translation';

type Props = {
    post: App.Data.PostData;
};

/** Assistants that open with the question already typed in (the reader still presses send). */
const ASSISTANTS = [
    { name: 'ChatGPT', url: 'https://chatgpt.com/?q=' },
    { name: 'Claude', url: 'https://claude.ai/new?q=' },
    { name: 'Perplexity', url: 'https://www.perplexity.ai/search/new?q=' },
];

/** Gemini cannot be opened with a question in the link, so the question is copied first. */
const GEMINI_URL = 'https://gemini.google.com/app';

const link =
    'inline-flex h-10 items-center gap-1.5 rounded-full border border-violet-300 px-4 text-sm font-semibold text-violet-800 hover:bg-violet-100 dark:border-violet-800 dark:text-violet-200 dark:hover:bg-violet-900/40';

/**
 * "Ask an AI assistant": links that open the reader's own ChatGPT, Claude or Perplexity with a
 * question about the post. NK Lens sends nothing and sees nothing of the conversation.
 */
export default function AskAi({ post }: Props) {
    const { t, locale } = useTranslation();
    const { locales } = usePage().props;
    const [copied, setCopied] = useState(false);

    const language =
        locales.find((item) => item.code === locale)?.name ?? 'English';

    const lines = [
        post.isPropaganda
            ? t(
                  'This headline comes from North Korean state media. Explain what message the regime wants to send with it, which propaganda techniques it uses, and what independent sources generally report about the subject. Do not treat its claims as facts. Answer in :language.',
                  { language },
              )
            : t(
                  'Explain the background of this news story about North Korea for a general reader: who or what is mentioned, why it matters and what is generally known about it. Keep it short and factual, and say clearly what is uncertain. Answer in :language.',
                  { language },
              ),
        '',
        `${t('Headline')}: ${post.title}`,
        `${t('Source')}: ${post.source?.name ?? 'NK Lens'}`,
        post.publishedAt
            ? `${t('Date')}: ${post.publishedAt.slice(0, 10)}`
            : null,
        post.excerpt ? `${t('Excerpt')}: ${post.excerpt}` : null,
        post.editorialNote
            ? `${t('NK Lens note')}: ${post.editorialNote}`
            : null,
        post.originalUrl ? `${t('Link')}: ${post.originalUrl}` : null,
    ];
    const question = lines
        .filter((line): line is string => line !== null)
        .join('\n');

    const copy = async (): Promise<boolean> => {
        try {
            await navigator.clipboard.writeText(question);
            setCopied(true);
            setTimeout(() => setCopied(false), 2500);

            return true;
        } catch {
            return false;
        }
    };

    return (
        <section
            id="ask-ai"
            aria-label={t('Ask an AI assistant about this story')}
            className="rounded-xl border border-violet-200 bg-violet-50 p-4 dark:border-violet-900 dark:bg-violet-950/40"
        >
            <h2 className="flex items-center gap-2 text-sm font-bold text-violet-900 dark:text-violet-200">
                <Sparkles aria-hidden="true" className="size-4" />
                {t('Ask an AI assistant about this story')}
            </h2>

            <div className="mt-3 flex flex-wrap gap-2">
                {ASSISTANTS.map(({ name, url }) => (
                    <a
                        key={name}
                        href={url + encodeURIComponent(question)}
                        target="_blank"
                        rel="noopener noreferrer"
                        className={link}
                    >
                        {name}
                        <ExternalLink aria-hidden="true" className="size-3.5" />
                    </a>
                ))}
                <a
                    href={GEMINI_URL}
                    target="_blank"
                    rel="noopener noreferrer"
                    onClick={() => void copy()}
                    className={link}
                    title={t(
                        'Copies the question, then opens Gemini: paste it there.',
                    )}
                >
                    Gemini
                    <ExternalLink aria-hidden="true" className="size-3.5" />
                </a>
                <button
                    type="button"
                    onClick={() => void copy()}
                    className={link}
                >
                    {copied ? (
                        <Check aria-hidden="true" className="size-4" />
                    ) : (
                        <Copy aria-hidden="true" className="size-4" />
                    )}
                    <span aria-live="polite">
                        {copied ? t('Copied!') : t('Copy the question')}
                    </span>
                </button>
            </div>

            <p className="mt-3 text-xs text-slate-600 dark:text-slate-400">
                {t(
                    'The assistant opens in your own account. NK Lens does not see your questions or the answers. For Gemini or any other assistant, the question is copied: paste it there.',
                )}
            </p>
        </section>
    );
}
