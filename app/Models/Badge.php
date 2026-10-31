<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Badge extends Model
{
    use HasFactory;

    protected $fillable = ['badge_name', 'icon_path', 'description'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_badges');
    }
}
