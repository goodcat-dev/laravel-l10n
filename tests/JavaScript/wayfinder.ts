import { route } from '../../resources/js/wayfinder.ts';

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
    configurable: true,
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

type PostArguments = number | [post: number] | { post: number };

const postId = (args: PostArguments): number =>
    typeof args === 'number' ? args : Array.isArray(args) ? args[0] : args.post;

const posts = {
    __canonical: (args: PostArguments): Definition => ({ method: 'get', url: `/posts/${postId(args)}` }),
    es: (args: PostArguments): Definition => ({ method: 'get', url: `/es/articulos/${postId(args)}` }),
};

expectUrl(route(posts, 1), '/es/articulos/1');
expectUrl(route(posts, [2]), '/es/articulos/2');
expectUrl(route(posts, { post: 3, lang: 'es' }), '/es/articulos/3');
expectUrl(route(posts, { post: 4, lang: 'fr' }), '/posts/4');

const tuple: [post: number] = [5];
expectUrl(route(posts, tuple), '/es/articulos/5');

if (tuple[0] !== 5) {
    throw new Error('The route helper mutated the caller tuple.');
}

const args = { id: 4, lang: 'es' };

route(routes, args);

if (args.lang !== 'es') {
    throw new Error('The route helper mutated the caller arguments.');
}

document.documentElement.lang = 'fr';

expectUrl(route(routes), '/example/');
expectUrl(route(routes, { id: 5 }), '/example/5');

const pt_BR = (args: Arguments = {}): Definition => ({
    method: 'get',
    url: `/pt/exemplo/${args.id ?? ''}`,
});

const zh_Hant_TW = (args: Arguments = {}): Definition => ({
    method: 'get',
    url: `/zh/example/${args.id ?? ''}`,
});

const regional = { __canonical: canonical, pt_BR, zh_Hant_TW };

document.documentElement.lang = 'pt-BR';

expectUrl(route(regional, { id: 6 }), '/pt/exemplo/6');
expectUrl(route(regional, { id: 7, lang: 'pt-BR' }), '/pt/exemplo/7');
expectUrl(route(regional, { id: 8, lang: 'pt_BR' }), '/pt/exemplo/8');

document.documentElement.lang = 'zh-Hant-TW';

expectUrl(route(regional, { id: 9 }), '/zh/example/9');

Reflect.deleteProperty(globalThis, 'document');

expectUrl(route(regional, { id: 10, lang: 'pt-BR' }), '/pt/exemplo/10');
expectUrl(route(regional, { id: 11 }), '/example/11');
expectUrl(route(posts, 6), '/posts/6');
expectUrl(route(posts, [7]), '/posts/7');
