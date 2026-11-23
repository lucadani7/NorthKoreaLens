import { editorialPolicy, howToHelp, sources, team } from '@/routes';

/** Pages about NK Lens, in the header (and the phone menu) on every public page. */
export const SITE_LINKS = [
    { title: 'Team', href: team() },
    { title: 'Editorial policy', href: editorialPolicy() },
    { title: 'Sources', href: sources() },
    { title: 'How you can help', href: howToHelp() },
];
