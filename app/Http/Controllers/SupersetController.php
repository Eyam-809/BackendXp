<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;

class SupersetController extends Controller
{
    public function guestToken(Request $request)
    {
        $supersetUrl = rtrim(config('services.superset.url'), '/');
        $username    = config('services.superset.username');
        $password    = config('services.superset.password');

        // Cliente Guzzle con cookie jar activado
        $client = new Client([
            'base_uri' => $supersetUrl,
            'cookies'  => true, // 👈 mantiene las cookies entre requests
            'timeout'  => 10,
        ]);

        try {
            // 1) LOGIN -> access_token
            $loginRes = $client->post('/api/v1/security/login', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                ],
                'json' => [
                    'username' => $username,
                    'password' => $password,
                    'provider' => 'db',
                    'refresh'  => true,
                ],
            ]);

            $loginBody   = json_decode($loginRes->getBody()->getContents(), true);
            $accessToken = $loginBody['access_token'] ?? null;

            if (! $accessToken) {
                return response()->json([
                    'error'   => 'No se obtuvo access_token desde Superset',
                    'details' => $loginBody,
                ], 500);
            }

            // 2) CSRF token (el mismo cliente guarda las cookies de sesión)
            $csrfRes = $client->get('/api/v1/security/csrf_token/', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept'        => 'application/json',
                ],
            ]);

            $csrfBody  = json_decode($csrfRes->getBody()->getContents(), true);
            $csrfToken = $csrfBody['result'] ?? null;

            if (! $csrfToken) {
                return response()->json([
                    'error'   => 'No se pudo obtener el CSRF token',
                    'details' => $csrfBody,
                ], 500);
            }

            // 3) Guest token (cookies ya van dentro gracias al cookie jar)
            $payload = [
                'user' => [
                    'username'   => $request->user()->email ?? 'guest',
                    'first_name' => $request->user()->name ?? 'Guest',
                    'last_name'  => '',
                ],
                'resources' => [[
                    'type' => 'dashboard',
                    'id'   => 'f8416863-b8d0-4013-bf37-92d66d027b01', // tu UUID
                ]],
                'rls' => [],
                'aud' => 'superset',
            ];

            $guestRes = $client->post('/api/v1/security/guest_token/', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'X-CSRFToken'   => $csrfToken,
                    'Content-Type'  => 'application/json',
                    'Accept'        => 'application/json',
                ],
                'json' => $payload,
            ]);

            $guestBody  = json_decode($guestRes->getBody()->getContents(), true);
            $guestToken = $guestBody['token'] ?? null;

            if (! $guestToken) {
                return response()->json([
                    'error'   => 'Superset no regresó token',
                    'details' => $guestBody,
                ], 500);
            }

            return response()->json(['token' => $guestToken]);
        } catch (\Throwable $e) {
            return response()->json([
                'error'   => 'Excepción al generar guest token',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}
