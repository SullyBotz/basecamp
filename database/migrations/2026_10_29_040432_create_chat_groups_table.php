<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel Master Grup Chat
        Schema::create('chat_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('creator_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

        // 2. Tabel Relasi Anggota Grup & Status Admin
        Schema::create('chat_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_group_id')->constrained('chat_groups')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->boolean('is_admin')->default(false); // true jika admin/pembuat
            $table->timestamps();
        });

        // 3. Modifikasi Tabel Messages agar mendukung pesan Grup
        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('group_id')->nullable()->after('receiver_id')->constrained('chat_groups')->onDelete('cascade');
            $table->unsignedBigInteger('receiver_id')->nullable()->change(); // Buat nullable karena jika pesan grup, receiver_id kosong
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropColumn('group_id');
            $table->unsignedBigInteger('receiver_id')->nullable(false)->change();
        });
        Schema::dropIfExists('chat_group_members');
        Schema::dropIfExists('chat_groups');
    }
};