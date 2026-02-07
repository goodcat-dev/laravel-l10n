import { route as ziggy } from '../../vendor/tightenco/ziggy';

export function route(name, params, absolute = true) {
    const localized = name.concat('.', params?.lang ?? document.documentElement.lang);

    delete params?.lang;

    if (ziggy().has(localized)) {
        name = localized;
    }

    return ziggy(name, params, absolute);
}
