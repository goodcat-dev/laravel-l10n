export function route<T extends (...args: any[]) => any>(
    routes: Record<string, T> & { __canonical: T },
    args?: Parameters<T>[0] & { lang?: string }
): ReturnType<T> {
    let params = args;
    let lang: string | undefined;

    if (args !== null && typeof args === 'object' && !Array.isArray(args) && 'lang' in args) {
        ({ lang, ...params } = args);
    }

    const locale = (lang ?? globalThis.document?.documentElement.lang)?.replaceAll('-', '_');

    const localized = locale ? routes[locale] : undefined;
    const route = localized ?? routes.__canonical;

    return params === undefined ? route() : route(params);
}
