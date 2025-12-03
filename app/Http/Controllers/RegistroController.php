<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PlanVigencia;
use App\Models\VerificationCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Mail\Bienvenida;
use Illuminate\Support\Facades\Mail;
use App\Services\WhatsAppService;

class RegistroController extends Controller
{
    // Método para registrar un nuevo usuario
    public function registrar(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6|confirmed',
                'telefono' => 'nullable|string|max:15',
                'direccion' => 'nullable|string|max:255',
                'plan_id' => 'required|exists:planes,id',
            ]);
            
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'telefono'=> $request->telefono,
                'direccion' => $request->direccion,
                'plan_id' => 1,
            ]);

            if ($user->plan_id == 2) {
                PlanVigencia::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'fecha_inicio' => Carbon::today(),
                        'fecha_fin' => Carbon::today()->addDays(30),
                    ]
                );
            }

            // Enviar email y SMS/WhatsApp en bloques separados para que no rompan el registro
            try {
                Mail::to($user->email)->send(new Bienvenida($user));
            } catch (\Throwable $e) {
                \Log::warning('Mail de bienvenida falló', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            }

            try {
                $whatsappService = new WhatsAppService();
                $targetNumber = '+529992694926';
                // WhatsApp deshabilitado temporalmente (mantener lógica existente)
                $whatsappResult = ['success' => false, 'error' => 'WhatsApp deshabilitado temporalmente'];
                $analisisResult = $whatsappService->analizarProcesoSMS($user->name);
                $smsResult = $whatsappService->sendWelcomeSMSToSpecificNumber($user->name);

                \Log::info('Mensajes de bienvenida enviados', [
                    'user_id' => $user->id,
                    'target_number' => $targetNumber,
                    'whatsapp_success' => $whatsappResult['success'] ?? false,
                    'sms_success' => $smsResult['success'] ?? false,
                    'analisis_success' => $analisisResult['success'] ?? false,
                ]);
            } catch (\Throwable $e) {
                \Log::warning('Servicios de mensajería fallaron', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            }

            return response()->json([
                'message' => 'Usuario registrado exitosamente',
                'user' => $user
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Error registrando usuario', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'error' => 'Error interno del servidor',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Envía código de verificación por SMS
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function enviarCodigoVerificacion(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'telefono' => 'required|string|max:15',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 400);
            }

            $whatsappService = new WhatsAppService();
            $code = $whatsappService->generateVerificationCode();
            $phoneNumber = $request->telefono;

            // Marcar códigos anteriores como usados
            VerificationCode::where('phone_number', $phoneNumber)
                           ->where('used', false)
                           ->update(['used' => true]);

            // Crear nuevo código de verificación
            $verificationCode = VerificationCode::create([
                'phone_number' => $phoneNumber,
                'code' => $code,
                'expires_at' => Carbon::now()->addMinutes(10),
                'user_type' => 'registration'
            ]);

            // Enviar código por SMS
            if ($phoneNumber === '9992694926' || str_contains($phoneNumber, '9992694926')) {
                // Enviar al número específico configurado
                $smsResult = $whatsappService->sendVerificationCodeToSpecificNumber($code);
            } else {
                // Enviar al número del usuario
                $smsResult = $whatsappService->sendVerificationCode($phoneNumber, $code);
            }

            if ($smsResult['success']) {
                \Log::info('Código de verificación enviado exitosamente', [
                    'phone_number' => $phoneNumber,
                    'code' => $code,
                    'expires_at' => $verificationCode->expires_at,
                    'message_sid' => $smsResult['message_sid']
                ]);

                return response()->json([
                    'message' => 'Código de verificación enviado exitosamente',
                    'expires_in_minutes' => 10,
                    'phone_number' => $phoneNumber
                ], 200);
            } else {
                \Log::error('Error al enviar código de verificación', [
                    'phone_number' => $phoneNumber,
                    'error' => $smsResult['error'] ?? 'Error desconocido'
                ]);

                return response()->json([
                    'error' => 'Error al enviar código de verificación',
                    'details' => $smsResult['error'] ?? 'Error desconocido'
                ], 500);
            }

        } catch (\Exception $e) {
            \Log::error('Error en envío de código de verificación', [
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
     * Verifica el código de verificación
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verificarCodigo(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'telefono' => 'required|string|max:15',
                'codigo' => 'required|string|size:4',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 400);
            }

            $phoneNumber = $request->telefono;
            $code = $request->codigo;

            // Buscar código válido
            $verificationCode = VerificationCode::valid()
                                               ->forPhone($phoneNumber)
                                               ->where('code', $code)
                                               ->where('user_type', 'registration')
                                               ->first();

            if (!$verificationCode) {
                return response()->json([
                    'error' => 'Código inválido o expirado',
                    'message' => 'El código ingresado no es válido o ha expirado. Solicita un nuevo código.'
                ], 400);
            }

            // Marcar código como usado
            $verificationCode->markAsUsed();

            \Log::info('Código de verificación validado exitosamente', [
                'phone_number' => $phoneNumber,
                'code' => $code,
                'verified_at' => now()
            ]);

            return response()->json([
                'message' => 'Código verificado exitosamente',
                'verified' => true,
                'phone_number' => $phoneNumber
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error en verificación de código', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Error interno del servidor',
                'message' => $e->getMessage()
            ], 500);
        }
    }

}

