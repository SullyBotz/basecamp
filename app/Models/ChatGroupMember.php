<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatGroupMember extends Model
{
    protected $guarded = [];

    // Relasi ke tabel chat_groups
    public function group()
    {
        return $this->belongsTo(ChatGroup::class, 'chat_group_id');
    }

    // Relasi ke tabel users
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}