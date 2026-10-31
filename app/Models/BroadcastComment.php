<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BroadcastComment extends Model
{
    // 💡 Tambahkan 'parent_id' ke dalam fillable
    protected $fillable = ['official_broadcast_id', 'user_id', 'parent_id', 'content'];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function broadcast() {
        return $this->belongsTo(OfficialBroadcast::class, 'official_broadcast_id');
    }

    // 💡 Relasi untuk memanggil balasan dari komentar ini
    public function replies() {
        return $this->hasMany(BroadcastComment::class, 'parent_id');
    }
}
