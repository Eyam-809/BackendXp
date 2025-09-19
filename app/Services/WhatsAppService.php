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
        $this->fromNumber = config('services.twilio.whatsapp_from');
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
        $message = "¡Hola {$userName}! 🎉\n\n¡Bienvenido a nuestra plataforma! Te mantendremos informado sobre las mejores ofertas y promociones.\n\n¡Gracias por registrarte!";
        
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
        // Remover caracteres no numéricos excepto +
        $phoneNumber = preg_replace('/[^\d+]/', '', $phoneNumber);
        
        // Si no tiene código de país, agregar +1 (Estados Unidos)
        if (!str_starts_with($phoneNumber, '+')) {
            $phoneNumber = '+1' . $phoneNumber;
        }
        
        return $phoneNumber;
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
}
