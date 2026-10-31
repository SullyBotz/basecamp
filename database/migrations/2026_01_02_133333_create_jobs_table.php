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
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('title');        // Judul Pekerjaan (ex: Frontend Developer)
            $table->string('company');      // Nama Perusahaan (ex: PT. Tech Indo)
            $table->string('location');     // Lokasi (ex: Jakarta / Remote)
            $table->string('salary')->nullable(); // Gaji (Opsional)
            $table->string('type');         // Tipe (Full-time, Part-time, Freelance)
            $table->text('description')->nullable();
            $table->string('link')->nullable(); // Link pendaftaran eksternal
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};
