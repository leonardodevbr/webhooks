<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebhooksTable extends Migration
{
    public function up()
    {
        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            $table->uuid('hash')->unique();
            $table->foreignId('url_id')->constrained('urls')->onDelete('cascade');

            // Controle de bloqueio
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->onDelete('set null'); // Registra qual plano estava ativo na criação
            $table->timestamp('blocked_at')->nullable(); // Indica se o webhook foi bloqueado por limite

            // Dados do webhook
            $table->timestamp('timestamp')->nullable();
            $table->string('method', 10);
            $table->json('headers')->nullable();
            $table->json('query_params')->nullable();
            $table->text('body')->nullable();
            $table->json('form_data')->nullable();
            $table->text('host');
            $table->integer('size')->nullable();
            $table->boolean('retransmitted')->default(false);
            $table->boolean('viewed')->default(false);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('webhooks');
    }
}
