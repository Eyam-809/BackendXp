<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\PlanVigencia;
use Carbon\Carbon;

class UsuariosController extends Controller
{
    // Obtener todos los usuarios
    public function index()
    {
        return response()->json(User::all());
    }

    // Obtener información del usuario autenticado
    public function show(Request $request)
{
    $user = $request->user();
    $mensaje = null;

    if ($user->plan_id == 2) {
        $vigencia = PlanVigencia::where('user_id', $user->id)->first();

        if ($vigencia) {
            $hoy = Carbon::now();
            $fechaFin = Carbon::parse($vigencia->fecha_fin);
            $diasRestantes = $hoy->diffInDays($fechaFin, false);

            if ($diasRestantes <= 7 && $diasRestantes >= 0) {
                $mensaje = "⚠️ Tu plan vencerá en $diasRestantes días (el " . $fechaFin->toDateString() . ").";
            }
        }
    }

    return response()->json([
        'user' => $user,
        'mensaje_plan' => $mensaje,  // <-- Asegúrate que envías esto
    ]);
}




    // Crear un nuevo usuario
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
        ]);

        $usuario = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password), // Encriptar contraseña
        ]);

        return response()->json($usuario, 201);
    }

   

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'telefono' => 'nullable|string',
            'direccion' => 'nullable|string',
        ]);
    
        $usuario = auth()->user(); // Obtiene al usuario autenticado
    
        $usuario->name = $request->name;
        $usuario->email = $request->email;
        $usuario->telefono = $request->telefono;  // Actualiza el teléfono
        $usuario->direccion = $request->direccion;  // Actualiza la dirección
        $usuario->save();
    
        return response()->json(['message' => 'Usuario actualizado']);
    }
    
    


    // Eliminar usuario
    public function destroy($id)
    {
        $usuario = User::findOrFail($id);
        $usuario->delete();

        return response()->json(['message' => 'Usuario eliminado']);
    }
}
