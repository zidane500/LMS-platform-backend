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
        Schema::create('choix_reponses', function (Blueprint $table) {
        $table->id();
        $table->foreignId('question_id')->constrained('questions')->onDelete('cascade');
        $table->text('texte');
        $table->boolean('est_correct')->default(false);
        $table->integer('ordre')->default(1);
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('choix_reponses');
    }
};
