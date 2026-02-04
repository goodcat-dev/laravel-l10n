export let fallbackLocale = 'en';

export function setFallbackLocale(locale: string) {
    fallbackLocale = locale;
}

export function route<T extends (...args: any[]) => any>(
    routes: Record<string, T>,
    args?: Parameters<T>[0] & { lang?: string }
): ReturnType<T> {
    const locale = args?.lang
        ?? document.documentElement.lang
        ?? fallbackLocale;

    delete args?.lang;

    const route = routes[locale] ?? routes[fallbackLocale];

    return args && Object.keys(args).length
        ? route(args)
        : route();
}
