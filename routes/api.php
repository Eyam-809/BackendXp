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
use App\Http\Controllers\PointsController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Rutas públicas
Route::post('/registros', [RegistroController::class, 'registrar']);
Route::post('/login', function (Request $request) {
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
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);

     // Carrito
     Route::post('/carrito/agregar', [CarritoController::class, 'agregarAlCarrito']);
     Route::get('/carrito', [CarritoController::class, 'verCarrito']);
     Route::delete('/carrito/eliminar/{id}', [CarritoController::class, 'eliminarDelCarrito']);

     // Sistema de Puntos
     Route::get('/points/{userId}', [PointsController::class, 'getUserPoints']);
     Route::get('/points/{userId}/history', [PointsController::class, 'getPointsHistory']);
     Route::post('/points/add', [PointsController::class, 'addPointsFromPurchase']);
     Route::post('/points/redeem', [PointsController::class, 'redeemReward']);
     Route::get('/points/{userId}/coupons', [PointsController::class, 'getUserCoupons']);

     // Tarjetas de pago
     Route::get('/cards', [PaymentCardController::class, 'index']);
     Route::post('/cards', [PaymentCardController::class, 'store']);
     Route::delete('/cards/{id}', [PaymentCardController::class, 'destroy']);
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
});


/*git add .
git commit -m "Agrega login al frontend"
git push origin Xp-dev
*/




