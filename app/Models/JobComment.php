<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobComment extends Model
{
    protected $fillable = ['job_id', 'user_id', 'parent_id', 'content'];

public function user() { return $this->belongsTo(User::class); }
public function job() { return $this->belongsTo(Job::class); }
public function replies() { return $this->hasMany(JobComment::class, 'parent_id'); }
}
