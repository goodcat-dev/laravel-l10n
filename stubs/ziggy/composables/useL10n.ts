import { route as ziggy } from '../../../vendor/tightenco/ziggy';
import { useI18n } from 'vue-i18n';

type Params = Record<string, unknown> & { lang?: string };

export function useL10n() {
    const { locale } = useI18n();

    const route = (name: string, params?: Params, absolute = true): string => {
        const localized = name.concat('.', params?.lang ?? locale.value);

        delete params?.lang;

        if (ziggy().has(localized)) {
            name = localized;
        }

        return ziggy(name, params, absolute);
    };

    return { route };
}
