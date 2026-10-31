<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_id',
        'receiver_id',
        'group_id',
        'body',
        'attachment',   
        'is_read',
    ];

    // Relasi ke User pengirim
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    // Relasi ke User penerima
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function group()
{
    return $this->belongsTo(ChatGroup::class, 'group_id');
}
}