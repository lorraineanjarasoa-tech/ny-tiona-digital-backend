<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            // Ajouter les colonnes si elles n'existent pas
            if (!Schema::hasColumn('meetings', 'titre')) {
                $table->string('titre')->nullable()->after('id');
            }
            if (!Schema::hasColumn('meetings', 'date_heure')) {
                $table->datetime('date_heure')->nullable()->after('description');
            }
            if (!Schema::hasColumn('meetings', 'duree_minutes')) {
                $table->integer('duree_minutes')->default(60)->after('date_heure');
            }
            if (!Schema::hasColumn('meetings', 'lien_meeting')) {
                $table->string('lien_meeting')->nullable()->after('duree_minutes');
            }
            if (!Schema::hasColumn('meetings', 'plateforme')) {
                $table->string('plateforme')->default('google_meet')->after('lien_meeting');
            }
            if (!Schema::hasColumn('meetings', 'max_participants')) {
                $table->integer('max_participants')->default(100)->after('plateforme');
            }
            if (!Schema::hasColumn('meetings', 'statut')) {
                $table->string('statut')->default('planifie')->after('max_participants');
            }
            if (!Schema::hasColumn('meetings', 'settings')) {
                $table->json('settings')->nullable()->after('statut');
            }
        });
    }

    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn([
                'titre',
                'date_heure',
                'duree_minutes',
                'lien_meeting',
                'plateforme',
                'max_participants',
                'statut',
                'settings'
            ]);
        });
    }
};