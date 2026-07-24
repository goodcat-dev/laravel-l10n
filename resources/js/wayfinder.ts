export function route<T extends (...args: any[]) => any>(
    routes: Record<string, T> & { __canonical: T },
    args?: Parameters<T>[0] & { lang?: string }
): ReturnType<T> {
    const { lang, ...params } = args ?? ({} as Parameters<T>[0] & { lang?: string });

    const locale = (lang ?? globalThis.document?.documentElement.lang)?.replaceAll('-', '_');

    const localized = locale ? routes[locale] : undefined;
    const route = localized ?? routes.__canonical;

    return Object.keys(params).length
        ? route(params)
        : route();
}
