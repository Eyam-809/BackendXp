<?php

use App\Models\PlanVigencia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RegistroController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UsuariosController;
use App\Http\Controllers\CarritoController;
use App\Http\Controllers\planesController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Route::middleware('auth:sanctum')->get('/user', [UsuariosController::class, 'show']);


function verificarVigenciaPlan($user) {
    $vigencia = PlanVigencia::where('user_id', $user->id)->first();

    if ($vigencia) {
        if (Carbon::now()->gt($vigencia->fecha_fin)) {
            // Si venció, degradar
            if ($user->plan_id != 1) {
                $user->plan_id = 1;
                $user->save();
            }
        } else {
            // Si está vigente y está en plan básico, actualizarlo
            if ($user->plan_id != 2) {
                $user->plan_id = 2;
                $user->save();
            }
        }
    }
}


Route::post('/registros', [RegistroController::class, 'registrar']);


Route::post('login', function (Request $request) {
    $credentials = $request->only('email', 'password');

    if (Auth::attempt($credentials)) {
        // El usuario ha sido autenticado
        $user = Auth::user();
        verificarVigenciaPlan($user);
        $token = $user->createToken('MyApp')->plainTextToken;

        return response()->json(['token' => $token, 'user' => $user], 200);
    }

    return response()->json(['message' => 'Unauthorized'], 401);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::apiResource('products', ProductController::class);
Route::apiResource('/usuarios', UsuariosController::class);
Route::middleware('auth:sanctum')->get('/usuario', [UsuariosController::class, 'show']);
Route::middleware('auth:sanctum')->put('/usuario', [UsuariosController::class, 'update']);
// Ruta para TODOS los productos (sin autenticación)
Route::get('/products', [ProductController::class, 'index']);

// Ruta para productos del USUARIO AUTENTICADO (con middleware)
Route::middleware('auth:sanctum')->get('/user/products', [ProductController::class, 'getUserProducts']);

Route::post('/carrito/agregar', [CarritoController::class, 'agregarAlCarrito']);
Route::get('/carrito', [CarritoController::class, 'verCarrito']);
// routes/api.php
Route::delete('carrito/eliminar/{id}', [CarritoController::class, 'eliminarDelCarrito']);

Route::get('/plan', [planesController::class, 'index']);

Route::get('/test', function () {
    return response()->json(['status' => 'ok']);
});


/*git add .
git commit -m "Agrega login al frontend"
git push origin Xp-dev
*/




