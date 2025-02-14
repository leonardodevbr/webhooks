<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        // Tabela de planos
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Nome do plano (ex: Gratuito, Pro, Enterprise)
            $table->string('slug')->unique(); // Slug para referência no código (ex: free, pro, enterprise)
            $table->string('description')->nullable(); // Descrição do plano
            $table->decimal('price', 10, 2)->default(0.00); // Preço do plano
            $table->string('billing_cycle')->default('monthly'); // Ciclo de pagamento (monthly, yearly, etc.)
            $table->boolean('active')->default(true); // Se o plano está disponível
            $table->string('external_plan_id')->nullable()->unique(); // ID externo na API de pagamentos
            $table->timestamps();
        });

        // Tabela de recursos/limitações associadas diretamente aos planos (One-to-Many)
        Schema::create('plan_limits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('plans')->onDelete('cascade');
            $table->string('resource');
            $table->string('limit_value')->nullable(); // Pode ser nulo se o recurso não estiver disponível
            $table->text('description')->nullable();
            $table->boolean('available')->default(true); // Indica se o recurso está disponível no plano
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('plan_limits');
        Schema::dropIfExists('plans');
    }
};
