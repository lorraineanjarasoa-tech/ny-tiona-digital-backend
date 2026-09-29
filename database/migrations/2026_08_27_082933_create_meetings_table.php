<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();

            $table->uuid('created_by')->nullable();

            $table->string('titre');
            $table->text('description')->nullable();
            $table->text('lien_meeting')->nullable();
            $table->string('plateforme')->default('google_meet');
            $table->timestamp('date_heure');
            $table->integer('duree_minutes')->default(60);
            $table->integer('max_participants')->default(100);
            $table->enum('statut', ['planifie', 'en_cours', 'termine', 'annule'])->default('planifie');
            $table->text('motif_annulation')->nullable();
            $table->json('settings')->nullable();

            $table->foreign('created_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');

            $table->timestamps();

            $table->index('created_by');
            $table->index('date_heure');
            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};