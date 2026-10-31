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
        Schema::table('broadcast_comments', function (Blueprint $table) {
        $table->foreignId('parent_id')->nullable()->after('user_id')->constrained('broadcast_comments')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('broadcast_comments', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
        $table->dropColumn('parent_id');
        });
    }
};
