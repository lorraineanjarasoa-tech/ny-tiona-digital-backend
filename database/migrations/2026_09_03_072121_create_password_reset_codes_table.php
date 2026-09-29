<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('password_reset_codes', function (Blueprint $table) {

            $table->id();

            /*
             * IMPORTANT :
             * users.id est un UUID.
             */
            $table->uuid('user_id');

            /*
             * Email ou téléphone utilisé
             * pour demander la récupération.
             */
            $table->string('identifier');

            /*
             * email ou telephone
             */
            $table->string('method');

            /*
             * Code à 6 chiffres.
             */
            $table->string('code', 6);

            /*
             * Nombre de tentatives
             * de vérification.
             */
            $table->unsignedTinyInteger('attempts')
                ->default(0);

            /*
             * Date d'expiration du code.
             */
            $table->timestamp('expires_at');

            /*
             * Date d'utilisation du code.
             */
            $table->timestamp('used_at')
                ->nullable();

            $table->timestamps();

            /*
             * Relation avec users.
             */
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            /*
             * Index utiles.
             */
            $table->index([
                'identifier',
                'method'
            ]);

            $table->index([
                'user_id',
                'used_at'
            ]);

            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'password_reset_codes'
        );
    }
};