<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('formation_acces_code', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('formation_id')->constrained()->onDelete('cascade');
            $table->timestamp('accessed_at')->useCurrent();
            $table->unique(['user_id', 'formation_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('formation_acces_code');
    }
};