<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->string('cin_recto')->nullable();
            $table->string('cin_verso')->nullable();
            $table->string('preuve_paiement')->nullable();
            $table->string('reference_bancaire')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'cin_recto',
                'cin_verso',
                'preuve_paiement',
                'reference_bancaire',
            ]);
        });
    }
};