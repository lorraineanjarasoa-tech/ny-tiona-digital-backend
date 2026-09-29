<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('messages')) {
            return;
        }

        Schema::create('messages', function (Blueprint $table) {
            $table->id();

            $table->foreignUuid('expediteur_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->foreignUuid('destinataire_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->text('contenu');
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();

            $table->timestamps();

            $table->index(['destinataire_id', 'is_read']);
            $table->index(['expediteur_id', 'destinataire_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};