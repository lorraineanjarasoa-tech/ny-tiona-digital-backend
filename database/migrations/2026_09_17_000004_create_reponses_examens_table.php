<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reponses_examens', function (Blueprint $table) {
            $table->id();

            // UUID (users.id = uuid)
            $table->foreignUuid('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // bigint (examens.id = bigint)
            $table->foreignId('examen_id')
                ->constrained('examens')
                ->onDelete('cascade');

            $table->json('reponses');
            $table->decimal('score', 5, 2)->default(0);
            $table->integer('bonnes_reponses')->default(0);
            $table->integer('mauvaises_reponses')->default(0);
            $table->integer('temps_ecoule')->default(0);
            $table->timestamps();
            $table->index(['user_id', 'examen_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reponses_examens');
    }
};