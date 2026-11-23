import { Info } from 'lucide-react';
import { useTranslation } from '@/hooks/use-translation';
import { warningBox, warningIcon } from '@/lib/warning-colors';
import { cn } from '@/lib/utils';

/** Always visible on public pages: NK Lens must never look affiliated with the regime. */
export default function DisclaimerBanner() {
    const { t } = useTranslation();

    return (
        <div role="note" className={cn('border-b-2', warningBox)}>
            <div className="mx-auto flex max-w-7xl items-start gap-3 px-6 py-3">
                <Info
                    aria-hidden="true"
                    className={cn('mt-0.5 size-5 shrink-0', warningIcon)}
                />
                <p className="text-sm leading-relaxed">
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
