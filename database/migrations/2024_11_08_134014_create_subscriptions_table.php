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

            $table->string('external_subscription_id')->nullable()->unique();
            $table->timestamp('started_at')->useCurrent(); // Início da assinatura
            $table->timestamp('expires_at')->nullable(); // Expiração (nulo se for plano vitalício)
            $table->boolean('is_active')->default(true); // Status da assinatura

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
