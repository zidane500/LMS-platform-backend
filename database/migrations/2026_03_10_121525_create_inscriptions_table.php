<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Cette migration AJOUTE les colonnes manquantes à la table inscriptions
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscriptions', function (Blueprint $table) {
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');

            $table->foreignId('formation_id')
                  ->constrained('formations')
                  ->onDelete('cascade');

            $table->timestamp('date_inscription')->useCurrent();

            // Un apprenant ne peut pas s'inscrire deux fois à la même formation
            $table->unique(['user_id', 'formation_id']);
        });
    }

    public function down(): void
    {
        Schema::table('inscriptions', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'formation_id']);
            $table->dropForeign(['user_id']);
            $table->dropForeign(['formation_id']);
            $table->dropColumn(['user_id', 'formation_id', 'date_inscription']);
        });
    }
};
