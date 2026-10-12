<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('progressions', function (Blueprint $table) {
            $table->id();

            $table->uuid('user_id');
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->unsignedBigInteger('cours_id');
            $table->foreign('cours_id')
                ->references('id')
                ->on('cours')
                ->onDelete('cascade');

            // 0 = pas commencé, 100 = terminé
            $table->integer('pourcentage')->default(0);

            // Statut
            $table->enum('statut', ['non_commence', 'en_cours', 'termine'])
                ->default('non_commence');

            // Temps passé (optionnel)
            $table->integer('temps_passe_minutes')->default(0);

            // Dernière activité
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();

            $table->timestamps();

            // Contrainte : un seul enregistrement par (user, cours)
            $table->unique(['user_id', 'cours_id']);

            $table->index(['user_id', 'statut']);
            $table->index('cours_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progressions');
    }
};