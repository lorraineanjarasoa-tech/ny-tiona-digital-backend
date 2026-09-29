<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partage_likes', function (Blueprint $table) {
            $table->id();

            // ✅ partages.id est un BIGINT
            $table->unsignedBigInteger('partage_id');

            // ✅ users.id est un UUID
            $table->uuid('user_id');

            $table->timestamps();

            $table->foreign('partage_id')
                ->references('id')->on('partages')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onDelete('cascade');

            $table->unique(['partage_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partage_likes');
    }
};