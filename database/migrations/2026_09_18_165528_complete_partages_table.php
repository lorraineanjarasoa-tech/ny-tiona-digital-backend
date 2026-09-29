<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partages', function (Blueprint $table) {
            // Relation utilisateur
            $table->uuid('user_id')->after('id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Contenu
            $table->string('titre', 150)->nullable()->after('user_id');
            $table->text('contenu')->nullable()->after('titre');

            // Média
            $table->string('media_path')->nullable()->after('contenu');
            $table->string('media_type')->nullable()->after('media_path'); // image, video, document
            $table->string('media_mime')->nullable()->after('media_type');
            $table->bigInteger('media_size')->nullable()->after('media_mime');

            // Visibilité (public, etudiants, formateurs, ma_formation, prive)
            $table->string('visibilite')->default('public')->after('media_size');

            // Stats
            $table->integer('likes_count')->default(0)->after('visibilite');
            $table->integer('views_count')->default(0)->after('likes_count');

            // Index pour la performance
            $table->index('user_id');
            $table->index('visibilite');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('partages', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['visibilite']);
            $table->dropIndex(['created_at']);
            $table->dropColumn([
                'user_id',
                'titre',
                'contenu',
                'media_path',
                'media_type',
                'media_mime',
                'media_size',
                'visibilite',
                'likes_count',
                'views_count',
            ]);
        });
    }
};