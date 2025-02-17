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
        Schema::create('webhook_retransmission_urls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('url_id')->constrained('urls')->onDelete('cascade'); // URL cadastrada para retransmissão

            // Controle de limites do plano
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->onDelete('set null'); // Plano ativo na criação
            $table->timestamp('blocked_at')->nullable(); // Bloqueio por limite

            // Dados da URL de retransmissão
            $table->text('url'); // URL de destino
            $table->boolean('is_public')->default(false); // Indica se a URL é pública (externa) ou local (interna)

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_retransmission_urls');
    }
};
