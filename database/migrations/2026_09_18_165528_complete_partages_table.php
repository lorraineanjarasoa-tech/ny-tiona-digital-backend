<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partages', function (Blueprint $table) {
            // Relation utilisateur (sans ->after() pour PostgreSQL)
            if (!Schema::hasColumn('partages', 'user_id')) {
                $table->uuid('user_id')->nullable();
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            }

            // Contenu
            if (!Schema::hasColumn('partages', 'titre')) {
                $table->string('titre', 150)->nullable();
            }
            if (!Schema::hasColumn('partages', 'contenu')) {
                $table->text('contenu')->nullable();
            }

            // Média
            if (!Schema::hasColumn('partages', 'media_path')) {
                $table->string('media_path')->nullable();
            }
            if (!Schema::hasColumn('partages', 'media_type')) {
                $table->string('media_type')->nullable();
            }
            if (!Schema::hasColumn('partages', 'media_mime')) {
                $table->string('media_mime')->nullable();
            }
            if (!Schema::hasColumn('partages', 'media_size')) {
                $table->bigInteger('media_size')->nullable();
            }

            // Visibilité
            if (!Schema::hasColumn('partages', 'visibilite')) {
                $table->string('visibilite')->default('public');
            }

            // Stats
            if (!Schema::hasColumn('partages', 'likes_count')) {
                $table->integer('likes_count')->default(0);
            }
            if (!Schema::hasColumn('partages', 'views_count')) {
                $table->integer('views_count')->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('partages', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
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