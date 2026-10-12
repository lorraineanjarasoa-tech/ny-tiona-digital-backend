<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('meetings', 'audience')) {
            Schema::table('meetings', function (Blueprint $table) {
                $table->enum('audience', ['public', 'formateurs', 'etudiants'])
                      ->default('public')
                      ->after('description');

                $table->index('audience');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('meetings', 'audience')) {
            Schema::table('meetings', function (Blueprint $table) {
                $table->dropIndex(['audience']);
                $table->dropColumn('audience');
            });
        }
    }
};