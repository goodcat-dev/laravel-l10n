import { route as ziggy } from '../../vendor/tightenco/ziggy';

export function l10n(name, params, absolute = true) {
    const localized = name.concat('.', params?.lang ?? document.documentElement.lang);

    delete params?.lang;

    if (ziggy().has(localized)) {
        name = localized;
    }

    return ziggy(name, params, absolute);
}
