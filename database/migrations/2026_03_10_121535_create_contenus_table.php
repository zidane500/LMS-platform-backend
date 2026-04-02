<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Ajoute les colonnes à la table contenus (qui n'a que id + timestamps)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contenus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')
                  ->constrained('modules')
                  ->onDelete('cascade');
            $table->string('titre', 255);
            $table->enum('type', ['video', 'pdf', 'audio', 'scorm']);
            $table->string('url', 1000)->nullable();          // URL externe
            $table->string('chemin_fichier', 500)->nullable(); // Fichier uploadé
            $table->unsignedSmallInteger('duree')->default(0); // minutes
            $table->text('resume')->nullable();
            $table->string('miniature', 500)->nullable();
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->timestamps();
        });
    }

   public function down(): void
{
    Schema::dropIfExists('contenus');
}
};
