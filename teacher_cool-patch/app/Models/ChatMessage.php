<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'message',
        'file',
        'sender_id',
        'receiver_id',
        'query_id',
        'is_seen'
    ];

    public function message_receiver_details()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}
