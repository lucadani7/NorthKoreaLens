import type { SVGAttributes } from 'react';

/** The NK Lens mark: a magnifying glass with a red centre. */
export default function NkLensLogo(props: SVGAttributes<SVGElement>) {
    return (
        <svg viewBox="0 0 32 32" fill="none" aria-hidden="true" {...props}>
            <circle cx="13" cy="13" r="9" stroke="currentColor" strokeWidth="3" />
            <circle cx="13" cy="13" r="3.5" className="fill-nk-red" />
            <path d="M20 20l8 8" stroke="currentColor" strokeWidth="3.5" strokeLinecap="round" />
        </svg>
    );
}
