<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('feedbacks', function (Blueprint $table) {

            $table->id();

            // Relations
            $table->foreignId('formation_id')
                ->constrained()
                ->onDelete('cascade');

            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            // Feedback utilisateur
            $table->tinyInteger('note'); // 1 à 6 étoiles

            $table->text('commentaire')->nullable();

            // Réponse du formateur
            $table->text('reponse_formateur')->nullable();

            $table->timestamp('repondu_le')->nullable();

            $table->timestamps();

            // 1 feedback par user pour chaque formation
            $table->unique(['formation_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedbacks');
    }
};