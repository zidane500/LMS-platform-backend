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
        Schema::create('demandes_formateur', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
        $table->foreignId('admin_id')->nullable()->constrained('users')->onDelete('set null');
        $table->string('specialite', 200);
        $table->tinyInteger('experience_annees')->unsigned();
        $table->text('motivation');
        $table->json('langues_enseignees')->nullable();
        $table->string('chemin_cv', 500);
        $table->string('chemin_attestation', 500);
        $table->enum('statut', ['en_attente', 'acceptee', 'refusee'])->default('en_attente');
        $table->timestamp('date_demande')->useCurrent();
        $table->timestamp('date_traitement')->nullable();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demandes_formateur');
    }
};
