<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('likes', function (Blueprint $table) {
            if (!Schema::hasColumn('likes', 'partage_id')) {
                $table->unsignedBigInteger('partage_id')->nullable();
                $table->foreign('partage_id')
                    ->references('id')->on('partages')
                    ->onDelete('cascade');
            }

            if (!Schema::hasColumn('likes', 'user_id')) {
                $table->uuid('user_id')->nullable();
                $table->foreign('user_id')
                    ->references('id')->on('users')
                    ->onDelete('cascade');
            }

            $table->unique(['partage_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('likes', function (Blueprint $table) {
            $table->dropForeign(['partage_id']);
            $table->dropForeign(['user_id']);
            $table->dropUnique(['partage_id', 'user_id']);
            $table->dropColumn(['partage_id', 'user_id']);
        });
    }
};