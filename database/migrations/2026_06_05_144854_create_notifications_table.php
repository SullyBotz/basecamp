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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            // user_id dibuat nullable() agar jika dikosongkan (null), notifikasi tersebut bersifat umum/bisa dilihat semua orang berdasarkan rolenyi
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('message');
            $table->string('type'); // Isi tipe: 'pesan', 'info_post', 'loker_tambah', 'loker_baru'
            $table->string('link')->nullable(); // URL tujuan ketika notifikasi di-klik
            $table->boolean('is_read')->default(false); // Status baca (false = belum dibaca)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};