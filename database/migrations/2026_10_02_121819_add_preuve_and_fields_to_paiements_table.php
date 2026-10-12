<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paiements', function (Blueprint $table) {

            // 1. Preuve de paiement (image ou PDF)
            if (!Schema::hasColumn('paiements', 'preuve_paiement')) {
                $table->string('preuve_paiement')->nullable()->after('reference');
            }

            // 2. Formation liée
            if (!Schema::hasColumn('paiements', 'formation_id')) {
                $table->unsignedBigInteger('formation_id')->nullable()->after('etudiant_id');
                $table->foreign('formation_id')
                    ->references('id')
                    ->on('formations')
                    ->onDelete('set null');
            }

            // 3. Motif de rejet
            if (!Schema::hasColumn('paiements', 'motif_rejet')) {
                $table->text('motif_rejet')->nullable()->after('statut');
            }

            // 4. Admin qui a validé
            if (!Schema::hasColumn('paiements', 'valide_par')) {
                $table->uuid('valide_par')->nullable()->after('motif_rejet');
            }

            // 5. Date de validation
            if (!Schema::hasColumn('paiements', 'date_validation')) {
                $table->timestamp('date_validation')->nullable()->after('valide_par');
            }
        });
    }

    public function down(): void
    {
        Schema::table('paiements', function (Blueprint $table) {
            $columns = ['preuve_paiement', 'formation_id', 'motif_rejet', 'valide_par', 'date_validation'];

            foreach ($columns as $col) {
                if (Schema::hasColumn('paiements', $col)) {
                    try { $table->dropForeign([$col]); } catch (\Exception $e) {}
                    $table->dropColumn($col);
                }
            }
        });
    }
};