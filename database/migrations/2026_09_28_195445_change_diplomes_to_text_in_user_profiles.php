<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Utilise une requête SQL brute (compatible PostgreSQL)
        // pour convertir json -> text sans dépendre de doctrine/dbal
        DB::statement('ALTER TABLE user_profiles ALTER COLUMN diplomes TYPE text USING diplomes::text');
        DB::statement('ALTER TABLE user_profiles ALTER COLUMN certifications TYPE text USING certifications::text');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE user_profiles ALTER COLUMN diplomes TYPE json USING diplomes::json');
        DB::statement('ALTER TABLE user_profiles ALTER COLUMN certifications TYPE json USING certifications::json');
    }
};