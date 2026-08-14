<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Front controller para Vercel
|--------------------------------------------------------------------------
|
| Vercel descubre las funciones dentro de `api/`, así que este archivo reemplaza
| a `public/index.php` en producción. `public/index.php` sigue existiendo y sigue
| siendo el front controller local; simplemente no se sube (ver .vercelignore).
|
| Lo único que hace de más es mover las rutas escribibles de Laravel a /tmp, que
| es el único lugar con permiso de escritura en el runtime serverless.
|
*/

define('LARAVEL_START', microtime(true));

require __DIR__.'/../vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

// Se detecta el filesystem de sólo lectura PROBÁNDOLO, no leyendo la variable
// VERCEL: esa variable sólo existe si el proyecto habilitó exponer las variables
// de sistema. Si se condicionara a ella y no estuviera, Laravel arrancaría contra
// un storage de sólo lectura y moriría con "Target class [view] does not exist",
// un error que no señala en absoluto la causa real.
if (! is_writable(__DIR__.'/../storage/framework')) {
    $storagePath = '/tmp/storage';

    foreach ([
        $storagePath.'/app/public',
        $storagePath.'/framework/cache/data',
        $storagePath.'/framework/sessions',
        $storagePath.'/framework/views',
        $storagePath.'/logs',
        // Acá se construye el manifiesto de paquetes en la primera request.
        // Ver APP_PACKAGES_CACHE / APP_SERVICES_CACHE en las variables de entorno.
        '/tmp/bootstrap-cache',
    ] as $directory) {
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }

    $app->useStoragePath($storagePath);
}

$app->handleRequest(Request::capture());
