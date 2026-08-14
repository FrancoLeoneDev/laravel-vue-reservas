import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
import services from './services'
import availability from './availability'
import bookings from './bookings'
/**
* @see \App\Http\Controllers\Admin\AgendaController::agenda
 * @see app/Http/Controllers/Admin/AgendaController.php:25
 * @route '/admin'
 */
export const agenda = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: agenda.url(options),
    method: 'get',
})

agenda.definition = {
    methods: ["get","head"],
    url: '/admin',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Admin\AgendaController::agenda
 * @see app/Http/Controllers/Admin/AgendaController.php:25
 * @route '/admin'
 */
agenda.url = (options?: RouteQueryOptions) => {
    return agenda.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Admin\AgendaController::agenda
 * @see app/Http/Controllers/Admin/AgendaController.php:25
 * @route '/admin'
 */
agenda.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: agenda.url(options),
    method: 'get',
})
/**
* @see \App\Http\Controllers\Admin\AgendaController::agenda
 * @see app/Http/Controllers/Admin/AgendaController.php:25
 * @route '/admin'
 */
agenda.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: agenda.url(options),
    method: 'head',
})

    /**
* @see \App\Http\Controllers\Admin\AgendaController::agenda
 * @see app/Http/Controllers/Admin/AgendaController.php:25
 * @route '/admin'
 */
    const agendaForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
        action: agenda.url(options),
        method: 'get',
    })

            /**
* @see \App\Http\Controllers\Admin\AgendaController::agenda
 * @see app/Http/Controllers/Admin/AgendaController.php:25
 * @route '/admin'
 */
        agendaForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: agenda.url(options),
            method: 'get',
        })
            /**
* @see \App\Http\Controllers\Admin\AgendaController::agenda
 * @see app/Http/Controllers/Admin/AgendaController.php:25
 * @route '/admin'
 */
        agendaForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
            action: agenda.url({
                        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
                            _method: 'HEAD',
                            ...(options?.query ?? options?.mergeQuery ?? {}),
                        }
                    }),
            method: 'get',
        })
    
    agenda.form = agendaForm
const admin = {
    agenda: Object.assign(agenda, agenda),
services: Object.assign(services, services),
availability: Object.assign(availability, availability),
bookings: Object.assign(bookings, bookings),
}

export default admin