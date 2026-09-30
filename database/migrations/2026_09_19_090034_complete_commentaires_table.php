<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commentaires', function (Blueprint $table) {
            if (!Schema::hasColumn('commentaires', 'partage_id')) {
                $table->unsignedBigInteger('partage_id')->nullable();
                $table->foreign('partage_id')
                    ->references('id')->on('partages')
                    ->onDelete('cascade');
            }

            if (!Schema::hasColumn('commentaires', 'user_id')) {
                $table->uuid('user_id')->nullable();
                $table->foreign('user_id')
                    ->references('id')->on('users')
                    ->onDelete('cascade');
            }

            if (!Schema::hasColumn('commentaires', 'contenu')) {
                $table->text('contenu')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('commentaires', function (Blueprint $table) {
            $table->dropForeign(['partage_id']);
            $table->dropForeign(['user_id']);
            $table->dropColumn(['partage_id', 'user_id', 'contenu']);
        });
    }
};