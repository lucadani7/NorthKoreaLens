import { Info } from 'lucide-react';
import { useTranslation } from '@/hooks/use-translation';

/** Always visible on public pages: NK Lens must never look affiliated with the regime. */
export default function DisclaimerBanner() {
    const { t } = useTranslation();

    return (
        <div
            role="note"
            className="border-b border-amber-200 bg-amber-50 dark:border-amber-900 dark:bg-amber-950"
        >
            <div className="mx-auto flex max-w-7xl items-start gap-3 px-6 py-3">
                <Info
                    aria-hidden="true"
                    className="mt-0.5 size-5 shrink-0 text-amber-800 dark:text-amber-300"
                />
                <p className="text-sm leading-relaxed text-amber-950 dark:text-amber-100">
                    <strong>
                        {t(
                            'NK Lens is not affiliated with the government of the DPRK and does not support the regime in Pyongyang.',
                        )}
                    </strong>{' '}
                    {t(
                        'Items in the Propaganda section are shown for information only, with editorial context.',
                    )}
                </p>
            </div>
        </div>
    );
}
