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
        Schema::create('certificats', function (Blueprint $table) {
        $table->id();
        $table->string('numero')->unique(); // CERT-xxxxx
        $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
        $table->foreignId('formation_id')->constrained('formations')->onDelete('cascade');
        $table->decimal('moyenne', 5, 2)->default(0); // score moyen des quiz
        $table->string('mention'); // Passable/Bien/Très Bien/Excellent
        $table->timestamp('emis_le')->nullable();
        $table->timestamps();
        $table->unique(['user_id', 'formation_id']); // un seul certificat par formation
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificats');
    }
};
