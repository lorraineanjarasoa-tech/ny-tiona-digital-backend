<?php
// database/migrations/xxxx_create_partages_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partages', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('auteur_id')->constrained('users')->onDelete('cascade');
            $table->string('titre');
            $table->text('contenu');
            $table->enum('statut', ['publie', 'brouillon', 'archive'])->default('publie');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partages');
    }
};