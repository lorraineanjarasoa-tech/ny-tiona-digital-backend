<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();

            $table->uuid('user_id')->unique();

            $table->string('nom_complet');

            $table->string('adresse')->nullable();

            $table->string('contact', 20)->nullable();

            $table->date('date_naissance')->nullable();

            $table->string('sexe', 10)->nullable();

            $table->string('cin', 12)->nullable();

            $table->string('derniere_etude')->nullable();

            $table->string('preference_cours')->nullable();

            $table->text('specialite')->nullable();

            $table->json('diplomes')->nullable();

            $table->json('certifications')->nullable();

            $table->string('photo_profil')->nullable();

            $table->text('bio')->nullable();

            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};