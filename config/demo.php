<?php

/*
|--------------------------------------------------------------------------
| Modo demostración
|--------------------------------------------------------------------------
|
| Este proyecto es una pieza de portfolio: la idea es que cualquiera pueda entrar
| de un clic y ver las dos caras del sistema (cliente y administrador) sin tener
| que registrarse.
|
| Estas credenciales se comparten con el frontend y se pintan en el login. En una
| aplicación real esto se apaga con DEMO_MODE=false y listo — por eso vive detrás
| de una config y no hardcodeado en un componente Vue.
|
*/

return [
    'enabled' => (bool) env('DEMO_MODE', false),

    /*
    | Secreto que protege el endpoint de reinicio de la demo. Lo manda el cron de
    | Vercel como bearer token. Si está vacío, el endpoint devuelve 404.
    */
    'cron_secret' => env('CRON_SECRET'),

    'accounts' => [
        [
            'role' => 'Administrador',
            'description' => 'Agenda del día y de la semana, servicios, horarios y estados de las reservas.',
            'email' => 'admin@demo.com',
            'password' => 'password',
        ],
        [
            'role' => 'Cliente',
            'description' => 'Reservar un turno, ver "Mis reservas" y cancelar.',
            'email' => 'cliente@demo.com',
            'password' => 'password',
        ],
    ],
];
