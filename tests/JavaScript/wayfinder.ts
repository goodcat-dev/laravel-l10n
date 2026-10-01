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

type QueryOptions = {
    query?: Record<string, string>;
    mergeQuery?: Record<string, string>;
};

const queryString = (options?: QueryOptions): string => {
    const query = new URLSearchParams(options?.query ?? options?.mergeQuery).toString();

    return query ? `?${query}` : '';
};

const searchablePosts = {
    __canonical: (args: PostArguments, options?: QueryOptions): Definition => ({
        method: 'get',
        url: `/posts/${postId(args)}${queryString(options)}`,
    }),
    es: (args: PostArguments, options?: QueryOptions): Definition => ({
        method: 'get',
        url: `/es/articulos/${postId(args)}${queryString(options)}`,
    }),
};

const queryOptions = { query: { page: '2' } };

expectUrl(route(searchablePosts, 1, queryOptions), '/posts/1?page=2');
expectUrl(route(searchablePosts, [2], queryOptions), '/posts/2?page=2');
expectUrl(route(searchablePosts, { post: 3, lang: 'es' }, queryOptions), '/es/articulos/3?page=2');
expectUrl(route(searchablePosts, { post: 4, lang: 'fr' }, queryOptions), '/posts/4?page=2');
expectUrl(route(searchablePosts, { post: 5 }, { mergeQuery: { filter: 'active' } }), '/posts/5?filter=active');

const index = {
    __canonical: (options?: QueryOptions): Definition => ({ method: 'get', url: `/posts${queryString(options)}` }),
    es: (options?: QueryOptions): Definition => ({ method: 'get', url: `/es/articulos${queryString(options)}` }),
};

expectUrl(route(index), '/posts');
expectUrl(route(index, undefined), '/posts');
expectUrl(route(index, queryOptions), '/posts?page=2');
expectUrl(route(index, { lang: 'es', ...queryOptions }), '/es/articulos?page=2');
expectUrl(route(index, { lang: 'es' }), '/es/articulos');

const optionalPost = {
    __canonical: (args?: PostArguments, options?: QueryOptions): Definition => ({
        method: 'get',
        url: `/posts/${args === undefined ? '' : postId(args)}${queryString(options)}`,
    }),
};

expectUrl(route(optionalPost), '/posts/');
expectUrl(route(optionalPost, undefined, queryOptions), '/posts/?page=2');
expectUrl(route(optionalPost, { post: 1, lang: 'fr' }, queryOptions), '/posts/1?page=2');

Object.defineProperty(globalThis, 'document', {
    configurable: true,
    value: { documentElement: { lang: 'es' } },
});

expectUrl(route(searchablePosts, 6, queryOptions), '/es/articulos/6?page=2');
expectUrl(route(index, queryOptions), '/es/articulos?page=2');

Reflect.deleteProperty(globalThis, 'document');

const forwardedArgs: unknown[][] = [];
const capture = {
    __canonical: (...args: [options?: QueryOptions]): Definition => {
        forwardedArgs.push(args);

        return { method: 'get', url: '/capture' };
    },
};

route(capture);
route(capture, undefined);
route(capture, queryOptions);
const localizedOptions = { lang: 'fr', ...queryOptions };
route(capture, localizedOptions);

if (forwardedArgs[0].length !== 0 || forwardedArgs[1].length !== 1 || forwardedArgs[2][0] !== queryOptions) {
    throw new Error('The route helper did not preserve the forwarded arguments.');
}

if ('lang' in (forwardedArgs[3][0] as object) || localizedOptions.lang !== 'fr'
    || (forwardedArgs[3][0] as QueryOptions).query !== queryOptions.query) {
    throw new Error('The route helper did not remove lang from a copy of the options.');
}

function checkInvalidArguments(): void {
    // @ts-expect-error The generated route requires a post argument.
    route(posts);
    // @ts-expect-error An explicit undefined cannot replace a required argument.
    route(posts, undefined);
    // @ts-expect-error Query options do not make the post argument optional.
    route(searchablePosts, undefined, queryOptions);
    // @ts-expect-error An explicit locale does not replace the post argument.
    route(searchablePosts, { lang: 'es' }, queryOptions);
    // @ts-expect-error Query options must retain their generated type.
    route(searchablePosts, 1, { invalid: true });
    // @ts-expect-error Parameterless routes accept options as their first argument only.
    route(index, {}, queryOptions);
}
