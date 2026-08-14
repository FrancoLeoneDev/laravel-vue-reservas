import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Admin\ServiceController::index
 * @see app/Http/Controllers/Admin/ServiceController.php:16
 * @route '/admin/servicios'
 */
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/admin/servicios',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Admin\ServiceController::index
 * @see app/Http/Controllers/Admin/ServiceController.php:16
 * @route '/admin/servicios'
 */
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\ServiceController::index
 * @see app/Http/Controllers/Admin/ServiceController.php:16
 * @route '/admin/servicios'
 */
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Admin\ServiceController::index
 * @see app/Http/Controllers/Admin/ServiceController.php:16
 * @route '/admin/servicios'
 */
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Admin\ServiceController::index
 * @see app/Http/Controllers/Admin/ServiceController.php:16
 * @route '/admin/servicios'
 */
    const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: index.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Admin\ServiceController::index
 * @see app/Http/Controllers/Admin/ServiceController.php:16
 * @route '/admin/servicios'
 */
        indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: index.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Admin\ServiceController::index
 * @see app/Http/Controllers/Admin/ServiceController.php:16
 * @route '/admin/servicios'
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
* @see \App\Http\Controllers\Admin\ServiceController::store
 * @see app/Http/Controllers/Admin/ServiceController.php:29
 * @route '/admin/servicios'
 */
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/admin/servicios',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Admin\ServiceController::store
 * @see app/Http/Controllers/Admin/ServiceController.php:29
 * @route '/admin/servicios'
 */
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\ServiceController::store
 * @see app/Http/Controllers/Admin/ServiceController.php:29
 * @route '/admin/servicios'
 */
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

    /**
* @see \App\Http\Controllers\Admin\ServiceController::store
 * @see app/Http/Controllers/Admin/ServiceController.php:29
 * @route '/admin/servicios'
 */
    const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: store.url(options),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Admin\ServiceController::store
 * @see app/Http/Controllers/Admin/ServiceController.php:29
 * @route '/admin/servicios'
 */
        storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: store.url(options),
            method: 'post',
        })
    
    store.form = storeForm
/**
* @see \App\Http\Controllers\Admin\ServiceController::update
 * @see app/Http/Controllers/Admin/ServiceController.php:41
 * @route '/admin/servicios/{service}'
 */
export const update = (args: { service: number | { id: number } } | [service: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/admin/servicios/{service}',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\Admin\ServiceController::update
 * @see app/Http/Controllers/Admin/ServiceController.php:41
 * @route '/admin/servicios/{service}'
 */
update.url = (args: { service: number | { id: number } } | [service: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { service: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { service: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    service: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        service: typeof args.service === 'object'
                ? args.service.id
                : args.service,
                }

    return update.definition.url
            .replace('{service}', parsedArgs.service.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\ServiceController::update
 * @see app/Http/Controllers/Admin/ServiceController.php:41
 * @route '/admin/servicios/{service}'
 */
update.put = (args: { service: number | { id: number } } | [service: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

    /**
* @see \App\Http\Controllers\Admin\ServiceController::update
 * @see app/Http/Controllers/Admin/ServiceController.php:41
 * @route '/admin/servicios/{service}'
 */
    const updateForm = (args: { service: number | { id: number } } | [service: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: update.url(args, {
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'PUT',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Admin\ServiceController::update
 * @see app/Http/Controllers/Admin/ServiceController.php:41
 * @route '/admin/servicios/{service}'
 */
        updateForm.put = (args: { service: number | { id: number } } | [service: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
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
* @see \App\Http\Controllers\Admin\ServiceController::destroy
 * @see app/Http/Controllers/Admin/ServiceController.php:59
 * @route '/admin/servicios/{service}'
 */
export const destroy = (args: { service: number | { id: number } } | [service: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/admin/servicios/{service}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\Admin\ServiceController::destroy
 * @see app/Http/Controllers/Admin/ServiceController.php:59
 * @route '/admin/servicios/{service}'
 */
destroy.url = (args: { service: number | { id: number } } | [service: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { service: args }
    }

            if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
            args = { service: args.id }
        }
    
    if (Array.isArray(args)) {
        args = {
                    service: args[0],
                }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
                        service: typeof args.service === 'object'
                ? args.service.id
                : args.service,
                }

    return destroy.definition.url
            .replace('{service}', parsedArgs.service.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\ServiceController::destroy
 * @see app/Http/Controllers/Admin/ServiceController.php:59
 * @route '/admin/servicios/{service}'
 */
destroy.delete = (args: { service: number | { id: number } } | [service: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

    /**
* @see \App\Http\Controllers\Admin\ServiceController::destroy
 * @see app/Http/Controllers/Admin/ServiceController.php:59
 * @route '/admin/servicios/{service}'
 */
    const destroyForm = (args: { service: number | { id: number } } | [service: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
        action: destroy.url(args, {
                    [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                        _method: 'DELETE',
                        ...(options?.query ?? options?.mergeQuery ?? {}),
                    }
                }),
        method: 'post',
    })

            /**
* @see \App\Http\Controllers\Admin\ServiceController::destroy
 * @see app/Http/Controllers/Admin/ServiceController.php:59
 * @route '/admin/servicios/{service}'
 */
        destroyForm.delete = (args: { service: number | { id: number } } | [service: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
            action: destroy.url(args, {
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'DELETE',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'post',
        })
    
    destroy.form = destroyForm
const services = {
    index: Object.assign(index, index),
store: Object.assign(store, store),
update: Object.assign(update, update),
destroy: Object.assign(destroy, destroy),
}

export default services