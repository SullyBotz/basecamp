<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Badge;
use App\Models\OfficialBroadcast;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            JobSeeder::class,
        ]);

        $this->call([
            MessageSeeder::class,
        ]);
        
        $this->call([
            ProgramStudiSeeder::class,
        ]);

        // 1. Buat User Admin (Dekan)
        $admin = User::updateOrCreate(
            ['email' => 'admin@basecamp.com'],
            [
                'name' => 'Emon Azriadi',
                'nim' => '00000000',
                'whatsapp' => '0812345678',
                'password' => Hash::make('password'),
                'role' => 'dekan',
                'is_verified' => '1',
            ]
        );

        // 1.5. Buat Akun Kaprodi (Menggunakan Array & Foreach agar rapi dan tidak error)
        $kaprodiList = [
            [
                'email' => 'teknik@tif.com',
                'name' => 'Safni Marwa',
                'nim' => '00000001',
                'whatsapp' => '081297518064',
                'password' => Hash::make('password'),
                'role' => 'kaprodi',
                'prodi_id' => '1',
                'is_verified' => '1',
            ],
            [
                'email' => 'teknik@tii.com',
                'name' => 'Aris Priyatno',
                'nim' => '00000002',
                'whatsapp' => '085272187892',
                'password' => Hash::make('password'),
                'role' => 'kaprodi',
                'prodi_id' => '3',
                'is_verified' => '1',
            ],
            [
                'email' => 'teknik@tis.com',
                'name' => 'Beni Setiawan',
                'nim' => '00000003',
                'whatsapp' => '081374136465',
                'password' => Hash::make('password'),
                'role' => 'kaprodi',
                'prodi_id' => '2',
                'is_verified' => '1',
            ],
        ];

        foreach ($kaprodiList as $kaprodi) {
            User::updateOrCreate(
                ['email' => $kaprodi['email']], // Cari berdasarkan email
                [
                    'name' => $kaprodi['name'],
                    'nim' => $kaprodi['nim'],
                    'whatsapp' => $kaprodi['whatsapp'],
                    'password' => $kaprodi['password'],
                    'role' => $kaprodi['role'],
                    'prodi_id' => $kaprodi['prodi_id'],
                    'is_verified' => $kaprodi['is_verified'],
                ]
            );
        }

        // --- Lanjutan kodemu di bawah sini ---

        \App\Models\Post::create([
            'user_id' => $admin->id,
            'content' => 'Halo semuanya! Selamat datang di BaseCamp. Mari kita saling berbagi informasi bermanfaat di sini.',
        ]);

        \App\Models\Post::create([
            'user_id' => $admin->id,
            'content' => 'Jangan lupa untuk melengkapi profil kalian agar bisa mendapatkan badge menarik!',
        ]);

        // 2. Buat Data Badges
        $badgesData = [
            ['badge_name' => 'Top Mentor', 'icon_path' => 'badges/gold.png', 'description' => 'Mentor terbaik.'],
            ['badge_name' => 'Alumni Berkontribusi', 'icon_path' => 'badges/silver.png', 'description' => 'Aktif berbagi loker.'],
            ['badge_name' => 'Mahasiswa Aktif', 'icon_path' => 'badges/bronze.png', 'description' => 'Rajin di forum.'],
        ];

        $badges = collect();
        foreach ($badgesData as $data) {
            $badges->push(Badge::create($data));
        }

        // 3. Buat Official Broadcasts
        OfficialBroadcast::create([
            'admin_id' => $admin->id,
            'title' => 'Info Akademik Baru',
            'content' => 'Isi pengumuman resmi dari admin...',
            'category' => 'akademik',
            'is_pinned' => true
        ]);

        // 4. Hubungkan Badge ke User (Tanpa Model UserBadge)
        // Karena role Kaprodi bukan 'dekan', akun Kaprodi juga akan kebagian badge di proses ini
        $users = User::where('role', '!=', 'dekan')->get();

        foreach ($users as $user) {
            // Mengambil 1 atau 2 badge acak dari koleksi $badges
            $randomBadgeIds = $badges->random(rand(1, 2))->pluck('id');

            // Menggunakan attach() untuk mengisi tabel user_badges (pivot)
            $user->badges()->attach($randomBadgeIds);
        }
    }
}