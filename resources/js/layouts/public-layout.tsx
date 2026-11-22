import type { ReactNode } from 'react';
import DisclaimerBanner from '@/components/disclaimer-banner';
import SiteHeader from '@/components/site-header';

/** Layout for the public pages that guests can see: the feed and, later, the map and documentaries. */
export default function PublicLayout({ children }: { children: ReactNode }) {
    return (
        <div className="flex min-h-svh flex-col bg-slate-100 text-slate-900 dark:bg-slate-950 dark:text-slate-100">
            <SiteHeader />
            <DisclaimerBanner />

            <div className="flex-1">{children}</div>

            <footer className="border-t border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
                <p className="mx-auto max-w-7xl px-6 py-5 text-sm text-slate-600 dark:text-slate-400">
                    © NK Lens · We are not affiliated with the government of the DPRK and do not support the regime in
                    Pyongyang.
                </p>
            </footer>
        </div>
    );
}
