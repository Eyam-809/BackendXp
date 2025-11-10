<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = ['user_one_id', 'user_two_id'];

    // Mensajes relacionados con esta conversación
    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    // Primer usuario
    public function userOne()
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    // Segundo usuario
    public function userTwo()
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }
}
