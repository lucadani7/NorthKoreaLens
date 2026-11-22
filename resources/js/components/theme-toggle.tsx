import type { LucideIcon } from 'lucide-react';
import { Monitor, Moon, Sun } from 'lucide-react';
import type { Appearance } from '@/hooks/use-appearance';
import { useAppearance } from '@/hooks/use-appearance';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';

const OPTIONS: { value: Appearance; icon: LucideIcon; label: string }[] = [
    { value: 'light', icon: Sun, label: 'Light mode' },
    { value: 'dark', icon: Moon, label: 'Dark mode' },
    { value: 'system', icon: Monitor, label: 'Use system setting' },
];

/** Light / dark / system switch for the blue site header. The choice is kept in a cookie for SSR. */
export default function ThemeToggle({ className }: { className?: string }) {
    const { appearance, updateAppearance } = useAppearance();
    const { t } = useTranslation();

    return (
        <div
            role="group"
            aria-label={t('Colour theme')}
            className={cn('flex rounded-full bg-white/15 p-1', className)}
        >
            {OPTIONS.map(({ value, icon: Icon, label }) => (
                <button
                    key={value}
                    type="button"
                    onClick={() => updateAppearance(value)}
                    aria-label={t(label)}
                    aria-pressed={appearance === value}
                    title={t(label)}
                    className={cn(
                        'flex size-9 items-center justify-center rounded-full transition-colors',
                        appearance === value
                            ? 'bg-white text-nk-blue'
                            : 'text-white hover:bg-white/20',
                    )}
                >
                    <Icon aria-hidden="true" className="size-4" />
                </button>
            ))}
        </div>
    );
}
