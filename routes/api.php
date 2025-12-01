<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PaymentCardController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RegistroController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UsuariosController;
use App\Http\Controllers\CarritoController;
use App\Http\Controllers\planesController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\SubcategoryController;
use App\Http\Controllers\OAuthController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\SMSController;
use App\Http\Controllers\PointsController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\DetalleCompraController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\DireccionController;
use App\Http\Controllers\StripeController;
use App\Http\Controllers\PayPalController;
use App\Http\Controllers\SupersetController;



/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Rutas públicas
Route::post('/registros', [RegistroController::class, 'registrar']);

// Rutas para verificación por SMS
Route::post('/verificacion/enviar-codigo', [RegistroController::class, 'enviarCodigoVerificacion']);
Route::post('/verificacion/verificar-codigo', [RegistroController::class, 'verificarCodigo']);

// Rutas para SMS
Route::post('/sms/bienvenida', [SMSController::class, 'enviarSMSBienvenida']);
Route::post('/sms/prueba', [SMSController::class, 'enviarSMSPrueba']);
Route::post('/sms/analisis', [SMSController::class, 'analizarSMS']);


Route::post('login', function (Request $request) {
    $credentials = $request->only('email', 'password');

    if (Auth::attempt($credentials)) {
        $user = Auth::user();
        $token = $user->createToken('MyApp')->plainTextToken;
        return response()->json(['token' => $token, 'user' => $user], 200);
    }

    return response()->json(['message' => 'Unauthorized'], 401);
});

// Rutas OAuth públicas
Route::get('login/google', [OAuthController::class, 'redirectToGoogle']);
Route::get('login/google/callback', [OAuthController::class, 'handleGoogleCallback']);
Route::get('login/facebook', [OAuthController::class, 'redirectToFacebook']);
Route::get('login/facebook/callback', [OAuthController::class, 'handleFacebookCallback']);
Route::get('/login/microsoft', [OAuthController::class, 'redirectToMicrosoft']);
Route::get('/login/microsoft/callback', [OAuthController::class, 'handleMicrosoftCallback']);
Route::get('login/github', [OAuthController::class, 'redirectToGitHub']);
Route::get('login/github/callback', [OAuthController::class, 'handleGitHubCallback']);

// Productos públicos
Route::get('/products', [ProductController::class, 'index']); // Todos los productos
Route::get('/products/trueques', [ProductController::class, 'getTrueques']); // Solo trueques
Route::get('/products/subcategory/{subcategoria_id}', [ProductController::class, 'getBySubcategoria']); // Por subcategoría
Route::get('/products/user/{id}', [ProductController::class, 'getUserProducts']); // Por usuario
Route::get('/products/active/{userId}/count', [ProductController::class, 'getActiveProducts']);

// Categorías y subcategorías
Route::apiResource('categorias', CategoriaController::class);
Route::get('/subcategories/{categoria_id}', [SubcategoryController::class, 'byCategory']);

// Rutas protegidas (requieren autenticación)
Route::middleware('auth:sanctum')->group(function () {
    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);

    // Perfil usuario
    Route::get('/usuario', [UsuariosController::class, 'show']);
    Route::put('/usuario', [UsuariosController::class, 'update']);
    Route::post('/usuario/foto', [UsuariosController::class, 'updateFoto']);
    Route::post('/user/change-password', [\App\Http\Controllers\UsuariosController::class, 'cambiarPassword']);

    // Conversaciones
    Route::get('/conversations', [ConversationController::class, 'index']);
    Route::post('/conversations', [ConversationController::class, 'store']);
    Route::get('/conversations/user/{id}', [ConversationController::class, 'getByUser']);
    Route::post('/conversations/{conversation}/read', [ConversationController::class, 'markAsRead']);
    // Mensajes
    Route::get('/conversations/{id}/messages', [MessageController::class, 'index']);
    Route::post('/conversations/{id}/messages', [MessageController::class, 'store']);

    // Productos del usuario autenticado
    Route::get('/user/products', [ProductController::class, 'getUserProducts']);

    // Crear, actualizar y eliminar productos
    //Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);
    //Route::get('/products/status/{status_id}', [ProductController::class, 'getByStatus']);

    // Carrito
    Route::post('/carrito/agregar', [CarritoController::class, 'agregarAlCarrito']);
    Route::get('/carrito', [CarritoController::class, 'verCarrito']);
    Route::delete('/carrito/eliminar/{id}', [CarritoController::class, 'eliminarDelCarrito']);

    // Compras - CON SISTEMA DE PUNTOS
    Route::post('/compras', [CompraController::class, 'store']);
    Route::get('/compras', [CompraController::class, 'index']);
    Route::get('/compras/{id}', [CompraController::class, 'show']);
    Route::put('/compras/{id}/actualizar-estado', [CompraController::class, 'actualizarEstado']);
    Route::put('/compras/{id}', [CompraController::class, 'update']);
    Route::delete('/compras/{id}', [CompraController::class, 'destroy']);
    Route::get('/compras/usuario/{userId}', [CompraController::class, 'getByUser']);
    Route::get('/compras/user/{userId}/count', [CompraController::class, 'countByUser']);

    // Pedidos
    Route::get('/pedidos', [PedidoController::class, 'index']);
    Route::get('/pedidos/{id}', [PedidoController::class, 'show']);
    Route::put('/pedidos/{id}/actualizar-estado', [PedidoController::class, 'updateEstado']);
    Route::delete('/pedidos/{id}', [PedidoController::class, 'destroy']);
    Route::get('/pedidos/usuario/{userId}', [PedidoController::class, 'getByUser']);

    // Detalle de compras
    Route::get('/detalle-compras', [DetalleCompraController::class, 'index']);
    Route::get('/detalle-compras/{id}', [DetalleCompraController::class, 'show']);
    Route::post('/detalle-compras', [DetalleCompraController::class, 'store']);
    Route::put('/detalle-compras/{id}', [DetalleCompraController::class, 'update']);
    Route::delete('/detalle-compras/{id}', [DetalleCompraController::class, 'destroy']);
});

// Planes
Route::get('/plan', [planesController::class, 'index']);

// Ruta de prueba
Route::get('/test', function () {
    return response()->json(['status' => 'ok']);
});

// Ruta de prueba para puntos (sin autenticación)
Route::get('/points/test', function () {
    return response()->json(['message' => 'Puntos API funcionando']);
});

Route::post('/products', [ProductController::class, 'store']);
Route::get('/products/user/{id}', [ProductController::class, 'getUserProducts']); 

// Rutas para notificaciones de WhatsApp
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/products/approved', [ProductController::class, 'getApprovedProducts']);
Route::get('/products/rejected', [ProductController::class, 'getRejectedProducts']);

    // Enviar oferta masiva a todos los usuarios
    Route::post('/notificaciones/oferta-masiva', [NotificacionController::class, 'enviarOfertaMasiva']);
    
    // Enviar oferta a usuario específico
    Route::post('/notificaciones/oferta-usuario/{userId}', [NotificacionController::class, 'enviarOfertaUsuario']);
    
    // Obtener estadísticas de WhatsApp
    Route::get('/notificaciones/estadisticas', [NotificacionController::class, 'estadisticas']);
    
    // Enviar mensaje de prueba
    Route::post('/notificaciones/prueba', [NotificacionController::class, 'enviarMensajePrueba']);
});

// Rutas para el sistema de puntos
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/points/{userId}', [PointsController::class, 'getUserPoints']);
    Route::get('/points/{userId}/history', [PointsController::class, 'getPointsHistory']);
    Route::post('/points/add', [PointsController::class, 'addPointsFromPurchase']);
    Route::post('/points/redeem', [PointsController::class, 'redeemReward']);
    Route::get('/points/{userId}/coupons', [PointsController::class, 'getUserCoupons']);

    Route::get('/direcciones', [DireccionController::class, 'index']);
    Route::post('/direcciones', [DireccionController::class, 'store']);
    Route::put('/direcciones/{id}', [DireccionController::class, 'update']);
    Route::delete('/direcciones/{id}', [DireccionController::class, 'destroy']);
   // Route::get('/direcciones/{id}', [DireccionController::class, 'show']);

    
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/products', [ProductController::class, 'store']);
});

Route::get('/products/{id}', [ProductController::class, 'show']);

//Prueba de estatus
Route::get('/products/status/{status_id}', [ProductController::class, 'getByStatus']);
Route::post('/compras/compraplan', [CompraController::class, 'storeSubscription']);

// Ruta para contar productos vendidos por usuario
Route::get('/products/user/{userId}/sold-count', [ProductController::class, 'countSoldByUser']);



Route::get('/products/status/1', [ProductController::class, 'getStatusOneProducts']);
Route::put('/products/{id}/status', [ProductController::class, 'updateStatus']);

Route::get('/direcciones/{userId}', [DireccionController::class, 'getbyUser']);

Route::post('/stripe/charge', [StripeController::class, 'charge']);
Route::get('/paypal/pay', [PayPalController::class, 'createPayment'])->name('paypal.pay');
Route::get('/paypal/success', [PayPalController::class, 'success'])->name('paypal.success');
Route::get('/paypal/cancel', [PayPalController::class, 'cancel'])->name('paypal.cancel');

Route::get('/superset/guest-token', [SupersetController::class, 'guestToken']);

