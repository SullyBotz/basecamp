<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatGroup extends Model
{
    protected $guarded = [];

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'chat_group_members', 'chat_group_id', 'user_id')
                    ->withPivot('is_admin')
                    ->withTimestamps();
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'group_id');
    }
}
