<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = ['conversation_id', 'sender_id', 'message', 'read_at'];

    // A qué conversación pertenece
    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    // Quién envió el mensaje
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
