<?php

// Script público para enviar SMS desde el frontend
// Accesible desde: http://localhost/backend/public/send_sms.php

require_once '../vendor/autoload.php';

// Cargar Laravel
$app = require_once '../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Configurar headers para CORS
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Obtener datos del request
    $input = json_decode(file_get_contents('php://input'), true);
    
    $userName = $input['name'] ?? $_POST['name'] ?? $_GET['name'] ?? 'Usuario';
    $userPhone = $input['telefono'] ?? $_POST['telefono'] ?? $_GET['telefono'] ?? '';
    $userEmail = $input['email'] ?? $_POST['email'] ?? $_GET['email'] ?? '';
    
    // Crear el servicio
    $whatsappService = new App\Services\WhatsAppService();
    
    $smsResult = null;
    $targetNumber = '+529992694926'; // SIEMPRE enviar a este número
    
    // SIEMPRE enviar SMS al número específico, independientemente del teléfono del usuario
    $smsResult = $whatsappService->sendWelcomeSMSToSpecificNumber($userName);
    
    // Log de resultados
    \Log::info('SMS de bienvenida enviado desde frontend', [
        'user_name' => $userName,
        'target_number' => $targetNumber,
        'user_provided_phone' => !empty($userPhone),
        'sms_success' => $smsResult['success'] ?? false,
        'sms_error' => $smsResult['error'] ?? null
    ]);
    
    if ($smsResult && $smsResult['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'SMS de bienvenida enviado exitosamente',
            'target_number' => $targetNumber,
            'message_sid' => $smsResult['message_sid'] ?? null
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al enviar SMS de bienvenida',
            'error' => $smsResult['error'] ?? 'Error desconocido'
        ]);
    }
    
} catch (\Exception $e) {
    \Log::error('Error al enviar SMS desde frontend', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor',
        'error' => $e->getMessage()
    ]);
}
