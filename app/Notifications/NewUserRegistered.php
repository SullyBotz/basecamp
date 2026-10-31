<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewUserRegistered extends Notification
{
    use Queueable;

    public $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    // Gunakan database untuk menyimpan notifikasi
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    // Data yang akan disimpan ke database dan ditampilkan di UI
    public function toDatabase(object $notifiable): array
    {
        return [
            'title'   => 'Pendaftar Baru!',
            'message' => "{$this->user->name} mendaftar sebagai " . strtoupper($this->user->role) . " dan menunggu verifikasi.",
            
            // 💡 PENTING: Ganti 'nama.route.akun' dengan nama route halaman Manajemen Akun Anda
            // Contoh jika url-nya /users, Anda bisa pakai url('/users')
            'url'     => url('/users'), 
        ];
    }
}