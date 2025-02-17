<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('webhook_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('hash')->unique(); // Identificador único do webhook
            $table->foreignId('url_id')->constrained('urls')->onDelete('cascade'); // URL de origem do webhook

            // Controle de bloqueio e limites
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->onDelete('set null'); // Plano ativo na criação
            $table->timestamp('blocked_at')->nullable(); // Indica bloqueio por limite de plano

            // Dados do webhook recebido
            $table->timestamp('received_at')->nullable(); // Momento do recebimento
            $table->string('method', 10); // Método HTTP (GET, POST, etc.)
            $table->json('headers')->nullable(); // Headers HTTP da requisição
            $table->json('query_params')->nullable(); // Query string recebida
            $table->text('body')->nullable(); // Corpo da requisição
            $table->json('form_data')->nullable(); // Dados enviados via formulário
            $table->text('host'); // Origem da requisição
            $table->integer('size')->nullable(); // Tamanho do payload em bytes

            // Novo campo para armazenar a requisição completa
            $table->longText('raw_request')->nullable(); // Guarda a request original em formato bruto

            // Flags de controle
            $table->boolean('retransmitted')->default(false); // Indica se foi retransmitido
            $table->boolean('viewed')->default(false); // Indica se foi visualizado no sistema

            $table->timestamps(); // Campos created_at e updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists('webhook_requests');
    }
};
