import { Link, usePage } from '@inertiajs/react';
import NkLensLogo from '@/components/nk-lens-logo';
import ThemeToggle from '@/components/theme-toggle';
import { dashboard, home, login, register } from '@/routes';

/** Thin stripe that recalls the flag without copying it. */
function FlagStripe() {
    return (
        <div aria-hidden="true" className="flex flex-col">
            <div className="h-1 bg-white" />
            <div className="h-1.5 bg-nk-red" />
            <div className="h-1 bg-white" />
            <div className="h-0.5 bg-nk-blue" />
        </div>
    );
}

export default function SiteHeader() {
    const { auth, name } = usePage().props;

    return (
        <header className="bg-nk-blue text-white">
            <div className="mx-auto flex min-h-16 max-w-7xl flex-wrap items-center gap-6 px-6">
                <Link href={home()} className="flex items-center gap-2.5 text-white">
                    <NkLensLogo className="size-8" />
                    <span className="font-display text-xl font-bold tracking-tight">{name}</span>
                </Link>

                <ThemeToggle className="ml-auto" />

                <nav className="flex items-center gap-2 text-sm font-semibold">
                    {auth.user ? (
                        <Link
                            href={dashboard()}
                            className="flex h-11 items-center rounded-full bg-white px-4 text-nk-blue hover:bg-white/90"
                        >
                            Dashboard
                        </Link>
                    ) : (
                        <>
                            <Link
                                href={login()}
                                className="flex h-11 items-center rounded-full px-4 hover:bg-white/15"
                            >
                                Log in
                            </Link>
                            <Link
                                href={register()}
                                className="flex h-11 items-center rounded-full bg-white px-4 text-nk-blue hover:bg-white/90"
                            >
                                Join NK Lens
                            </Link>
                        </>
                    )}
                </nav>
            </div>
            <FlagStripe />
        </header>
    );
}
