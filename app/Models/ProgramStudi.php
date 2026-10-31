<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProgramStudi extends Model
{
    use HasFactory;

    protected $table = 'program_studis';

    protected $fillable = [
        'nama',
        'kode',
        'jenjang',
        'nama_kaprodi',
        'nip_kaprodi',
        'tahun_berdiri',
        'akreditasi',
        'no_sk_akreditasi',
        'icon',
        'warna',
        'deskripsi',
        'visi',
        'misi',
        'jumlah_mahasiswa',
        'jumlah_dosen',
        'jumlah_alumni',
        'jumlah_lulusan_tahun_ini',
        'rata_rata_ipk',
        'rata_rata_masa_studi',
        'email',
        'telepon',
        'website',
        'is_active',
        'role',
        'prodi_id'
    ];

    protected $casts = [
        'is_active'            => 'boolean',
        'rata_rata_ipk'        => 'float',
        'rata_rata_masa_studi' => 'float',
    ];

    /**
     * Hitung usia prodi (berapa tahun berdiri)
     */
    public function getUsiaAttribute(): int
    {
        return now()->year - $this->tahun_berdiri;
    }

    /**
     * Badge warna akreditasi
     */
    public function getAkreditasiBadgeColorAttribute(): string
    {
        return match ($this->akreditasi) {
            'Unggul'      => 'bg-emerald-100 text-emerald-700',
            'Baik Sekali' => 'bg-blue-100 text-blue-700',
            'Baik'        => 'bg-sky-100 text-sky-600',
            'A'           => 'bg-purple-100 text-purple-700',
            'B'           => 'bg-yellow-100 text-yellow-700',
            default       => 'bg-gray-100 text-gray-500',
        };
    }
    public function users()
    {
        return $this->hasMany(User::class, 'prodi_id');
    }
}
