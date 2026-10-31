<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Profile extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id', 'angkatan', 'bio', 'linkedin_url', 
        'current_job', 'company', 'thesis_title', 
        'pembimbing_1','pembimbing_2', 'points'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
