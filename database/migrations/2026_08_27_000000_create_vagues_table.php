<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vagues', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('formation_id');

            $table->unsignedInteger('vague');

            $table->date('date_debut');

            $table->date('date_fin');

            $table->unsignedInteger('capacite')->default(30);

            $table->string('statut')->default('ouverte');

            $table->timestamps();

            $table->foreign('formation_id')
                ->references('id')
                ->on('formations')
                ->cascadeOnDelete();

            $table->index('formation_id');

            $table->index('date_debut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vagues');
    }
};