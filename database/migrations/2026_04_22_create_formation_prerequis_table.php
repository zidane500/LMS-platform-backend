<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Liaisons entre une formation codée et ses formations prérequises
        Schema::create('formation_prerequis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('formation_codee_id')
                  ->constrained('formations')->onDelete('cascade');
            $table->foreignId('prerequis_formation_id')
                  ->constrained('formations')->onDelete('cascade');
            $table->unique(['formation_codee_id', 'prerequis_formation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('formation_prerequis');
    }
};