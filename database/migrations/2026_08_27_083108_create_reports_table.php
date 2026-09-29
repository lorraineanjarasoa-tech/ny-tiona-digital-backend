<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();

            // Changer en uuid pour correspondre à users.id
            $table->uuid('reporter_id')->nullable();

            $table->string('subject');
            $table->text('reason');
            $table->enum('status', [
                'pending',
                'resolved',
                'rejected'
            ])->default('pending');

            $table->nullableMorphs('reportable');

            $table->timestamps();

            // Clé étrangère avec le bon type
            $table->foreign('reporter_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');

            // Index
            $table->index('status');
            $table->index('reporter_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};