<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('examens', function (Blueprint $table) {
            $table->id();

            $table->string('titre');
            $table->text('description')->nullable();

            // bigint (formations.id = bigint)
            $table->foreignId('formation_id')
                ->nullable()
                ->constrained('formations')
                ->onDelete('cascade');

            // UUID (users.id = uuid)
            $table->foreignUuid('created_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');

            $table->integer('duree_minutes')->default(30);
            $table->integer('note_sur')->default(20);
            $table->timestamp('date_debut')->nullable();
            $table->timestamp('date_fin')->nullable();
            $table->enum('statut', ['brouillon', 'publie', 'ferme'])->default('brouillon');
            $table->integer('tentatives_max')->default(1);
            $table->timestamps();
            $table->index(['statut', 'formation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('examens');
    }
};