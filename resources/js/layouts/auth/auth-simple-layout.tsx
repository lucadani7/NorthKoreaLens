import { Link } from '@inertiajs/react';
import LanguagePicker from '@/components/language-picker';
import NkLensLogo from '@/components/nk-lens-logo';
import { useTranslation } from '@/hooks/use-translation';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    const { t } = useTranslation();
    // Pages pass their English title and description; they are translated here.
    const translatedTitle = title ? t(title) : '';

    return (
        <div className="relative flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10">
            <LanguagePicker
                variant="plain"
                className="absolute top-4 right-4"
            />

            <div className="w-full max-w-sm">
                <div className="flex flex-col gap-8">
                    <div className="flex flex-col items-center gap-4">
                        <Link
                            href={home()}
                            className="flex flex-col items-center gap-2 font-medium"
                        >
                            <NkLensLogo className="size-10 text-nk-blue dark:text-white" />
                            <span className="sr-only">NK Lens</span>
                        </Link>

                        <div className="space-y-2 text-center">
                            <h1 className="text-xl font-medium">
                                {translatedTitle}
                            </h1>
                            <p className="text-center text-sm text-muted-foreground">
                                {description ? t(description) : ''}
                            </p>
                        </div>
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
