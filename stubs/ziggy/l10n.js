import { route as ziggy } from '../../vendor/tightenco/ziggy';

export function route(name, params, absolute = true) {
    const localized = name.concat('.', params?.lang ?? document.documentElement.lang);

    if (params && typeof params === 'object' && !Array.isArray(params) && 'lang' in params) {
        const { lang, ...rest } = params;

        params = rest;
    }

    if (ziggy().has(localized)) {
        name = localized;
    }

    return ziggy(name, params, absolute);
}
