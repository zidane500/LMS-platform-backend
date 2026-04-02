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
    Schema::create('badges', function (Blueprint $table) {
        $table->id();
        $table->string('code')->unique(); // ex: first_module, quiz_perfect
        $table->string('nom');
        $table->string('description');
        $table->string('icone')->default('🏆');
        $table->string('condition'); // ex: module_complete, quiz_reussi_premier_coup
        $table->timestamps();
    });
}
public function down(): void { Schema::dropIfExists('badges'); }
};
