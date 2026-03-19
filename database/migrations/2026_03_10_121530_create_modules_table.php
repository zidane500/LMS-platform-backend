<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Cette migration AJOUTE les colonnes manquantes à la table modules
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->foreignId('formation_id')
                  ->constrained('formations')
                  ->onDelete('cascade');

            $table->string('titre', 255);
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('duree')->default(0);
            $table->unsignedSmallInteger('ordre')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->dropForeign(['formation_id']);
            $table->dropColumn(['formation_id', 'titre', 'description', 'duree', 'ordre']);
        });
    }
};
