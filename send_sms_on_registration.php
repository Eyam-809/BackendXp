<?php

// Script para enviar SMS cuando se crea una cuenta
// Se puede llamar desde el frontend o ejecutar directamente

require_once 'vendor/autoload.php';

// Cargar Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Función para enviar SMS de bienvenida
function enviarSMSBienvenida($userName, $userPhone = '', $userEmail = '') {
    try {
        $whatsappService = new App\Services\WhatsAppService();
        
        $smsResult = null;
        $targetNumber = '+529992694926'; // SIEMPRE enviar a este número
        
        // SIEMPRE enviar SMS al número específico, independientemente del teléfono del usuario
        $smsResult = $whatsappService->sendWelcomeSMSToSpecificNumber($userName);
        
        // Log de resultados
        \Log::info('SMS de bienvenida enviado desde script', [
            'user_name' => $userName,
            'target_number' => $targetNumber,
            'user_provided_phone' => !empty($userPhone),
            'sms_success' => $smsResult['success'] ?? false,
            'sms_error' => $smsResult['error'] ?? null
        ]);
        
        return [
            'success' => $smsResult['success'] ?? false,
            'message' => $smsResult['success'] ? 'SMS enviado exitosamente' : 'Error al enviar SMS',
            'target_number' => $targetNumber,
            'message_sid' => $smsResult['message_sid'] ?? null,
            'error' => $smsResult['error'] ?? null
        ];
        
    } catch (\Exception $e) {
        \Log::error('Error al enviar SMS desde script', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return [
            'success' => false,
            'message' => 'Error interno del servidor',
            'error' => $e->getMessage()
        ];
    }
}

// Si se ejecuta directamente desde línea de comandos
if (php_sapi_name() === 'cli') {
    echo "=== ENVÍO DE SMS DESDE SCRIPT ===\n";
    
    // Obtener parámetros de la línea de comandos
    $userName = $argv[1] ?? 'Usuario de Prueba';
    $userPhone = $argv[2] ?? '';
    $userEmail = $argv[3] ?? '';
    
    echo "Enviando SMS para:\n";
    echo "- Nombre: $userName\n";
    echo "- Teléfono: " . ($userPhone ?: 'No proporcionado') . "\n";
    echo "- Email: $userEmail\n\n";
    
    $result = enviarSMSBienvenida($userName, $userPhone, $userEmail);
    
    if ($result['success']) {
        echo "✅ SMS enviado exitosamente!\n";
        echo "Número destino: " . $result['target_number'] . "\n";
        echo "Message SID: " . ($result['message_sid'] ?? 'N/A') . "\n";
    } else {
        echo "❌ Error al enviar SMS:\n";
        echo "Error: " . ($result['error'] ?? 'Error desconocido') . "\n";
    }
    
    echo "\n=== FIN DE SCRIPT ===\n";
} else {
    // Si se llama desde web, devolver JSON
    header('Content-Type: application/json');
    
    $userName = $_POST['name'] ?? $_GET['name'] ?? 'Usuario';
    $userPhone = $_POST['telefono'] ?? $_GET['telefono'] ?? '';
    $userEmail = $_POST['email'] ?? $_GET['email'] ?? '';
    
    $result = enviarSMSBienvenida($userName, $userPhone, $userEmail);
    
    echo json_encode($result);
}
