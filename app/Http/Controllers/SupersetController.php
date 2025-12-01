<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class SupersetController extends Controller
{
    public function guestToken(Request $request)
    {
        $supersetUrl = rtrim(config('services.superset.url'), '/'); // e.g. https://mi-superset.example.com
        $username    = config('services.superset.username'); // admin user
        $password    = config('services.superset.password');

        $client = new Client([
            'base_uri' => $supersetUrl,
            'cookies'  => true,
            'timeout'  => 10,
            // 'verify' => false, // solo si usas certificados auto-firmados (no recomendado)
        ]);

        try {
            // 1) Login -> access_token
            $loginRes = $client->post('/api/v1/security/login', [
                'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                'json' => [
                    'username' => $username,
                    'password' => $password,
                    'provider' => 'db',
                    'refresh'  => true,
                ],
            ]);
            $loginBody = json_decode($loginRes->getBody()->getContents(), true);
            $accessToken = $loginBody['access_token'] ?? null;
            if (! $accessToken) {
                Log::error('Superset login failed', ['body' => $loginBody]);
                return response()->json(['error' => 'No se obtuvo access_token'], 500);
            }

            // 2) CSRF token
            $csrfRes = $client->get('/api/v1/security/csrf_token/', [
                'headers' => ['Authorization' => 'Bearer ' . $accessToken, 'Accept' => 'application/json'],
            ]);
            $csrfBody = json_decode($csrfRes->getBody()->getContents(), true);
            $csrfToken = $csrfBody['result'] ?? null;
            if (! $csrfToken) {
                Log::error('No se obtuvo CSRF token', ['body' => $csrfBody]);
                return response()->json(['error' => 'No se obtuvo CSRF token'], 500);
            }

            // 3) Guest token
            $dashboardUuid = $request->input('dashboard_uuid', 'f8416863-b8d0-4013-bf37-92d66d027b01'); // cambiar si quieres
            // Evitar error cuando NO hay login
            $user = $request->user();

            $payload = [
                'user' => [
                    'username'   => $user ? $user->email : 'guest',
                    'first_name' => $user ? $user->name  : 'Guest',
                    'last_name'  => '',
                ],
                'resources' => [['type' => 'dashboard', 'id' => $dashboardUuid]],
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

            $guestBody = json_decode($guestRes->getBody()->getContents(), true);
            $guestToken = $guestBody['token'] ?? null;
            if (! $guestToken) {
                Log::error('No se obtuvo guest token', ['body' => $guestBody]);
                return response()->json(['error' => 'No se obtuvo guest token', 'details' => $guestBody], 500);
            }

            return response()->json(['token' => $guestToken]);
        } catch (\Throwable $e) {
            Log::error('Excepción al generar guest token', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Excepción al generar guest token', 'details' => $e->getMessage()], 500);
        }
    }
}
