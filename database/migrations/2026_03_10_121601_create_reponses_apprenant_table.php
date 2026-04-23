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
        Schema::create('reponses_apprenant', function (Blueprint $table) {
        $table->id();
        $table->foreignId('tentative_id')->constrained('tentatives_quiz')->onDelete('cascade');
        $table->foreignId('question_id')->constrained('questions')->onDelete('cascade');
        $table->foreignId('choix_id')->nullable()->constrained('choix_reponses')->onDelete('set null');
        $table->text('reponse_texte')->nullable();
        $table->boolean('est_correct')->default(false);
        $table->decimal('score_ia', 8, 2)->nullable()->after('est_correct');
        $table->text('feedback_ia')->nullable()->after('score_ia');
        $table->text('points_forts')->nullable()->after('feedback_ia');
        $table->text('points_amelioration')->nullable()->after('points_forts');
        $table->decimal('points_obtenus', 8, 2)->default(0)->after('points_amelioration');
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reponses_apprenant');
    }
};
