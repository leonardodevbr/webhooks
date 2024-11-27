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
        Schema::create('webhook_retransmission_urls', function (Blueprint $table) {
            $table->id(); // ID da tabela
            $table->foreignId('url_id')->constrained('urls')->onDelete('cascade'); // Associação com a tabela de URLs
            $table->text('url'); // URL para retransmissão
            $table->boolean('is_online')->default(false); // Flag para indicar se é Online ou Local
            $table->timestamps(); // Campos created_at e updated_at
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
