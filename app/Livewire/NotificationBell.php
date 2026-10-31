<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

class NotificationBell extends Component
{
    public $notifications = [];
    public $unreadCount = 0;
    public $previousCount = 0; // 💡 Tambahkan ini untuk mengingat jumlah sebelumnya

    public function mount()
    {
        $this->updateNotifications();
        $this->previousCount = $this->unreadCount; // Set awal saat halaman dimuat
    }

    public function updateNotifications()
    {
        if (Auth::check()) {
            $this->notifications = Notification::where('user_id', Auth::id())
                ->orWhereNull('user_id')
                ->latest()
                ->take(5)
                ->get();

            // Hitung jumlah notif yang belum dibaca
            $newCount = Notification::where(function($query) {
                    $query->where('user_id', Auth::id())
                          ->orWhereNull('user_id');
                })
                ->where('is_read', false)
                ->count();

            // 💡 LOGIKA SUARA: Jika jumlah baru lebih besar dari sebelumnya, mainkan suara!
            if ($newCount > $this->previousCount) {
                $this->dispatch('play-notif-sound'); // Kirim sinyal ke frontend
            }

            // Perbarui data
            $this->unreadCount = $newCount;
            $this->previousCount = $newCount;
        }
    }

    public function render()
    {
        return view('livewire.notification-bell');
    }
}