export let fallbackLocale: string = 'en';

export function setFallbackLocale(locale: string): void {
    fallbackLocale = locale;
}

export function route<T extends (...args: any[]) => any>(
    routes: Record<string, T>,
    args?: Parameters<T>[0] & { lang?: string }
): ReturnType<T> {
    const { lang, ...params } = args ?? {};

    const locale = lang
        ?? document.documentElement.lang
        ?? fallbackLocale;

    const route = routes[locale] ?? routes[fallbackLocale];

    return Object.keys(params).length
        ? route(params)
        : route();
}
