import { useI18n } from 'vue-i18n';

export function useL10n() {
    const { locale, fallbackLocale } = useI18n();

    const getFallbackLocale = (): string => {
        const value = fallbackLocale.value;

        if (typeof value === 'string') {
            return value;
        }

        if (Array.isArray(value)) {
            return value[0];
        }

        return value?.default?.[0] ?? 'en';
    };

    const route = <T extends (...args: any[]) => any>(
        routes: Record<string, T>,
        args?: Parameters<T>[0] & { lang?: string },
    ): ReturnType<T> => {
        const desiredLocale = args?.lang ?? locale.value;

        delete args?.lang;

        const route = routes[desiredLocale] ?? routes[getFallbackLocale()];

        return args && Object.keys(args).length
            ? route(args)
            : route();
    };

    return { route };
}
