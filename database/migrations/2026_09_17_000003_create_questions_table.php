<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();

            // bigint (examens.id = bigint)
            $table->foreignId('examen_id')
                ->constrained('examens')
                ->onDelete('cascade');

            $table->text('question');
            $table->json('reponses');
            $table->integer('bonne_reponse')->default(0);
            $table->integer('points')->default(1);
            $table->text('explication')->nullable();
            $table->integer('ordre')->default(0);
            $table->timestamps();
            $table->index(['examen_id', 'ordre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};