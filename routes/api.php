<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
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
});

// Planes
Route::get('/plan', [planesController::class, 'index']);

// Ruta de prueba
Route::get('/test', function () {
    return response()->json(['status' => 'ok']);
});
