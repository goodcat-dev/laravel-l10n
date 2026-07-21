import { route as ziggy } from '../../vendor/tightenco/ziggy';

export function route(name, params, absolute = true, config) {
    if (name === undefined) {
        return ziggy(name, params, absolute, config);
    }

    const locale = params?.lang ?? globalThis.document?.documentElement.lang;

    if (params && typeof params === 'object' && !Array.isArray(params) && 'lang' in params) {
        const { lang, ...rest } = params;

        params = rest;
    }

    const router = ziggy(undefined, undefined, absolute, config);

    if (locale) {
        const localized = [`${name}.${locale}`, `${name}.${locale.replaceAll('-', '_')}`]
            .find((candidate) => router.has(candidate));

        name = localized ?? name;
    }

    return ziggy(name, params, absolute, config);
}
