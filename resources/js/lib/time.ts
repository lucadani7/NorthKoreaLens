const UNITS: [Intl.RelativeTimeFormatUnit, number][] = [
    ['week', 60 * 60 * 24 * 7],
    ['day', 60 * 60 * 24],
];

function completedCalendarMonths(start: Date, end: Date): number {
    const months =
        (end.getUTCFullYear() - start.getUTCFullYear()) * 12 +
        end.getUTCMonth() -
        start.getUTCMonth();
    const targetMonth = start.getUTCMonth() + months;
    const targetYear = start.getUTCFullYear() + Math.floor(targetMonth / 12);
    const normalizedMonth = targetMonth % 12;
    const lastDayOfMonth = new Date(
        Date.UTC(targetYear, normalizedMonth + 1, 0),
    ).getUTCDate();
    const anniversary = Date.UTC(
        targetYear,
        normalizedMonth,
        Math.min(start.getUTCDate(), lastDayOfMonth),
        start.getUTCHours(),
        start.getUTCMinutes(),
        start.getUTCSeconds(),
        start.getUTCMilliseconds(),
    );

    return anniversary > end.getTime() ? months - 1 : months;
}

function formatUnit(
    locale: string,
    value: number,
    unit: 'hour' | 'minute' | 'year' | 'month',
): string {
    return new Intl.NumberFormat(locale, {
        style: 'unit',
        unit,
        unitDisplay: 'long',
    }).format(value);
}

/**
 * "2 hours ago", "yesterday", in the given locale.
 * `now` is a parameter so the server and the browser can render the same text.
 */
export function timeAgo(
    iso: string,
    locale = 'en',
    now: Date = new Date(),
    translate: (
        key: string,
        replacements?: Record<string, string | number>,
    ) => string = (key, replacements = {}) =>
        Object.entries(replacements).reduce(
            (text, [name, value]) => text.replaceAll(`:${name}`, String(value)),
            key,
        ),
): string {
    const seconds = Math.round(
        (new Date(iso).getTime() - now.getTime()) / 1000,
    );
    const format = new Intl.RelativeTimeFormat(locale, { numeric: 'auto' });
    const publishedAt = new Date(iso);
    const [start, end] = seconds > 0 ? [now, publishedAt] : [publishedAt, now];
    const totalMonths = completedCalendarMonths(start, end);
    const years = Math.floor(totalMonths / 12);
    const months = totalMonths % 12;

    if (years > 0 && months > 0) {
        const replacements = {
            years: formatUnit(locale, years, 'year'),
            months: formatUnit(locale, months, 'month'),
        };

        return seconds > 0
            ? translate('in :years and :months', replacements)
            : translate(':years and :months ago', replacements);
    }

    if (years > 0) {
        return new Intl.RelativeTimeFormat(locale, {
            numeric: 'always',
        }).format(Math.sign(seconds) * years, 'year');
    }

    if (totalMonths > 0) {
        return format.format(Math.sign(seconds) * totalMonths, 'month');
    }

    for (const [unit, size] of UNITS) {
        if (Math.abs(seconds) >= size) {
            return format.format(Math.round(seconds / size), unit);
        }
    }

    const totalMinutes = Math.round(Math.abs(seconds) / 60);
    const hours = Math.floor(totalMinutes / 60);
    const minutes = totalMinutes % 60;

    if (hours > 0 && minutes > 0) {
        const replacements = {
            hours: formatUnit(locale, hours, 'hour'),
            minutes: formatUnit(locale, minutes, 'minute'),
        };

        return seconds > 0
            ? translate('in :hours and :minutes', replacements)
            : translate(':hours and :minutes ago', replacements);
    }

    if (hours > 0) {
        return format.format(Math.sign(seconds) * hours, 'hour');
    }

    return format.format(Math.sign(seconds) * totalMinutes, 'minute');
}
