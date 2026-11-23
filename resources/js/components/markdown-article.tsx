import { Head, router } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useEffect, useRef } from 'react';
import SectionsLayout from '@/components/sections-layout';
import { useTranslation } from '@/hooks/use-translation';
import { warningBox } from '@/lib/warning-colors';
import { cn } from '@/lib/utils';

type Props = {
    page: App.Data.PageData;
    /** Shown under the text, inside the same card (e.g. the list of sources). */
    children?: ReactNode;
    /** Shown after the introduction, before the first heading (e.g. the team members). */
    afterIntro?: ReactNode;
};

/** A page the team wrote in Markdown, rendered on the server. */
export default function MarkdownArticle({ page, children, afterIntro }: Props) {
    const { t, locale } = useTranslation();
    const bodyRef = useRef<HTMLDivElement>(null);
    // With afterIntro, the page is split at its first heading so the block sits in between.
    const split = afterIntro ? page.html.indexOf('<h2') : -1;
    const intro = split > 0 ? page.html.slice(0, split) : page.html;
    const rest = split > 0 ? page.html.slice(split) : '';

    // Links between our own pages ("/sources") go through Inertia, so the page does not reload.
    useEffect(() => {
        const body = bodyRef.current;

        if (!body) {
            return;
        }

        const followInternalLink = (event: MouseEvent) => {
            const link = (event.target as HTMLElement).closest('a');

            if (
                !link ||
                link.origin !== window.location.origin ||
                event.metaKey ||
                event.ctrlKey ||
                event.shiftKey ||
                event.button !== 0
            ) {
                return;
            }

            event.preventDefault();
            router.visit(link.pathname + link.search + link.hash);
        };

        body.addEventListener('click', followInternalLink);

        return () => body.removeEventListener('click', followInternalLink);
    }, []);

    return (
        <>
            <Head title={page.title}>
                {page.description && (
                    <meta name="description" content={page.description} />
                )}
            </Head>

            <SectionsLayout className="max-w-3xl">
                <article className="rounded-xl bg-white p-6 shadow-sm sm:p-10 dark:bg-slate-900">
                    {page.locale !== locale && (
                        <p
                            className={cn(
                                'mb-6 rounded-lg border-2 px-4 py-3 text-sm',
                                warningBox,
                            )}
                        >
                            {t(
                                'This page is not yet available in your language, so you are reading it in English.',
                            )}
                        </p>
                    )}

                    <h1
                        lang={page.locale}
                        className="font-display text-3xl leading-tight font-bold sm:text-4xl"
                    >
                        {page.title}
                    </h1>

                    <div ref={bodyRef} lang={page.locale}>
                        <div
                            className="nk-prose mt-6"
                            // Our own Markdown, rendered by the server with raw HTML stripped.
                            dangerouslySetInnerHTML={{ __html: intro }}
                        />
                        {afterIntro}
                        {rest && (
                            <div
                                className="nk-prose mt-6"
                                dangerouslySetInnerHTML={{ __html: rest }}
                            />
                        )}
                    </div>

                    {children}
                </article>
            </SectionsLayout>
        </>
    );
}
