import { router, usePage } from '@inertiajs/react';
import { Check, ChevronDown, Globe } from 'lucide-react';
import { useState } from 'react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import { useIsMobile } from '@/hooks/use-mobile';
import { useTranslation } from '@/hooks/use-translation';
import { cn } from '@/lib/utils';
import { update as updateLocale } from '@/routes/locale';

type Props = {
    className?: string;
    /** "header" for the blue site header, "plain" for light or dark page backgrounds. */
    variant?: 'header' | 'plain';
    /** Below 640 px, show the language code (EN, RO…) instead of its name. */
    compact?: boolean;
};

/**
 * Language menu. Each language is named in its own language.
 * Phones get a sheet that slides up from the bottom, with large touch targets;
 * larger screens get a dropdown under the button.
 */
export default function LanguagePicker({
    className,
    variant = 'header',
    compact = false,
}: Props) {
    const { locales } = usePage().props;
    const { t, locale } = useTranslation();
    const isMobile = useIsMobile();
    const [open, setOpen] = useState(false);

    const current = locales.find(({ code }) => code === locale);

    const choose = (code: string) => {
        setOpen(false);

        if (code !== locale) {
            router.post(
                updateLocale.url(),
                { locale: code },
                { preserveScroll: true },
            );
        }
    };

    const trigger = (
        <button
            type="button"
            className={cn(
                'flex h-11 cursor-pointer items-center gap-2 rounded-full pr-3 pl-3 text-sm font-semibold transition-colors focus-visible:ring-2 focus-visible:outline-none',
                variant === 'header'
                    ? 'bg-white/15 text-white hover:bg-white/25 focus-visible:ring-white'
                    : 'border border-border text-foreground hover:bg-muted focus-visible:ring-ring',
                className,
            )}
        >
            <Globe aria-hidden="true" className="size-4" />
            <span className="sr-only">{t('Language')}:</span>
            <span
                lang={current?.code}
                className={cn(compact && 'hidden sm:inline')}
            >
                {current?.name}
            </span>
            {compact && (
                <span aria-hidden="true" className="uppercase sm:hidden">
                    {current?.code}
                </span>
            )}
            <ChevronDown aria-hidden="true" className="size-4 opacity-70" />
        </button>
    );

    if (isMobile) {
        return (
            <Sheet open={open} onOpenChange={setOpen}>
                <SheetTrigger asChild>{trigger}</SheetTrigger>
                <SheetContent
                    side="bottom"
                    aria-describedby={undefined}
                    className="max-h-[85dvh] gap-0 rounded-t-2xl pb-[max(1rem,env(safe-area-inset-bottom))]"
                >
                    <div
                        aria-hidden="true"
                        className="mx-auto mt-3 h-1.5 w-10 rounded-full bg-muted-foreground/30"
                    />
                    <SheetHeader className="px-5 pt-3 pb-2">
                        <SheetTitle className="flex items-center gap-2 font-display text-lg font-bold">
                            <Globe
                                aria-hidden="true"
                                className="size-5 text-nk-blue dark:text-sky-300"
                            />
                            {t('Language')}
                        </SheetTitle>
                    </SheetHeader>

                    <ul className="overflow-y-auto px-3">
                        {locales.map(({ code, name }) => {
                            const selected = code === locale;

                            return (
                                <li key={code}>
                                    <button
                                        type="button"
                                        lang={code}
                                        aria-current={selected || undefined}
                                        onClick={() => choose(code)}
                                        className={cn(
                                            'flex min-h-12 w-full items-center justify-between rounded-xl px-4 text-left text-base transition-colors',
                                            selected
                                                ? 'bg-nk-blue font-semibold text-white'
                                                : 'text-foreground hover:bg-muted active:bg-muted',
                                        )}
                                    >
                                        {name}
                                        {selected && (
                                            <Check
                                                aria-hidden="true"
                                                className="size-5"
                                            />
                                        )}
                                    </button>
                                </li>
                            );
                        })}
                    </ul>
                </SheetContent>
            </Sheet>
        );
    }

    return (
        <DropdownMenu open={open} onOpenChange={setOpen}>
            <DropdownMenuTrigger asChild>{trigger}</DropdownMenuTrigger>
            <DropdownMenuContent
                align="end"
                className="min-w-44 rounded-xl p-1.5"
            >
                <DropdownMenuRadioGroup value={locale} onValueChange={choose}>
                    {locales.map(({ code, name }) => (
                        <DropdownMenuRadioItem
                            key={code}
                            value={code}
                            lang={code}
                            className="cursor-pointer rounded-lg py-2 text-sm"
                        >
                            {name}
                        </DropdownMenuRadioItem>
                    ))}
                </DropdownMenuRadioGroup>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
