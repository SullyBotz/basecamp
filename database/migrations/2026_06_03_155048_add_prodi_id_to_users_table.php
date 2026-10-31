<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Menambahkan kolom prodi_id setelah kolom role (nullable karena dekan tidak punya prodi)
            $table->unsignedBigInteger('prodi_id')->nullable()->after('role');

            // Opsional: Tambahkan foreign key agar relasi ke tabel program_studis lebih aman
            $table->foreign('prodi_id')->references('id')->on('program_studis')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Hapus foreign key dan kolom jika migration di-rollback
            $table->dropForeign(['prodi_id']);
            $table->dropColumn('prodi_id');
        });
    }
};
