import { route as ziggy } from '../../vendor/tightenco/ziggy';

type Params = Record<string, unknown> & { lang?: string };

export function route(name: string, params?: Params, absolute = true): string {
    const localized = name.concat('.', params?.lang ?? document.documentElement.lang);

    delete params?.lang;

    if (ziggy().has(localized)) {
        name = localized;
    }

    return ziggy(name, params, absolute);
}
