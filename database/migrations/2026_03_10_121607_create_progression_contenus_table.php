<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Ajoute les colonnes à progression_contenus pour tracker la lecture (US 3.2)
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('progression_contenus', function (Blueprint $table) {
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade');
            $table->foreignId('contenu_id')
                  ->constrained('contenus')
                  ->onDelete('cascade');
            $table->boolean('complete')->default(false);
            $table->unsignedTinyInteger('pourcentage')->default(0);
            $table->timestamp('derniere_consultation')->nullable();
            $table->unique(['user_id', 'contenu_id']);
        });
    }

    public function down(): void
    {
        Schema::table('progression_contenus', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'contenu_id']);
            $table->dropForeign(['user_id']);
            $table->dropForeign(['contenu_id']);
            $table->dropColumn(['user_id','contenu_id','complete','pourcentage','derniere_consultation']);
        });
    }
};
