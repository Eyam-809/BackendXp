<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\Request;
use App\Models\Message; // ...existing code...

class ConversationController extends Controller
{
    // Listar todas las conversaciones del usuario autenticado
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $conversations = Conversation::where('user_one_id', $userId)
            ->orWhere('user_two_id', $userId)
            ->with(['userOne', 'userTwo', 'messages'])
            ->get();

        return response()->json($conversations);
    }

    // Crear o obtener una conversación con otro usuario
    public function store(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id'
        ]);

        $userId = $request->user()->id;
        $receiverId = $request->receiver_id;

        // Para evitar duplicados, siempre ordenamos los IDs
        $conversation = Conversation::firstOrCreate([
            'user_one_id' => min($userId, $receiverId),
            'user_two_id' => max($userId, $receiverId)
        ]);

        // Crear mensaje inicial "¿Sigue disponible?"
        try {
            // Usa la relación messages() en Conversation para crear el mensaje
            $initialMessage = $conversation->messages()->create([
                'sender_id' => $userId,
                'message' => '¿Sigue disponible?'
            ]);
        } catch (\Throwable $e) {
            // Si falla la creación del mensaje, registrar y continuar
            \Log::error('No se pudo crear el mensaje inicial de la conversación', [
                'conversation_id' => $conversation->id,
                'error' => $e->getMessage()
            ]);
            $initialMessage = null;
        }

        return response()->json([
            'conversation' => $conversation,
            'initial_message' => $initialMessage
        ]);
    }

    // Obtener conversaciones de un usuario específico
    public function getByUser($id)
    {
        try {
            $conversations = Conversation::where('user_one_id', $id)
                ->orWhere('user_two_id', $id)
                ->with(['userOne', 'userTwo', 'messages' => function($q) {
                    $q->orderBy('created_at', 'asc');
                }])
                ->get();

            return response()->json($conversations);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function markAsRead(Request $request, Conversation $conversation)
    {
        $userId = $request->user()->id;

        // Solo los participantes pueden marcar como leídos
        if (!in_array($userId, [$conversation->user_one_id, $conversation->user_two_id])) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        // Marcar como leídos los mensajes del otro usuario
        $updated = $conversation->messages()
            ->where('sender_id', '<>', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        // Opcional: devolver los mensajes actualizados
        $messages = $conversation->messages()->orderBy('created_at','asc')->get();

        return response()->json([
            'updated' => $updated,
            'messages' => $messages
        ]);
    }
}
