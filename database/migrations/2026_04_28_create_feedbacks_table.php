<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('formation_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->tinyInteger('note'); // 1 à 6 étoiles
            $table->text('commentaire')->nullable();
            $table->timestamps();
            $table->unique(['formation_id', 'user_id']); // 1 feedback par user/formation
        });
    }
    public function down(): void { Schema::dropIfExists('feedbacks'); }
};