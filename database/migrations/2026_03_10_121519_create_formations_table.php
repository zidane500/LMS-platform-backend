<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Cette migration AJOUTE les colonnes manquantes à la table formations
// qui existe déjà mais est vide (seulement id + timestamps)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('formations', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('formateur_id')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');

            $table->string('titre', 255);
            $table->text('description');
            $table->string('categorie', 100);
            $table->enum('niveau', ['debutant', 'intermediaire', 'avance'])->default('debutant');
            $table->unsignedSmallInteger('duree_estimee')->default(0);
            $table->json('prerequis')->nullable();
            $table->string('miniature', 500)->nullable();
            $table->enum('statut', ['brouillon', 'publie'])->default('brouillon');
        });
    }

     public function down(): void
{
    Schema::dropIfExists('formations');
}
};


/*

*/