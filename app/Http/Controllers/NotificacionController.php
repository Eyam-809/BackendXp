<?php

namespace App\Http\Controllers;

use App\Services\WhatsAppService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NotificacionController extends Controller
{
    private $whatsappService;

    public function __construct()
    {
        $this->whatsappService = new WhatsAppService();
    }

    /**
     * Envía notificación de oferta a todos los usuarios con teléfono
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function enviarOfertaMasiva(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'titulo' => 'required|string|max:255',
                'descripcion' => 'required|string|max:1000',
                'descuento' => 'nullable|string|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 400);
            }

            // Obtener todos los usuarios con teléfono válido
            $usuarios = User::whereNotNull('telefono')
                           ->where('telefono', '!=', '')
                           ->get();

            $enviados = 0;
            $errores = 0;
            $resultados = [];

            foreach ($usuarios as $usuario) {
                if ($this->whatsappService->isValidPhoneNumber($usuario->telefono)) {
                    $resultado = $this->whatsappService->sendOfferNotification(
                        $usuario->telefono,
                        $request->titulo,
                        $request->descripcion,
                        $request->descuento
                    );

                    if ($resultado['success']) {
                        $enviados++;
                        Log::info('Oferta enviada por WhatsApp', [
                            'user_id' => $usuario->id,
                            'phone' => $usuario->telefono,
                            'offer' => $request->titulo
                        ]);
                    } else {
                        $errores++;
                        Log::warning('Error al enviar oferta por WhatsApp', [
                            'user_id' => $usuario->id,
                            'phone' => $usuario->telefono,
                            'error' => $resultado['error'] ?? 'Error desconocido'
                        ]);
                    }

                    $resultados[] = [
                        'usuario' => $usuario->name,
                        'telefono' => $usuario->telefono,
                        'exitoso' => $resultado['success'],
                        'error' => $resultado['error'] ?? null
                    ];
                } else {
                    $errores++;
                    $resultados[] = [
                        'usuario' => $usuario->name,
                        'telefono' => $usuario->telefono,
                        'exitoso' => false,
                        'error' => 'Número de teléfono inválido'
                    ];
                }
            }

            return response()->json([
                'message' => 'Proceso de envío completado',
                'estadisticas' => [
                    'total_usuarios' => $usuarios->count(),
                    'mensajes_enviados' => $enviados,
                    'errores' => $errores
                ],
                'resultados' => $resultados
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error en envío masivo de ofertas', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Error interno del servidor',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Envía notificación de oferta a un usuario específico
     *
     * @param Request $request
     * @param int $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function enviarOfertaUsuario(Request $request, $userId)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'titulo' => 'required|string|max:255',
                'descripcion' => 'required|string|max:1000',
                'descuento' => 'nullable|string|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 400);
            }

            $usuario = User::findOrFail($userId);

            if (!$usuario->telefono) {
                return response()->json([
                    'error' => 'El usuario no tiene número de teléfono registrado'
                ], 400);
            }

            if (!$this->whatsappService->isValidPhoneNumber($usuario->telefono)) {
                return response()->json([
                    'error' => 'El número de teléfono del usuario no es válido'
                ], 400);
            }

            $resultado = $this->whatsappService->sendOfferNotification(
                $usuario->telefono,
                $request->titulo,
                $request->descripcion,
                $request->descuento
            );

            if ($resultado['success']) {
                Log::info('Oferta enviada por WhatsApp a usuario específico', [
                    'user_id' => $usuario->id,
                    'phone' => $usuario->telefono,
                    'offer' => $request->titulo
                ]);

                return response()->json([
                    'message' => 'Oferta enviada exitosamente',
                    'usuario' => $usuario->name,
                    'telefono' => $usuario->telefono,
                    'message_sid' => $resultado['message_sid']
                ], 200);
            } else {
                return response()->json([
                    'error' => 'Error al enviar mensaje de WhatsApp',
                    'details' => $resultado['error']
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Error al enviar oferta a usuario específico', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Error interno del servidor',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene estadísticas de usuarios con WhatsApp
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function estadisticas()
    {
        try {
            $totalUsuarios = User::count();
            $usuariosConTelefono = User::whereNotNull('telefono')
                                    ->where('telefono', '!=', '')
                                    ->count();
            
            $usuariosValidosWhatsApp = 0;
            $usuarios = User::whereNotNull('telefono')
                           ->where('telefono', '!=', '')
                           ->get();

            foreach ($usuarios as $usuario) {
                if ($this->whatsappService->isValidPhoneNumber($usuario->telefono)) {
                    $usuariosValidosWhatsApp++;
                }
            }

            return response()->json([
                'estadisticas' => [
                    'total_usuarios' => $totalUsuarios,
                    'usuarios_con_telefono' => $usuariosConTelefono,
                    'usuarios_validos_whatsapp' => $usuariosValidosWhatsApp,
                    'porcentaje_cobertura' => $totalUsuarios > 0 ? round(($usuariosValidosWhatsApp / $totalUsuarios) * 100, 2) : 0
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener estadísticas',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Envía mensaje de prueba a un número específico
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function enviarMensajePrueba(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'telefono' => 'required|string',
                'mensaje' => 'required|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 400);
            }

            if (!$this->whatsappService->isValidPhoneNumber($request->telefono)) {
                return response()->json([
                    'error' => 'Número de teléfono inválido'
                ], 400);
            }

            $resultado = $this->whatsappService->sendMessage($request->telefono, $request->mensaje);

            if ($resultado['success']) {
                return response()->json([
                    'message' => 'Mensaje enviado exitosamente',
                    'telefono' => $request->telefono,
                    'message_sid' => $resultado['message_sid']
                ], 200);
            } else {
                return response()->json([
                    'error' => 'Error al enviar mensaje',
                    'details' => $resultado['error']
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error interno del servidor',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
