<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Job extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'company',
        'location',
        'salary',
        'type',
        'image_path',
        'description',
        'is_verified',
        'link',
    ];

    public function likes() {
    return $this->hasMany(JobLike::class);
}
public function comments() {
    return $this->hasMany(JobComment::class);
}

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
