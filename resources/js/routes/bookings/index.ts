import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\BookingController::create
 * @see app/Http/Controllers/BookingController.php:32
 * @route '/reservar/{service}'
 */
export const create = (args: { service: string | { slug: string } } | [service: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(args, options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/reservar/{service}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BookingController::create
 * @see app/Http/Controllers/BookingController.php:32
 * @route '/reservar/{service}'
 */
create.url = (args: { service: string | { slug: string } } | [service: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { service: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'slug' in args) {
            args = { service: args.slug }
        }
    
    if (Array.isArray(args)) {
        args = {
                    service: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        service: typeof args.service === 'object'
                ? args.service.slug
                : args.service,
                }

    return create.definition.url
            .replace('{service}', parsedArgs.service.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BookingController::create
 * @see app/Http/Controllers/BookingController.php:32
 * @route '/reservar/{service}'
 */
create.get = (args: { service: string | { slug: string } } | [service: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(args, options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\BookingController::create
 * @see app/Http/Controllers/BookingController.php:32
 * @route '/reservar/{service}'
 */
create.head = (args: { service: string | { slug: string } } | [service: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(args, options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\BookingController::create
 * @see app/Http/Controllers/BookingController.php:32
 * @route '/reservar/{service}'
 */
    const createForm = (args: { service: string | { slug: string } } | [service: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: create.url(args, options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\BookingController::create
 * @see app/Http/Controllers/BookingController.php:32
 * @route '/reservar/{service}'
 */
        createForm.get = (args: { service: string | { slug: string } } | [service: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: create.url(args, options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\BookingController::create
 * @see app/Http/Controllers/BookingController.php:32
 * @route '/reservar/{service}'
 */
        createForm.head = (args: { service: string | { slug: string } } | [service: string | { slug: string } ] | string | { slug: string }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: create.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    create.form = createForm
/**
* @see \App\Http\Controllers\BookingController::store
 * @see app/Http/Controllers/BookingController.php:63
 * @route '/reservas'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/reservas',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BookingController::store
 * @see app/Http/Controllers/BookingController.php:63
 * @route '/reservas'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\BookingController::store
 * @see app/Http/Controllers/BookingController.php:63
 * @route '/reservas'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\BookingController::store
 * @see app/Http/Controllers/BookingController.php:63
 * @route '/reservas'
 */
    const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: store.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\BookingController::store
 * @see app/Http/Controllers/BookingController.php:63
 * @route '/reservas'
 */
        storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: store.url(options),
            method: 'post',
        })
    
    store.form = storeForm
/**
* @see \App\Http\Controllers\BookingController::index
 * @see app/Http/Controllers/BookingController.php:88
 * @route '/mis-reservas'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/mis-reservas',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\BookingController::index
 * @see app/Http/Controllers/BookingController.php:88
 * @route '/mis-reservas'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\BookingController::index
 * @see app/Http/Controllers/BookingController.php:88
 * @route '/mis-reservas'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\BookingController::index
 * @see app/Http/Controllers/BookingController.php:88
 * @route '/mis-reservas'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\BookingController::index
 * @see app/Http/Controllers/BookingController.php:88
 * @route '/mis-reservas'
 */
    const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: index.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\BookingController::index
 * @see app/Http/Controllers/BookingController.php:88
 * @route '/mis-reservas'
 */
        indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\BookingController::index
 * @see app/Http/Controllers/BookingController.php:88
 * @route '/mis-reservas'
 */
        indexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    index.form = indexForm
/**
* @see \App\Http\Controllers\BookingController::destroy
 * @see app/Http/Controllers/BookingController.php:109
 * @route '/reservas/{booking}'
 */
export const destroy = (args: { booking: number | { id: number } } | [booking: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/reservas/{booking}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\BookingController::destroy
 * @see app/Http/Controllers/BookingController.php:109
 * @route '/reservas/{booking}'
 */
destroy.url = (args: { booking: number | { id: number } } | [booking: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { booking: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { booking: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    booking: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        booking: typeof args.booking === 'object'
                ? args.booking.id
                : args.booking,
                }

    return destroy.definition.url
            .replace('{booking}', parsedArgs.booking.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\BookingController::destroy
 * @see app/Http/Controllers/BookingController.php:109
 * @route '/reservas/{booking}'
 */
destroy.delete = (args: { booking: number | { id: number } } | [booking: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

    /**
* @see \App\Http\Controllers\BookingController::destroy
 * @see app/Http/Controllers/BookingController.php:109
 * @route '/reservas/{booking}'
 */
    const destroyForm = (args: { booking: number | { id: number } } | [booking: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: destroy.url(args, {
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'DELETE',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\BookingController::destroy
 * @see app/Http/Controllers/BookingController.php:109
 * @route '/reservas/{booking}'
 */
        destroyForm.delete = (args: { booking: number | { id: number } } | [booking: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: destroy.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'DELETE',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    destroy.form = destroyForm
const bookings = {
    create: Object.assign(create, create),
store: Object.assign(store, store),
index: Object.assign(index, index),
destroy: Object.assign(destroy, destroy),
}

export default bookings