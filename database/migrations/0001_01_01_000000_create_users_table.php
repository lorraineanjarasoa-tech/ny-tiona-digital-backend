<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('email')->unique();

            $table->string('password');

            $table->string('role')->default('etudiant');

            $table->boolean('is_validated')->default(false);

            $table->boolean('is_active')->default(true);

            $table->timestamp('email_verified_at')->nullable();

            $table->timestamp('last_login_at')->nullable();

            $table->string('verification_token', 100)->nullable()->unique();

            $table->rememberToken();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};