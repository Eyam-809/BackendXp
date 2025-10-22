<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    private $client;
    private $accountSid;
    private $authToken;
    private $fromNumber;

    public function __construct()
    {
        $this->client = new Client();
        $this->accountSid = config('services.twilio.account_sid');
        $this->authToken = config('services.twilio.auth_token');
        // Usar el número de Twilio desde la configuración
        $this->fromNumber = config('services.twilio.whatsapp_from', '+17622453853');
    }

    /**
     * Envía un mensaje de WhatsApp usando Twilio API
     *
     * @param string $to Número de teléfono de destino (formato: +1234567890)
     * @param string $message Mensaje a enviar
     * @return array Respuesta de la API
     */
    public function sendMessage(string $to, string $message): array
    {
        try {
            // Formatear número de destino para WhatsApp
            $toFormatted = $this->formatPhoneNumber($to);
            
            $response = $this->client->post("https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages.json", [
                'auth' => [$this->accountSid, $this->authToken],
                'form_params' => [
                    'From' => "whatsapp:{$this->fromNumber}",
                    'To' => "whatsapp:{$toFormatted}",
                    'Body' => $message
                ]
            ]);

            $responseData = json_decode($response->getBody(), true);
            
            Log::info('Mensaje de WhatsApp enviado exitosamente', [
                'to' => $toFormatted,
                'message_sid' => $responseData['sid'] ?? null
            ]);

            return [
                'success' => true,
                'message_sid' => $responseData['sid'] ?? null,
                'data' => $responseData
            ];

        } catch (RequestException $e) {
            $errorResponse = json_decode($e->getResponse()->getBody(), true);
            
            Log::error('Error al enviar mensaje de WhatsApp', [
                'to' => $to,
                'error' => $errorResponse,
                'status_code' => $e->getResponse()->getStatusCode()
            ]);

            return [
                'success' => false,
                'error' => $errorResponse['message'] ?? 'Error desconocido',
                'status_code' => $e->getResponse()->getStatusCode()
            ];
        }
    }

    /**
     * Envía notificación de bienvenida
     *
     * @param string $phoneNumber
     * @param string $userName
     * @return array
     */
    public function sendWelcomeMessage(string $phoneNumber, string $userName): array
    {
        $message = "¡Bienvenido a XPmarket! ahora te mantendremos informado de nuestra ofertas exclusivas y productos nuevos.";
        
        return $this->sendMessage($phoneNumber, $message);
    }

    /**
     * Envía notificación de oferta especial
     *
     * @param string $phoneNumber
     * @param string $offerTitle
     * @param string $offerDescription
     * @param string $discount
     * @return array
     */
    public function sendOfferNotification(string $phoneNumber, string $offerTitle, string $offerDescription, string $discount = null): array
    {
        $message = "🔥 *¡Oferta Especial!* 🔥\n\n";
        $message .= "*{$offerTitle}*\n\n";
        $message .= "{$offerDescription}\n\n";
        
        if ($discount) {
            $message .= "💰 *Descuento: {$discount}*\n\n";
        }
        
        $message .= "¡No te la pierdas! 🛍️\n";
        $message .= "Visita nuestra plataforma para más detalles.";
        
        return $this->sendMessage($phoneNumber, $message);
    }

    /**
     * Formatea el número de teléfono para WhatsApp
     *
     * @param string $phoneNumber
     * @return string
     */
    private function formatPhoneNumber(string $phoneNumber): string
    {
        // Remover caracteres no numéricos excepto el signo +
        $phoneNumber = preg_replace('/[^\d+]/', '', $phoneNumber);
        
        // Casos especiales para números conocidos
        if (str_contains($phoneNumber, '9992694926')) {
            return '+529992694926';
        }
        
        // Si ya tiene código de país, devolverlo tal como está
        if (str_starts_with($phoneNumber, '+')) {
            return $phoneNumber;
        }
        
        // Detectar números mexicanos (10 dígitos que empiezan con 55, 56, 57, 59, 33, etc.)
        $digitsOnly = preg_replace('/[^\d]/', '', $phoneNumber);
        
        if (strlen($digitsOnly) == 10) {
            // Verificar si es un número mexicano por el área
            $areaCode = substr($digitsOnly, 0, 2);
            $mexicanAreaCodes = ['33', '55', '56', '57', '59', '81', '91', '95', '99', '44', '66', '77', '88'];
            
            if (in_array($areaCode, $mexicanAreaCodes)) {
                return '+52' . $digitsOnly;
            }
        }
        
        // Si no es mexicano o no podemos determinarlo, usar +1 como fallback
        return '+1' . $phoneNumber;
    }

    /**
     * Verifica si el número de teléfono es válido para WhatsApp
     *
     * @param string $phoneNumber
     * @return bool
     */
    public function isValidPhoneNumber(string $phoneNumber): bool
    {
        $phoneNumber = preg_replace('/[^\d+]/', '', $phoneNumber);
        
        // Verificar que tenga al menos 10 dígitos (sin código de país)
        $digitsOnly = preg_replace('/[^\d]/', '', $phoneNumber);
        
        return strlen($digitsOnly) >= 10;
    }

    /**
     * Envía un mensaje al número específico configurado
     *
     * @param string $message Mensaje a enviar
     * @return array Respuesta de la API
     */
    public function sendToSpecificNumber(string $message): array
    {
        $specificNumber = '+529992694926';
        return $this->sendMessage($specificNumber, $message);
    }

    /**
     * Envía notificación de bienvenida al número específico
     *
     * @param string $userName
     * @return array
     */
    public function sendWelcomeToSpecificNumber(string $userName): array
    {
        $message = "¡Bienvenido a XPmarket! ahora te mantendremos informado de nuestra ofertas exclusivas y productos nuevos.";
        
        return $this->sendToSpecificNumber($message);
    }

    /**
     * Envía notificación de oferta al número específico
     *
     * @param string $offerTitle
     * @param string $offerDescription
     * @param string $discount
     * @return array
     */
    public function sendOfferToSpecificNumber(string $offerTitle, string $offerDescription, string $discount = null): array
    {
        $message = "🔥 *¡Oferta Especial!* 🔥\n\n";
        $message .= "*{$offerTitle}*\n\n";
        $message .= "{$offerDescription}\n\n";
        
        if ($discount) {
            $message .= "💰 *Descuento: {$discount}*\n\n";
        }
        
        $message .= "¡No te la pierdas! 🛍️\n";
        $message .= "Visita nuestra plataforma para más detalles.";
        
        return $this->sendToSpecificNumber($message);
    }

    /**
     * Envía un mensaje SMS usando Twilio API
     *
     * @param string $to Número de teléfono de destino (formato: +1234567890)
     * @param string $message Mensaje a enviar
     * @return array Respuesta de la API
     */
    public function sendSMS(string $to, string $message): array
    {
        try {
            // Formatear número de destino para SMS
            $toFormatted = $this->formatPhoneNumber($to);
            
            $response = $this->client->post("https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages.json", [
                'auth' => [$this->accountSid, $this->authToken],
                'form_params' => [
                    'From' => $this->fromNumber, // Para SMS no necesita el prefijo "whatsapp:"
                    'To' => $toFormatted,
                    'Body' => $message
                ]
            ]);

            $responseData = json_decode($response->getBody(), true);
            
            Log::info('Mensaje SMS enviado exitosamente', [
                'to' => $toFormatted,
                'message_sid' => $responseData['sid'] ?? null
            ]);

            return [
                'success' => true,
                'message_sid' => $responseData['sid'] ?? null,
                'data' => $responseData
            ];

        } catch (RequestException $e) {
            $errorResponse = json_decode($e->getResponse()->getBody(), true);
            
            Log::error('Error al enviar mensaje SMS', [
                'to' => $to,
                'error' => $errorResponse,
                'status_code' => $e->getResponse()->getStatusCode()
            ]);

            return [
                'success' => false,
                'error' => $errorResponse['message'] ?? 'Error desconocido',
                'status_code' => $e->getResponse()->getStatusCode()
            ];
        }
    }

    /**
     * Envía notificación de bienvenida por SMS
     *
     * @param string $phoneNumber
     * @param string $userName
     * @return array
     */
    public function sendWelcomeSMS(string $phoneNumber, string $userName): array
    {
        $message = "¡Bienvenido a XPmarket! Ahora te mantendremos informado de nuestras ofertas exclusivas y productos nuevos.";
        
        return $this->sendSMS($phoneNumber, $message);
    }

    /**
     * Envía notificación de bienvenida por SMS al número específico
     *
     * @param string $userName
     * @return array
     */
    public function sendWelcomeSMSToSpecificNumber(string $userName): array
    {
        $message = "¡Bienvenido a XPmarket! Ahora te mantendremos informado de nuestras ofertas exclusivas y productos nuevos.";
        
        $specificNumber = '+529992694926';
        return $this->sendSMS($specificNumber, $message);
    }

    /**
     * Envía notificación de oferta por SMS
     *
     * @param string $phoneNumber
     * @param string $offerTitle
     * @param string $offerDescription
     * @param string $discount
     * @return array
     */
    public function sendOfferSMS(string $phoneNumber, string $offerTitle, string $offerDescription, string $discount = null): array
    {
        $message = "🔥 ¡Oferta Especial! 🔥\n\n";
        $message .= "{$offerTitle}\n\n";
        $message .= "{$offerDescription}\n\n";
        
        if ($discount) {
            $message .= "💰 Descuento: {$discount}\n\n";
        }
        
        $message .= "¡No te la pierdas! 🛍️\n";
        $message .= "Visita nuestra plataforma para más detalles.";
        
        return $this->sendSMS($phoneNumber, $message);
    }

    /**
     * Envía notificación de oferta por SMS al número específico
     *
     * @param string $offerTitle
     * @param string $offerDescription
     * @param string $discount
     * @return array
     */
    public function sendOfferSMSToSpecificNumber(string $offerTitle, string $offerDescription, string $discount = null): array
    {
        $message = "🔥 ¡Oferta Especial! 🔥\n\n";
        $message .= "{$offerTitle}\n\n";
        $message .= "{$offerDescription}\n\n";
        
        if ($discount) {
            $message .= "💰 Descuento: {$discount}\n\n";
        }
        
        $message .= "¡No te la pierdas! 🛍️\n";
        $message .= "Visita nuestra plataforma para más detalles.";
        
        $specificNumber = '+529992694926';
        return $this->sendSMS($specificNumber, $message);
    }

    /**
     * Genera un código de verificación de 4 dígitos
     *
     * @return string
     */
    public function generateVerificationCode(): string
    {
        return str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Envía código de verificación por SMS
     *
     * @param string $phoneNumber
     * @param string $code
     * @return array
     */
    public function sendVerificationCode(string $phoneNumber, string $code): array
    {
        $message = "Tu código de verificación para XPmarket es: {$code}\n\nEste código expira en 10 minutos. No lo compartas con nadie.";
        
        return $this->sendSMS($phoneNumber, $message);
    }

    /**
     * Envía código de verificación por SMS al número específico
     *
     * @param string $code
     * @return array
     */
    public function sendVerificationCodeToSpecificNumber(string $code): array
    {
        $message = "Tu código de verificación para XPmarket es: {$code}\n\nEste código expira en 10 minutos. No lo compartas con nadie.";
        
        $specificNumber = '+529992694926';
        return $this->sendSMS($specificNumber, $message);
    }

    /**
     * Realiza análisis del proceso de SMS para verificar funcionamiento
     *
     * @param string $userName
     * @return array
     */
    public function analizarProcesoSMS(string $userName): array
    {
        try {
            Log::info('Iniciando análisis del proceso de SMS', [
                'user_name' => $userName,
                'timestamp' => now()
            ]);

            // 1. Verificar configuración de Twilio
            $configStatus = $this->verificarConfiguracionTwilio();
            
            // 2. Probar envío de SMS de análisis
            $smsResult = $this->enviarSMSAnalisis($userName);
            
            // 3. Generar reporte de análisis
            $analisis = [
                'timestamp' => now()->toDateTimeString(),
                'user_name' => $userName,
                'configuracion' => $configStatus,
                'envio_sms' => $smsResult,
                'estado_general' => $configStatus['valida'] && $smsResult['success'] ? 'FUNCIONANDO' : 'CON_ERRORES'
            ];

            Log::info('Análisis del proceso de SMS completado', $analisis);

            return [
                'success' => true,
                'analisis' => $analisis,
                'message' => 'Análisis del proceso de SMS completado exitosamente'
            ];

        } catch (\Exception $e) {
            Log::error('Error en análisis del proceso de SMS', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Error durante el análisis del proceso de SMS'
            ];
        }
    }

    /**
     * Verifica la configuración de Twilio
     *
     * @return array
     */
    private function verificarConfiguracionTwilio(): array
    {
        $accountSid = $this->accountSid;
        $authToken = $this->authToken;
        $fromNumber = $this->fromNumber;

        $valida = !empty($accountSid) && !empty($authToken) && !empty($fromNumber);

        return [
            'valida' => $valida,
            'account_sid' => !empty($accountSid) ? 'Configurado' : 'No configurado',
            'auth_token' => !empty($authToken) ? 'Configurado' : 'No configurado',
            'from_number' => $fromNumber,
            'timestamp' => now()->toDateTimeString()
        ];
    }

    /**
     * Envía SMS de análisis al número específico
     *
     * @param string $userName
     * @return array
     */
    private function enviarSMSAnalisis(string $userName): array
    {
        $message = "🔍 ANÁLISIS DE SMS - XPmarket\n\n";
        $message .= "Usuario: {$userName}\n";
        $message .= "Fecha: " . now()->format('d/m/Y H:i:s') . "\n";
        $message .= "Estado: Sistema funcionando correctamente\n\n";
        $message .= "Este es un mensaje de análisis automático del sistema de SMS.";

        $specificNumber = '+529992694926';
        return $this->sendSMS($specificNumber, $message);
    }
}

