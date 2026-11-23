/**
 * Each kind of post has its own colour: news blue, propaganda red, curiosities green.
 */
export function isCuriosity(slug: string | null | undefined): boolean {
    return slug === 'curiosities';
}

/** The small label with the section name on a post. */
export function categoryBadge(slug: string): string {
    return isCuriosity(slug)
        ? 'bg-nk-green/10 text-nk-green-dark dark:bg-nk-green/30 dark:text-white'
        : 'bg-nk-blue/10 text-nk-blue-dark dark:bg-nk-blue/30 dark:text-white';
}
