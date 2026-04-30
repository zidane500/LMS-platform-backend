<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();

            // Formation liée au message
            $table->foreignId('formation_id')
                ->constrained()
                ->onDelete('cascade');

            // Expéditeur
            $table->foreignId('sender_id')
                ->constrained('users')
                ->onDelete('cascade');

            // Destinataire (conversation privée)
            $table->foreignId('receiver_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('cascade');

            // Contenu message
            $table->text('contenu');

            // Lu par formateur
            $table->boolean('lu_formateur')->default(false);

            // Message retiré
            $table->boolean('is_retracted')->default(false);

            // Type message : text|image|file|video|audio
            $table->string('type')->default('text');

            // Média
            $table->string('media_url')->nullable();
            $table->string('media_nom')->nullable();
            $table->string('media_mime')->nullable();

            // Réponse à un message
            $table->foreignId('reply_to_id')
                ->nullable()
                ->constrained('messages')
                ->nullOnDelete();

            // Expéditeur bloqué
            $table->boolean('is_blocked_sender')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};