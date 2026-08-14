import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Admin\AvailabilityController::index
 * @see app/Http/Controllers/Admin/AvailabilityController.php:14
 * @route '/admin/disponibilidad'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/admin/disponibilidad',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Admin\AvailabilityController::index
 * @see app/Http/Controllers/Admin/AvailabilityController.php:14
 * @route '/admin/disponibilidad'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\AvailabilityController::index
 * @see app/Http/Controllers/Admin/AvailabilityController.php:14
 * @route '/admin/disponibilidad'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Admin\AvailabilityController::index
 * @see app/Http/Controllers/Admin/AvailabilityController.php:14
 * @route '/admin/disponibilidad'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Admin\AvailabilityController::index
 * @see app/Http/Controllers/Admin/AvailabilityController.php:14
 * @route '/admin/disponibilidad'
 */
    const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: index.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Admin\AvailabilityController::index
 * @see app/Http/Controllers/Admin/AvailabilityController.php:14
 * @route '/admin/disponibilidad'
 */
        indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Admin\AvailabilityController::index
 * @see app/Http/Controllers/Admin/AvailabilityController.php:14
 * @route '/admin/disponibilidad'
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
* @see \App\Http\Controllers\Admin\AvailabilityController::store
 * @see app/Http/Controllers/Admin/AvailabilityController.php:44
 * @route '/admin/disponibilidad'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/admin/disponibilidad',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Admin\AvailabilityController::store
 * @see app/Http/Controllers/Admin/AvailabilityController.php:44
 * @route '/admin/disponibilidad'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\AvailabilityController::store
 * @see app/Http/Controllers/Admin/AvailabilityController.php:44
 * @route '/admin/disponibilidad'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Admin\AvailabilityController::store
 * @see app/Http/Controllers/Admin/AvailabilityController.php:44
 * @route '/admin/disponibilidad'
 */
    const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: store.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Admin\AvailabilityController::store
 * @see app/Http/Controllers/Admin/AvailabilityController.php:44
 * @route '/admin/disponibilidad'
 */
        storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: store.url(options),
            method: 'post',
        })
    
    store.form = storeForm
/**
* @see \App\Http\Controllers\Admin\AvailabilityController::update
 * @see app/Http/Controllers/Admin/AvailabilityController.php:53
 * @route '/admin/disponibilidad/{availability}'
 */
export const update = (args: { availability: number | { id: number } } | [availability: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/admin/disponibilidad/{availability}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\Admin\AvailabilityController::update
 * @see app/Http/Controllers/Admin/AvailabilityController.php:53
 * @route '/admin/disponibilidad/{availability}'
 */
update.url = (args: { availability: number | { id: number } } | [availability: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { availability: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { availability: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    availability: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        availability: typeof args.availability === 'object'
                ? args.availability.id
                : args.availability,
                }

    return update.definition.url
            .replace('{availability}', parsedArgs.availability.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\AvailabilityController::update
 * @see app/Http/Controllers/Admin/AvailabilityController.php:53
 * @route '/admin/disponibilidad/{availability}'
 */
update.put = (args: { availability: number | { id: number } } | [availability: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

    /**
* @see \App\Http\Controllers\Admin\AvailabilityController::update
 * @see app/Http/Controllers/Admin/AvailabilityController.php:53
 * @route '/admin/disponibilidad/{availability}'
 */
    const updateForm = (args: { availability: number | { id: number } } | [availability: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: update.url(args, {
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'PUT',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Admin\AvailabilityController::update
 * @see app/Http/Controllers/Admin/AvailabilityController.php:53
 * @route '/admin/disponibilidad/{availability}'
 */
        updateForm.put = (args: { availability: number | { id: number } } | [availability: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: update.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'PUT',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    update.form = updateForm
/**
* @see \App\Http\Controllers\Admin\AvailabilityController::destroy
 * @see app/Http/Controllers/Admin/AvailabilityController.php:68
 * @route '/admin/disponibilidad/{availability}'
 */
export const destroy = (args: { availability: number | { id: number } } | [availability: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/admin/disponibilidad/{availability}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\Admin\AvailabilityController::destroy
 * @see app/Http/Controllers/Admin/AvailabilityController.php:68
 * @route '/admin/disponibilidad/{availability}'
 */
destroy.url = (args: { availability: number | { id: number } } | [availability: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { availability: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { availability: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    availability: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        availability: typeof args.availability === 'object'
                ? args.availability.id
                : args.availability,
                }

    return destroy.definition.url
            .replace('{availability}', parsedArgs.availability.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\AvailabilityController::destroy
 * @see app/Http/Controllers/Admin/AvailabilityController.php:68
 * @route '/admin/disponibilidad/{availability}'
 */
destroy.delete = (args: { availability: number | { id: number } } | [availability: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

    /**
* @see \App\Http\Controllers\Admin\AvailabilityController::destroy
 * @see app/Http/Controllers/Admin/AvailabilityController.php:68
 * @route '/admin/disponibilidad/{availability}'
 */
    const destroyForm = (args: { availability: number | { id: number } } | [availability: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: destroy.url(args, {
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'DELETE',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Admin\AvailabilityController::destroy
 * @see app/Http/Controllers/Admin/AvailabilityController.php:68
 * @route '/admin/disponibilidad/{availability}'
 */
        destroyForm.delete = (args: { availability: number | { id: number } } | [availability: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: destroy.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'DELETE',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    destroy.form = destroyForm
const availability = {
    index: Object.assign(index, index),
store: Object.assign(store, store),
update: Object.assign(update, update),
destroy: Object.assign(destroy, destroy),
}

export default availability