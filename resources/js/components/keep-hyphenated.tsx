import { Fragment } from 'react';

/** Words joined by a hyphen, e.g. "Jang-mi", "nord-coreean" or "Kim Il-sung". */
const HYPHENATED = /([\p{L}\p{N}]+(?:-[\p{L}\p{N}]+)+)/u;

/**
 * French puts a space before ! ? : ; » and after «. A no-break space there keeps the mark
 * on the same line as the word before it, so a title never ends with a lone "!" on its own line.
 */
function keepFrenchPunctuation(text: string): string {
    return text.replace(/ ([!?:;»])/g, ' $1').replace(/« /g, '« ');
}

/**
 * Text in which hyphenated words never break across lines: the browser may only break at
 * the spaces, so "Jang-mi" moves to the next line whole instead of ending a line with "Jang-".
 * Hyphens are left as they are, so copying the text still gives ordinary hyphens.
 */
export default function KeepHyphenated({ text }: { text: string }) {
    return keepFrenchPunctuation(text)
        .split(HYPHENATED)
        .map((part, index) =>
            index % 2 === 1 ? (
                <span key={index} className="whitespace-nowrap">
                    {part}
                </span>
            ) : (
                <Fragment key={index}>{part}</Fragment>
            ),
        );
}
