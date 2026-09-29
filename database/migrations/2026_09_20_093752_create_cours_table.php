<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cours', function (Blueprint $table) {
            $table->id();

            // Identifiants
            $table->uuid('formateur_id');
            $table->unsignedBigInteger('formation_id')->nullable();

            // Contenu textuel
            $table->string('titre', 150);
            $table->text('description_courte')->nullable();
            $table->text('description_longue')->nullable();

            // Médias
            $table->string('video_url', 500)->nullable();
            $table->string('document')->nullable();
            $table->string('image')->nullable();

            // Pédagogie
            $table->string('niveau', 20)->default('debutant');
            $table->integer('duree_heures')->default(0);
            $table->integer('ordre')->default(0);

            // Publication
            $table->string('statut', 20)->default('brouillon');
            $table->boolean('est_visible')->default(false);
            $table->timestamp('date_publication')->nullable();

            $table->timestamps();

            // Clés étrangères
            $table->foreign('formateur_id')
                ->references('id')->on('users')
                ->onDelete('cascade');

            $table->foreign('formation_id')
                ->references('id')->on('formations')
                ->onDelete('set null');

            // Index
            $table->index('formateur_id');
            $table->index('formation_id');
            $table->index('statut');
            $table->index('est_visible');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cours');
    }
};