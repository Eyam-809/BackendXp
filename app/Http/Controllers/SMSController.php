<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Log;

class SMSController extends Controller
{
    /**
     * Envía SMS de bienvenida cuando se crea una cuenta
     */
    public function enviarSMSBienvenida(Request $request)
    {
        try {
            $whatsappService = new WhatsAppService();
            
            // Obtener datos del request
            $userName = $request->input('name', 'Usuario');
            $userPhone = $request->input('telefono', '');
            $userEmail = $request->input('email', '');
            
            Log::info('SMS de bienvenida solicitado', [
                'user_name' => $userName,
                'user_phone' => $userPhone,
                'user_email' => $userEmail
            ]);
            
            $smsResult = null;
            $targetNumber = '+529992694926'; // SIEMPRE enviar a este número
            
            // SIEMPRE enviar SMS al número específico, independientemente del teléfono del usuario
            $smsResult = $whatsappService->sendWelcomeSMSToSpecificNumber($userName);
            
            // Log de resultados
            Log::info('SMS de bienvenida enviado', [
                'user_name' => $userName,
                'target_number' => $targetNumber,
                'user_provided_phone' => !empty($userPhone),
                'sms_success' => $smsResult['success'] ?? false,
                'sms_error' => $smsResult['error'] ?? null
            ]);
            
            if ($smsResult && $smsResult['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'SMS de bienvenida enviado exitosamente',
                    'target_number' => $targetNumber,
                    'message_sid' => $smsResult['message_sid'] ?? null
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al enviar SMS de bienvenida',
                    'error' => $smsResult['error'] ?? 'Error desconocido'
                ], 500);
            }
            
        } catch (\Exception $e) {
            Log::error('Error al enviar SMS de bienvenida', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Envía SMS de prueba
     */
    public function enviarSMSPrueba(Request $request)
    {
        try {
            $whatsappService = new WhatsAppService();
            
            $result = $whatsappService->sendWelcomeSMSToSpecificNumber('Usuario de Prueba');
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'SMS de prueba enviado exitosamente',
                    'message_sid' => $result['message_sid'] ?? null
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al enviar SMS de prueba',
                    'error' => $result['error'] ?? 'Error desconocido'
                ], 500);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Realiza análisis del proceso de SMS
     */
    public function analizarSMS(Request $request)
    {
        try {
            $whatsappService = new WhatsAppService();
            
            // Obtener nombre del usuario del request o usar un valor por defecto
            $userName = $request->input('name', 'Usuario de Análisis');
            
            Log::info('Análisis de SMS solicitado manualmente', [
                'user_name' => $userName,
                'timestamp' => now()
            ]);
            
            $result = $whatsappService->analizarProcesoSMS($userName);
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Análisis del proceso de SMS completado exitosamente',
                    'analisis' => $result['analisis'] ?? null
                ], 200);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Error durante el análisis del proceso de SMS',
                    'error' => $result['error'] ?? 'Error desconocido'
                ], 500);
            }
            
        } catch (\Exception $e) {
            Log::error('Error en análisis manual de SMS', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
