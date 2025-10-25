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
     * Envía notificación de oferta al número específico configurado
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

            // Enviar oferta al número específico
            $resultado = $this->whatsappService->sendOfferToSpecificNumber(
                $request->titulo,
                $request->descripcion,
                $request->descuento
            );

            if ($resultado['success']) {
                Log::info('Oferta enviada por WhatsApp al número específico', [
                    'offer' => $request->titulo,
                    'target_number' => '+529992694926',
                    'message_sid' => $resultado['message_sid']
                ]);

                return response()->json([
                    'message' => 'Oferta enviada exitosamente al número específico',
                    'estadisticas' => [
                        'mensajes_enviados' => 1,
                        'errores' => 0,
                        'numero_destino' => '+529992694926'
                    ],
                    'resultado' => [
                        'exitoso' => true,
                        'message_sid' => $resultado['message_sid']
                    ]
                ], 200);
            } else {
                Log::warning('Error al enviar oferta por WhatsApp', [
                    'offer' => $request->titulo,
                    'target_number' => '+529992694926',
                    'error' => $resultado['error'] ?? 'Error desconocido'
                ]);

                return response()->json([
                    'message' => 'Error al enviar oferta',
                    'estadisticas' => [
                        'mensajes_enviados' => 0,
                        'errores' => 1,
                        'numero_destino' => '+529992694926'
                    ],
                    'resultado' => [
                        'exitoso' => false,
                        'error' => $resultado['error'] ?? 'Error desconocido'
                    ]
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Error en envío de oferta', [
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
     * Envía notificación de oferta al número específico (mismo comportamiento que masiva)
     *
     * @param Request $request
     * @param int $userId
     * @return \Illuminate\Http\JsonResponse
     */
    public function enviarOfertaUsuario(Request $request, $userId)
    {
        // Redirigir al método masivo ya que siempre enviamos al mismo número
        return $this->enviarOfertaMasiva($request);
    }

    /**
     * Obtiene estadísticas del sistema de WhatsApp
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function estadisticas()
    {
        try {
            $totalUsuarios = User::count();

            return response()->json([
                'estadisticas' => [
                    'total_usuarios' => $totalUsuarios,
                    'numero_whatsapp_remitente' => '+14155238886',
                    'numero_whatsapp_destino' => '+529992694926',
                    'configuracion' => 'Envío dirigido a número específico',
                    'porcentaje_cobertura' => 100 // Siempre enviamos al número específico
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
     * Envía mensaje de prueba al número específico configurado
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function enviarMensajePrueba(Request $request)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'mensaje' => 'required|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 400);
            }

            $resultado = $this->whatsappService->sendToSpecificNumber($request->mensaje);

            if ($resultado['success']) {
                return response()->json([
                    'message' => 'Mensaje de prueba enviado exitosamente',
                    'telefono_destino' => '+529992694926',
                    'telefono_remitente' => '+14155238886',
                    'message_sid' => $resultado['message_sid']
                ], 200);
            } else {
                return response()->json([
                    'error' => 'Error al enviar mensaje de prueba',
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

