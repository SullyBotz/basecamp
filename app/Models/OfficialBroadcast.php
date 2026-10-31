<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OfficialBroadcast extends Model
{
    use HasFactory;

    protected $fillable = ['admin_id', 'title', 'content', 'category', 'image_path', 'is_pinned', 'attachment'];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
    public function likes() {
    return $this->hasMany(BroadcastLike::class);
}

public function comments() {
    return $this->hasMany(BroadcastComment::class);
}
}
