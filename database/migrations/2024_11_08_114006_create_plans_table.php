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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Nome do plano (ex: Gratuito, Pro, Enterprise)
            $table->string('slug')->unique(); // Slug para referência no código (ex: free, pro, enterprise)
            $table->decimal('price', 10, 2)->default(0.00); // Preço do plano (se for 0, é gratuito)
            $table->string('billing_cycle')->default('monthly'); // Ciclo de pagamento (monthly, yearly, etc.)
            $table->boolean('active')->default(true);

            // Limites do plano
            $table->integer('max_urls')->default(1); // Quantidade máxima de URLs monitoradas
            $table->integer('max_webhooks_per_url')->default(1000); // Quantidade máxima de webhooks por URL
            $table->integer('max_retransmission_urls')->default(1); // Quantidade máxima de URLs de retransmissão

            $table->string('external_plan_id')->nullable()->unique();
            $table->boolean('supports_custom_slugs')->default(false); // Permite personalizar o slug?
            $table->boolean('real_time_notifications')->default(false); // Suporte a notificações em tempo real?

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
