import { TriangleAlert } from 'lucide-react';
import { useTranslation } from '@/hooks/use-translation';

/** Red band on every post from North Korean state media or the Propaganda section. */
export default function PropagandaBanner() {
    const { t } = useTranslation();

    return (
        <div className="flex items-center gap-2 bg-nk-red-dark px-5 py-2 text-[13px] font-semibold tracking-wide text-white">
            <TriangleAlert aria-hidden="true" className="size-4 shrink-0" />
            {t('PROPAGANDA · Official North Korean state source')}
        </div>
    );
}
