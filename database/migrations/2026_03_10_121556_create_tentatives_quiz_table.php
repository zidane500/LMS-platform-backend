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
         Schema::create('tentatives_quiz', function (Blueprint $table) {
        $table->id();
        $table->foreignId('quiz_id')->constrained('quiz')->onDelete('cascade');
        $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
        $table->decimal('score', 8, 2)->default(0);
        $table->integer('score_max')->default(0);
        $table->boolean('reussi')->default(false);
        $table->integer('duree_secondes')->nullable();
        $table->timestamp('termine_le')->nullable();
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tentatives_quiz');
    }
};
