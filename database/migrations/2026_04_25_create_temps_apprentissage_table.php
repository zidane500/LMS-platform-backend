<?php
// database/migrations/2026_04_25_create_temps_apprentissage_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('temps_apprentissage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('formation_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('duree_secondes')->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'formation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temps_apprentissage');
    }
};