// Script JavaScript para enviar SMS desde el frontend
// Se puede incluir en cualquier página del frontend

async function enviarSMSBienvenida(userName, userPhone = '', userEmail = '') {
    try {
        const response = await fetch('http://localhost/backend/public/send_sms.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                name: userName,
                telefono: userPhone,
                email: userEmail
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            console.log('✅ SMS enviado exitosamente:', result);
            return result;
        } else {
            console.error('❌ Error al enviar SMS:', result);
            return result;
        }
        
    } catch (error) {
        console.error('❌ Error de conexión:', error);
        return {
            success: false,
            message: 'Error de conexión',
            error: error.message
        };
    }
}

// Función para ser llamada desde el botón de registro
function onRegistroCompletado(userName, userPhone = '', userEmail = '') {
    console.log('Enviando SMS de bienvenida...');
    
    enviarSMSBienvenida(userName, userPhone, userEmail)
        .then(result => {
            if (result.success) {
                console.log('🎉 SMS de bienvenida enviado exitosamente!');
                // Aquí puedes mostrar una notificación al usuario si quieres
            } else {
                console.error('Error al enviar SMS:', result.error);
            }
        })
        .catch(error => {
            console.error('Error inesperado:', error);
        });
}

// Ejemplo de uso:
// onRegistroCompletado('Juan Pérez', '9992694926', 'juan@example.com');
// onRegistroCompletado('María García', '', 'maria@example.com'); // Sin teléfono



