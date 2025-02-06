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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->onDelete('cascade'); // Usuário que fez o pagamento
            $table->foreignId('plan_id')->constrained('plans')->onDelete('cascade'); // Plano pago

            $table->string('gateway_reference')->nullable(); // ID do pagamento na Efi Pay
            $table->enum('status', ['waiting', 'pending', 'approved', 'paid', 'failed', 'expired', 'refunded', 'canceled'])->default('pending');
            $table->decimal('amount', 10, 2); // Valor pago
            $table->string('payment_method')->nullable(); // Método de pagamento (Pix, cartão, boleto)
            $table->json('gateway_response')->nullable(); // Resposta da API de pagamento para auditoria

            $table->timestamp('paid_at')->nullable(); // Quando o pagamento foi aprovado
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
