type LocalizedParams<Params> = Params | (Extract<Params, object> & { lang?: string });

export type LocalizedRoutes<T> = Record<string, T> & { __canonical: T };

export function route<T extends (...args: any[]) => any>(
    routes: LocalizedRoutes<T>,
    params: LocalizedParams<Parameters<T>[0]>,
    options?: Parameters<T>[1],
): ReturnType<T>;

export function route<T extends () => any>(
    routes: LocalizedRoutes<T>,
    params?: LocalizedParams<Parameters<T>[0]>,
    options?: Parameters<T>[1],
): ReturnType<T>;

export function route(
    routes: LocalizedRoutes<(...args: any[]) => any>,
    params?: unknown,
    options?: unknown,
): unknown {
    let lang: string | undefined;

    if (params !== null && typeof params === 'object' && !Array.isArray(params) && 'lang' in params) {
        ({ lang, ...params } = params as { lang?: string });
    }

    const locale = (lang ?? globalThis.document?.documentElement.lang)?.replaceAll('-', '_');
    const localized = locale ? routes[locale] : undefined;
    const selected = localized ?? routes.__canonical;

    if (arguments.length === 1) {
        return selected();
    }

    return arguments.length === 2 ? selected(params) : selected(params, options);
}
