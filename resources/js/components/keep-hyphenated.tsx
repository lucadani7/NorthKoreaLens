import { Fragment } from 'react';

/** Words joined by a hyphen, e.g. "Jang-mi", "nord-coreean" or "Kim Il-sung". */
const HYPHENATED = /([\p{L}\p{N}]+(?:-[\p{L}\p{N}]+)+)/u;

/**
 * Text in which hyphenated words never break across lines: the browser may only break at
 * the spaces, so "Jang-mi" moves to the next line whole instead of ending a line with "Jang-".
 * The text itself is unchanged, so copying it still gives ordinary hyphens.
 */
export default function KeepHyphenated({ text }: { text: string }) {
    return text.split(HYPHENATED).map((part, index) =>
        index % 2 === 1 ? (
            <span key={index} className="whitespace-nowrap">
                {part}
            </span>
        ) : (
            <Fragment key={index}>{part}</Fragment>
        ),
    );
}
