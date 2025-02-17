<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('webhook_retransmissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_request_id')->constrained('webhook_requests')->onDelete('cascade'); // Relaciona com o webhook recebido
            $table->foreignId('webhook_retransmission_url_id')->constrained('webhook_retransmission_urls')->onDelete('cascade'); // URL de retransmissão

            // Controle de tentativas
            $table->integer('attempts')->default(0); // Número de tentativas de retransmissão
            $table->timestamp('last_attempt_at')->nullable(); // Última tentativa
            $table->enum('status', ['pending', 'processing', 'success', 'failed'])->default('pending'); // Status da retransmissão
            $table->integer('response_status')->nullable(); // Código HTTP da resposta da URL de destino
            $table->text('response_body')->nullable(); // Resposta da URL após envio

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_retransmissions');
    }
};
