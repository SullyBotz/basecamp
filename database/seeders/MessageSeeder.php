<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Profile; // Pastikan Model Profile di-import
use App\Models\Message;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MessageSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat User Admin/Utama
        $me = User::firstOrCreate(
            ['email' => 'admin@basecamp.com'],
            [
                'name' => 'Emon Azriadi',
                'nim' => '123456789',
                'whatsapp' => '08123456789',
                'password' => Hash::make('password'),
                'role' => 'dekan',
                'is_verified' => '1'
            ]
        );

        // Buat Profil untuk Admin (opsional)
        Profile::updateOrCreate(['user_id' => $me->id], [
            'angkatan' => '2018',
            'thesis_title' => 'Analisis Keamanan Jaringan pada Sistem Cloud',
            'pembimbing_1' => 'Prof. Dr. Budiman',
            'pembimbing_2' => 'Prof Dr. Arif'
        ]);

        // 2. Data Alumni Dummy
        $alumniData = [
            [
                'name' => 'Budi Santoso',
                'nim' => '202000001',
                'whatsapp' => '08123987654',
                'thesis' => 'Implementasi Blockchain untuk Validasi Ijazah Digital',
                'job' => 'Backend Developer',
                'company' => 'Gojek'
            ],
            [
                'name' => 'Siti Aminah',
                'nim' => '202000002',
                'whatsapp' => '081239876542',
                'thesis' => 'Perancangan UI/UX Aplikasi E-Learning Berbasis Mobile',
                'job' => 'Product Designer',
                'company' => 'Shopee'
            ],
            [
                'name' => 'Rizky Pratama',
                'nim' => '202000003',
                'whatsapp' => '081239876541',
                'thesis' => 'Optimasi Algoritma K-Means untuk Pengelompokan Data Alumni',
                'job' => 'Data Analyst',
                'company' => 'Traveloka'
            ],
        ];

        foreach ($alumniData as $data) {
            // Simpan User
            $user = User::create([
                'name' => $data['name'],
                'nim' => $data['nim'],
                'whatsapp' => $data['whatsapp'],
                'email' => strtolower(str_replace(' ', '', $data['name'])) . '@example.com',
                'password' => Hash::make('password'),
                'role' => 'alumni',
                'is_verified' => '1',
            ]);

            // Simpan Profile (Menghubungkan ke User yang baru dibuat)
            Profile::create([
                'user_id' => $user->id,
                'angkatan' => '2020',
                'bio' => 'Halo! Saya alumni yang antusias dengan teknologi baru.',
                'current_job' => $data['job'],
                'company' => $data['company'],
                'thesis_title' => $data['thesis'],
                'pembimbing_1' => 'Prof. Dr. Budiman',
                'pembimbing_2' => 'Prof Dr. Arif'
            ]);

            // Buat pesan awal agar inbox tidak kosong
            Message::create([
                'sender_id' => $user->id,
                'receiver_id' => $me->id,
                'body' => "Halo kak, perkenalkan saya {$user->name}. Judul skripsi saya tentang " . $data['thesis'],
                'is_read' => false,
            ]);
        }
    }
}
