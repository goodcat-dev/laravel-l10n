import { route } from '../../stubs/wayfinder/l10n.ts';

type Arguments = {
    id?: number;
    lang?: string;
};

type Definition = {
    method: 'get';
    url: string;
};

const canonical = (args: Arguments = {}): Definition => ({
    method: 'get',
    url: `/example/${args.id ?? ''}`,
});

const es = (args: Arguments = {}): Definition => ({
    method: 'get',
    url: `/es/ejemplo/${args.id ?? ''}`,
});

const routes = { __canonical: canonical, es };

const expectUrl = (actual: Definition, expected: string): void => {
    if (actual.url !== expected) {
        throw new Error(`Expected ${expected}, received ${actual.url}`);
    }
};

Object.defineProperty(globalThis, 'document', {
    value: {
        documentElement: {
            lang: 'es',
        },
    },
});

document.documentElement.lang = 'es';

expectUrl(route(routes), '/es/ejemplo/');
expectUrl(route(routes, { id: 1 }), '/es/ejemplo/1');
expectUrl(route(routes, { id: 2, lang: 'es' }), '/es/ejemplo/2');
expectUrl(route(routes, { id: 3, lang: 'fr' }), '/example/3');

const args = { id: 4, lang: 'es' };

route(routes, args);

if (args.lang !== 'es') {
    throw new Error('The route helper mutated the caller arguments.');
}

document.documentElement.lang = 'fr';

expectUrl(route(routes), '/example/');
expectUrl(route(routes, { id: 5 }), '/example/5');
