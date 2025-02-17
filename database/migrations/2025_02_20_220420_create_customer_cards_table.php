<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('customer_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->nullable()->constrained('accounts')->onDelete('cascade');
            $table->string('payment_token')->unique();
            $table->string('card_brand');
            $table->string('card_mask');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('customer_cards');
    }
};
