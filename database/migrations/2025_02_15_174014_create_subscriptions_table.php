<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('plans')->onDelete('cascade'); // Plano ativo
            $table->foreignId('account_id')->constrained('accounts')->onDelete('cascade'); // Conta

            $table->string('external_subscription_id')->nullable()->unique();
            $table->timestamp('started_at')->useCurrent(); // Início da assinatura
            $table->timestamp('expires_at')->nullable(); // Expiração (nulo se for plano vitalício)
            $table->enum('status', [
                'new',         // Nova assinatura criada, aguardando primeira cobrança
                'active',      // Assinatura ativa, cobrança sendo realizada normalmente
                'suspended',   // Assinatura suspensa temporariamente
                'canceled',    // Assinatura cancelada pelo usuário ou pelo sistema
                'expired'      // Assinatura expirada (fim do prazo sem renovação)
            ])->default('new');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
