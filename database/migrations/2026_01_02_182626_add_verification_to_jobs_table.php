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
        Schema::table('jobs', function (Blueprint $table) {
            if (!Schema::hasColumn('jobs', 'is_active')) {
                $table->boolean('is_active')->default(false)->after('id');
            }

            // Cek user_id (Jika belum ada baru ditambah)
            if (!Schema::hasColumn('jobs', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade')->after('is_active');
            }

            // C. Cek salary
            if (!Schema::hasColumn('jobs', 'salary')) {
                $table->string('salary')->nullable()->after('type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['is_active', 'user_id', 'salary']);
        });
    }
};
