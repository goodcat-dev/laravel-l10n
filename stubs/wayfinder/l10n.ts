export function route<T extends (...args: any[]) => any>(
    routes: Record<string, T> & { __canonical: T },
    args?: Parameters<T>[0] & { lang?: string }
): ReturnType<T> {
    const { lang, ...params } = args ?? ({} as Parameters<T>[0] & { lang?: string });

    const locale = (lang ?? document.documentElement.lang).replaceAll('-', '_');

    const route = routes[locale] ?? routes.__canonical;

    return Object.keys(params).length
        ? route(params)
        : route();
}
