<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inscriptions', function (Blueprint $table) {
            $table->id();

            // Étudiant : users.id est UUID
            $table->uuid('etudiant_id');

            // Formation : formations.id est BIGINT
            $table->foreignId('formation_id')
                ->constrained('formations')
                ->cascadeOnDelete();

            $table->string('preuve_paiement')->nullable();

            $table->string('reference_bancaire')->nullable();

            $table->string('statut')
                ->default('en_attente');

            $table->text('motif_rejet')->nullable();

            // Administrateur qui valide : users.id est UUID
            $table->uuid('valide_par')->nullable();

            $table->timestamp('date_validation')->nullable();

            $table->timestamp('date_inscription')->nullable();

            $table->json('documents_justificatifs')->nullable();

            $table->timestamps();

            // Clé étrangère étudiant
            $table->foreign('etudiant_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            // Clé étrangère validateur
            $table->foreign('valide_par')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            // Évite plusieurs inscriptions identiques
            $table->unique([
                'etudiant_id',
                'formation_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscriptions');
    }
};