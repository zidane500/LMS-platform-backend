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
         Schema::create('progression_formations', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
        $table->foreignId('formation_id')->constrained('formations')->onDelete('cascade');
        $table->integer('pourcentage_global')->default(0);
        $table->integer('modules_completes')->default(0);
        $table->integer('contenus_completes')->default(0);
        $table->boolean('complete')->default(false);
        $table->timestamp('termine_le')->nullable();
        $table->timestamps();
        $table->unique(['user_id', 'formation_id']);
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('progression_formations');
    }
};
