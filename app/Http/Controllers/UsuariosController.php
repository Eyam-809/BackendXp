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
            'mensaje_plan' => $mensaje,
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
            'password' => Hash::make($request->password),
        ]);

        return response()->json($usuario, 201);
    }

    // Actualizar usuario (incluyendo foto base64)
    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'telefono' => 'nullable|string',
            'direccion' => 'nullable|string',
            'foto' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // <-- Se cambia a 'foto'
        ]);
    
        $usuario = auth()->user();
    
        $usuario->name = $request->name;
        $usuario->email = $request->email;
        $usuario->telefono = $request->telefono;
        $usuario->direccion = $request->direccion;
    
        if ($request->hasFile('foto')) {
            $image = $request->file('foto');
            $base64 = base64_encode(file_get_contents($image->getRealPath()));
            $mime = $image->getMimeType();
            $usuario->foto = "data:$mime;base64,$base64";
        }
    
        $usuario->save();
    
        return response()->json([
            'message' => 'Usuario actualizado correctamente',
            'foto' => $usuario->foto,
        ]);
    }

    // Eliminar usuario
    public function destroy($id)
    {
        $usuario = User::findOrFail($id);
        $usuario->delete();

        return response()->json(['message' => 'Usuario eliminado']);
    }

    // En UsuariosController.php
    public function updateFoto(Request $request)
    {
        $request->validate([
            'foto' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $usuario = auth()->user();

        if ($request->hasFile('foto')) {
            $image = $request->file('foto');
            $base64 = base64_encode(file_get_contents($image->getRealPath()));
            $mime = $image->getMimeType();
            $usuario->foto = "data:$mime;base64,$base64";
            $usuario->save();
        }

        return response()->json([
            'message' => 'Foto actualizada correctamente',
            'foto' => $usuario->foto,
        ]);
    }


}
