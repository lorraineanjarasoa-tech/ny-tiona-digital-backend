<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();

            // UUID car users.id est uuid
            $table->foreignUuid('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // Type : message, ami, examen, paiement, inscription, alerte
            $table->string('type', 50)->default('default');

            $table->string('title');
            $table->text('message')->nullable();

            // URL vers laquelle rediriger au clic
            $table->string('url')->nullable();

            // Statut lu/non lu
            $table->boolean('read')->default(false);

            // Données supplémentaires (JSON)
            $table->json('data')->nullable();

            $table->timestamps();

            // Index pour requêtes rapides
            $table->index(['user_id', 'read']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};