<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_studis', function (Blueprint $table) {
            $table->id();
            $table->string('nama');                        // Nama program studi
            $table->string('kode')->unique();              // Kode prodi, mis: TI, TS, TIN
            $table->string('jenjang')->default('S1');      // S1 / D3 / S2
            $table->string('nama_kaprodi');                // Nama kepala prodi
            $table->string('nip_kaprodi')->nullable();     // NIP kaprodi
            $table->year('tahun_berdiri');                 // Tahun berdiri prodi
            $table->string('akreditasi')->default('B');    // A / B / Unggul / Baik Sekali / Baik
            $table->string('no_sk_akreditasi')->nullable();// Nomor SK akreditasi
            $table->string('icon')->nullable();            // Emoji / icon identitas
            $table->string('warna')->default('#7c3aed');   // Warna tema prodi (hex)
            $table->text('deskripsi')->nullable();         // Deskripsi singkat prodi
            $table->text('visi')->nullable();              // Visi prodi
            $table->text('misi')->nullable();              // Misi prodi
            $table->integer('jumlah_mahasiswa')->default(0);
            $table->integer('jumlah_dosen')->default(0);
            $table->integer('jumlah_alumni')->default(0);
            $table->integer('jumlah_lulusan_tahun_ini')->default(0);
            $table->decimal('rata_rata_ipk', 3, 2)->default(0.00);
            $table->decimal('rata_rata_masa_studi', 4, 2)->default(0.00); // dalam semester
            $table->string('email')->nullable();
            $table->string('telepon')->nullable();
            $table->string('website')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('program_studis');
    }
};
