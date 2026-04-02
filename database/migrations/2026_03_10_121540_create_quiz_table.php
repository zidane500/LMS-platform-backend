<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('quiz', function (Blueprint $table) {
        $table->id();
        $table->foreignId('module_id')->constrained('modules')->onDelete('cascade');
        $table->string('titre');
        $table->text('description')->nullable();
        $table->integer('seuil_reussite')->default(70);
        $table->integer('duree_minutes')->nullable();
        $table->integer('nb_tentatives_max')->default(3);
        $table->string('statut')->default('actif');
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz');
    }
};
