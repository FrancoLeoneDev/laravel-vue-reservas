<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

/**
 * Endpoint que dispara el reset de la demo, pensado para el cron de Vercel.
 *
 * En serverless no hay un scheduler corriendo (`schedule:run` necesita un proceso
 * vivo), así que la plataforma pega a esta URL una vez por día. Vercel manda el
 * header `Authorization: Bearer $CRON_SECRET`, y sin ese secreto acá no entra nadie:
 * si no, sería un botón público para borrar la base.
 */
class ResetDemoController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $secret = config('demo.cron_secret');

        abort_if($secret === null || $secret === '', 404);

        // hash_equals para no filtrar el secreto por diferencia de tiempos.
        abort_unless(
            hash_equals($secret, (string) $request->bearerToken()),
            403,
            'Token inválido.',
        );

        Artisan::call('demo:reset');

        return response()->json([
            'status' => 'ok',
            'output' => trim(Artisan::output()),
        ]);
    }
}
