import assert from 'node:assert/strict';
import { route } from '../../stubs/ziggy/l10n.js';

const definition = (uri) => ({
    uri,
    methods: ['GET', 'HEAD'],
});

const config = (canonical, localized = 'es/ejemplo/{id}') => ({
    url: 'https://example.com',
    port: null,
    defaults: {},
    routes: {
        example: definition(canonical),
        'example.es': definition(localized),
    },
});

Object.defineProperty(globalThis, 'document', {
    configurable: true,
    value: {
        documentElement: {
            lang: 'es',
        },
        getElementById: () => null,
    },
});

const strategies = [
    [config('example/{id}'), 'https://example.com/example/1', 'https://example.com/es/ejemplo/1'],
    [config('en/example/{id}'), 'https://example.com/en/example/1', 'https://example.com/es/ejemplo/1'],
    [config('example/{id}', 'ejemplo/{id}'), 'https://example.com/example/1', 'https://example.com/ejemplo/1'],
];

for (const [ziggy, canonical, localized] of strategies) {
    globalThis.Ziggy = ziggy;

    document.documentElement.lang = 'fr';
    assert.equal(route('example', { id: 1 }), canonical);

    document.documentElement.lang = 'es';
    assert.equal(route('example', { id: 1 }), localized);
}

const args = { id: 2, lang: 'es' };

route('example', args);

assert.deepEqual(args, { id: 2, lang: 'es' });
assert.equal(route().has('example.es'), true);

delete globalThis.Ziggy;

const explicit = config('example/{id}');

assert.equal(
    route('example', { id: 3, lang: 'es' }, true, explicit),
    'https://example.com/es/ejemplo/3',
);
assert.equal(route(undefined, undefined, true, explicit).has('example.es'), true);

const regional = {
    ...explicit,
    routes: {
        ...explicit.routes,
        'example.pt-BR': definition('pt-br/exemplo/{id}'),
        'example.pt_BR': definition('pt_br/exemplo/{id}'),
    },
};

document.documentElement.lang = 'pt-BR';

assert.equal(
    route('example', { id: 4 }, true, regional),
    'https://example.com/pt-br/exemplo/4',
);

const normalizedRegional = {
    ...explicit,
    routes: {
        ...explicit.routes,
        'example.pt_BR': definition('pt_br/exemplo/{id}'),
    },
};

assert.equal(
    route('example', { id: 5 }, true, normalizedRegional),
    'https://example.com/pt_br/exemplo/5',
);

delete globalThis.document;

assert.equal(route('example', 6, true, explicit), 'https://example.com/example/6');
assert.equal(
    route('example', { id: 7, lang: 'es' }, true, explicit),
    'https://example.com/es/ejemplo/7',
);
