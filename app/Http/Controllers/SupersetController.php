<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class SupersetController extends Controller
{
    public function guestToken(Request $request)
    {
        $apiKey      = config('services.superset.api_key');
        $apiSecret   = config('services.superset.api_secret');
        $teamId      = config('services.superset.team_id');        // 6b49e5a3
        $workspaceId = config('services.superset.workspace_id');   // 6c152ecc

        // ID de tu dashboard embebido (puedes sobreescribirlo desde el front)
        $dashboardId = $request->input('dashboard_id', '5ce05b5b-db97-45fe-8166-c190312f555b');

        try {
            $client = new Client([
                'timeout' => 10,
            ]);

            // 1) Obtener access_token con tus API key + secret
            $authResponse = $client->post('https://api.app.preset.io/v1/auth/', [
                'json' => [
                    'name'   => $apiKey,
                    'secret' => $apiSecret,
                ],
                'headers' => [
                    'Accept'       => 'application/json',
                    'Content-Type' => 'application/json',
                ],
            ]);

            $authBody    = json_decode($authResponse->getBody()->getContents(), true);
            $accessToken = $authBody['payload']['access_token'] ?? null;

            if (!$accessToken) {
                throw new \Exception('No se pudo obtener access_token de Preset');
            }

            // 2) Pedir guest-token al Manager API
            $payload = [
                "user" => [
                    "username"   => "guest_".uniqid(),
                    "first_name" => "Guest",
                    "last_name"  => "",
                ],
                "resources" => [
                    ["type" => "dashboard", "id" => $dashboardId],
                ],
                "rls" => [],
            ];

            $guestResponse = $client->post(
                "https://manage.app.preset.io/api/v1/teams/{$teamId}/workspaces/{$workspaceId}/guest-token/",
                [
                    'headers' => [
                        // según docs: se manda el token directo en Authorization
                        'Authorization' => $accessToken,
                        'Accept'        => 'application/json',
                        'Content-Type'  => 'application/json',
                    ],
                    'json' => $payload,
                ]
            );

            $guestBody = json_decode($guestResponse->getBody()->getContents(), true);

            // Estructura de respuesta según docs de embed
            $token = $guestBody['data']['payload']['token'] ?? null;

            if (!$token) {
                throw new \Exception('Preset no regresó guest token');
            }

            return response()->json(['token' => $token]);

        } catch (\Throwable $e) {
            Log::error('Error al pedir guest token', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
