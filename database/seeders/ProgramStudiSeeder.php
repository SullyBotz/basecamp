<?php

namespace Database\Seeders;

use App\Models\ProgramStudi;
use Illuminate\Database\Seeder;

class ProgramStudiSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [
                'nama'                    => 'Teknik Informatika',
                'kode'                    => 'TI',
                'jenjang'                 => 'S1',
                'nama_kaprodi'            => 'Dr. Ahmad Fauzi, M.Kom',
                'nip_kaprodi'             => '197805102005011003',
                'tahun_berdiri'           => 2001,
                'akreditasi'              => 'Baik Sekali',
                'no_sk_akreditasi'        => '0527/SK/BAN-PT/Akred/S/V/2023',
                'icon'                    => '💻',
                'warna'                   => '#6366f1',
                'deskripsi'               => 'Program Studi Teknik Informatika mempersiapkan lulusan yang kompeten di bidang rekayasa perangkat lunak, kecerdasan buatan, jaringan komputer, dan sistem informasi untuk menghadapi tantangan era digital.',
                'visi'                    => 'Menjadi program studi unggulan di tingkat nasional yang menghasilkan sarjana informatika berkarakter, inovatif, dan berdaya saing global pada tahun 2030.',
                'misi'                    => 'Menyelenggarakan pendidikan informatika yang berkualitas; Melaksanakan penelitian dan pengabdian masyarakat berbasis teknologi informasi; Menjalin kerjasama dengan industri dan institusi nasional maupun internasional.',
                'jumlah_mahasiswa'        => 487,
                'jumlah_dosen'            => 18,
                'jumlah_alumni'           => 1240,
                'jumlah_lulusan_tahun_ini'=> 89,
                'rata_rata_ipk'           => 3.42,
                'rata_rata_masa_studi'    => 8.6,
                'email'                   => 'prodi.ti@universitaspahlawan.ac.id',
                'telepon'                 => '(0762) 123456',
                'website'                 => 'https://ti.universitaspahlawan.ac.id',
                'is_active'               => true,
            ],
            [
                'nama'                    => 'Teknik Sipil',
                'kode'                    => 'TS',
                'jenjang'                 => 'S1',
                'nama_kaprodi'            => 'Dr. Ir. Hendra Kurniawan, MT',
                'nip_kaprodi'             => '196904251997021001',
                'tahun_berdiri'           => 1998,
                'akreditasi'              => 'Baik Sekali',
                'no_sk_akreditasi'        => '0412/SK/BAN-PT/Akred/S/IV/2023',
                'icon'                    => '🏗️',
                'warna'                   => '#f59e0b',
                'deskripsi'               => 'Program Studi Teknik Sipil mendidik tenaga ahli dalam perancangan, pembangunan, dan pengelolaan infrastruktur seperti jalan, jembatan, gedung, dan pengelolaan sumber daya air yang berwawasan lingkungan.',
                'visi'                    => 'Menjadi program studi teknik sipil yang unggul, mandiri, dan berkarakter dalam pengembangan ilmu pengetahuan dan teknologi infrastruktur berkelanjutan.',
                'misi'                    => 'Menyelenggarakan proses pembelajaran teknik sipil yang bermutu; Mengembangkan riset infrastruktur berbasis kebutuhan lokal Riau; Berkontribusi aktif dalam pembangunan infrastruktur wilayah Sumatera.',
                'jumlah_mahasiswa'        => 312,
                'jumlah_dosen'            => 14,
                'jumlah_alumni'           => 985,
                'jumlah_lulusan_tahun_ini'=> 67,
                'rata_rata_ipk'           => 3.38,
                'rata_rata_masa_studi'    => 9.1,
                'email'                   => 'prodi.ts@universitaspahlawan.ac.id',
                'telepon'                 => '(0762) 123457',
                'website'                 => 'https://ts.universitaspahlawan.ac.id',
                'is_active'               => true,
            ],
            [
                'nama'                    => 'Teknik Industri',
                'kode'                    => 'TIN',
                'jenjang'                 => 'S1',
                'nama_kaprodi'            => 'Dr. Rini Susanti, ST., MT',
                'nip_kaprodi'             => '198203152008012005',
                'tahun_berdiri'           => 2005,
                'akreditasi'              => 'Baik',
                'no_sk_akreditasi'        => '0318/SK/BAN-PT/Akred/S/III/2023',
                'icon'                    => '⚙️',
                'warna'                   => '#10b981',
                'deskripsi'               => 'Program Studi Teknik Industri mencetak sarjana yang mampu merancang, menganalisis, dan mengoptimalkan sistem terintegrasi dari manusia, mesin, material, informasi, dan energi untuk meningkatkan produktivitas industri.',
                'visi'                    => 'Menjadi program studi teknik industri yang menghasilkan sarjana kompeten, adaptif, dan beretika dalam pengembangan sistem industri yang efisien dan berkelanjutan.',
                'misi'                    => 'Melaksanakan pendidikan teknik industri berbasis kompetensi; Mendorong penelitian terapan di bidang optimasi sistem produksi dan manajemen industri; Membangun kemitraan dengan industri manufaktur dan jasa di Provinsi Riau.',
                'jumlah_mahasiswa'        => 276,
                'jumlah_dosen'            => 12,
                'jumlah_alumni'           => 620,
                'jumlah_lulusan_tahun_ini'=> 54,
                'rata_rata_ipk'           => 3.45,
                'rata_rata_masa_studi'    => 8.9,
                'email'                   => 'prodi.tin@universitaspahlawan.ac.id',
                'telepon'                 => '(0762) 123458',
                'website'                 => 'https://tin.universitaspahlawan.ac.id',
                'is_active'               => true,
            ],
        ];

        foreach ($data as $prodi) {
            ProgramStudi::updateOrCreate(['kode' => $prodi['kode']], $prodi);
        }
    }
}
