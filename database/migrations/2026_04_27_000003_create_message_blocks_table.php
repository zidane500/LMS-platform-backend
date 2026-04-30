<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_blocks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('formation_id')
                ->constrained()
                ->onDelete('cascade');

            $table->foreignId('blocked_user_id')
                ->constrained('users')
                ->onDelete('cascade');

            $table->foreignId('blocked_by')
                ->constrained('users')
                ->onDelete('cascade');

            $table->timestamps();

            $table->unique(['formation_id', 'blocked_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_blocks');
    }
};