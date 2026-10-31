<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Job;
use App\Models\Like;
use App\Models\Post;
use App\Models\Badge;
use App\Models\Comment;
use App\Models\Message;
use App\Models\Profile;
use App\Models\ProgramStudi; // 1. Import model ProgramStudi
use App\Models\OfficialBroadcast;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;


class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'nim',
        'password',
        'role',
        'avatar',
        'angkatan',
        'whatsapp',
        'is_verified',
        'prodi_id', // 2. Tambahkan prodi_id di sini agar bisa di-mass assignment
        'is_profile_completed', // <-- WAJIB ADA
        'angkatan',             // <-- WAJIB ADA
        'judul_skripsi',        // <-- WAJIB ADA
        'pembimbing_1', // 👈 Pastikan sudah ditambahkan
        'pembimbing_2', // 👈 Pastikan sudah ditambahkan
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // 3. Tambahkan fungsi relasi belongsTo ke ProgramStudi
    public function prodi()
    {
        return $this->belongsTo(ProgramStudi::class, 'prodi_id');
    }

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function badges()
    {
        return $this->belongsToMany(Badge::class, 'user_badges');
    }

    public function broadcasts()
    {
        return $this->hasMany(OfficialBroadcast::class);
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function likedPosts()
    {
        return $this->belongsToMany(Post::class, 'likes');
    }

    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    public function jobs()
    {
        return $this->hasMany(Job::class);
    }

    public function chatGroups()
{
    return $this->belongsToMany(ChatGroup::class, 'chat_group_members', 'user_id', 'chat_group_id')
                ->withPivot('is_admin')
                ->withTimestamps();
}
}
