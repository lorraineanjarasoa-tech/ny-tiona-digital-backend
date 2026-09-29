<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commentaires', function (Blueprint $table) {
            $table->unsignedBigInteger('partage_id')->after('id');
            $table->uuid('user_id')->after('partage_id');
            $table->text('contenu')->after('user_id');

            $table->foreign('partage_id')
                ->references('id')->on('partages')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('cascade');

            $table->index('partage_id');
        });
    }

    public function down(): void
    {
        Schema::table('commentaires', function (Blueprint $table) {
            $table->dropForeign(['partage_id']);
            $table->dropForeign(['user_id']);
            $table->dropIndex(['partage_id']);
            $table->dropColumn(['partage_id', 'user_id', 'contenu']);
        });
    }
};