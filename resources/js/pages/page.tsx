import MarkdownArticle from '@/components/markdown-article';

/** About us, the editorial policy and the other pages written in Markdown. */
export default function Page({ page }: { page: App.Data.PageData }) {
    return <MarkdownArticle page={page} />;
}
