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
    Schema::table('user_profiles', function (Blueprint $table) {
        $table->text('diplomes')->nullable()->change();
        $table->text('certifications')->nullable()->change();
    });
}

public function down(): void
{
    Schema::table('user_profiles', function (Blueprint $table) {
        $table->json('diplomes')->nullable()->change();
        $table->json('certifications')->nullable()->change();
    });
}
};
