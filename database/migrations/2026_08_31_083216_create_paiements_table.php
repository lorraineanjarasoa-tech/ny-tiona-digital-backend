<?php
// database/migrations/xxxx_create_paiements_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('etudiant_id')->constrained('users')->onDelete('cascade');
            $table->integer('mois');
            $table->integer('annee');
            $table->decimal('montant', 10, 2);
            $table->timestamp('date_paiement')->nullable();
            $table->enum('statut', ['en_attente', 'paye', 'annule'])->default('en_attente');
            $table->string('reference')->nullable()->unique();
            $table->timestamps();

            $table->index(['etudiant_id', 'mois', 'annee']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiements');
    }
};