<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PlanVigencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Mail\Bienvenida;
use Illuminate\Support\Facades\Mail;
use App\Services\WhatsAppService;

class RegistroController extends Controller
{
    // Método para registrar un nuevo usuario
    public function registrar(Request $request)
{
    try {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'telefono' => 'nullable|string|max:15',
            'direccion' => 'nullable|string|max:255',
            'plan_id' => 'required|exists:planes,id',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'telefono'=> $request->telefono,
            'direccion' => $request->direccion, 
            'plan_id' => $request->plan_id,
        ]);

        if ($user->plan_id == 2) {
            PlanVigencia::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'fecha_inicio' => Carbon::today(),
                    'fecha_fin' => Carbon::today()->addDays(30),
                ]
            );
        }

       // Enviar email de bienvenida
       Mail::to($user->email)->send(new Bienvenida($user));

       // Enviar mensaje de WhatsApp de bienvenida si el usuario tiene teléfono
       if ($user->telefono) {
           $whatsappService = new WhatsAppService();
           
           // Verificar que el número de teléfono sea válido
           if ($whatsappService->isValidPhoneNumber($user->telefono)) {
               $whatsappResult = $whatsappService->sendWelcomeMessage($user->telefono, $user->name);
               
               // Log del resultado (opcional)
               if ($whatsappResult['success']) {
                   \Log::info('Mensaje de bienvenida enviado por WhatsApp', [
                       'user_id' => $user->id,
                       'phone' => $user->telefono
                   ]);
               } else {
                   \Log::warning('Error al enviar mensaje de bienvenida por WhatsApp', [
                       'user_id' => $user->id,
                       'phone' => $user->telefono,
                       'error' => $whatsappResult['error'] ?? 'Error desconocido'
                   ]);
               }
           }
       }

        return response()->json([
            'message' => 'Usuario registrado exitosamente',
            'user' => $user
        ], 201);
    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Error interno del servidor',
            'message' => $e->getMessage(),
        ], 500);
    }
}

}

