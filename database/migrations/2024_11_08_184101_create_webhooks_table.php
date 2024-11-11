<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebhooksTable extends Migration
{
    public function up()
    {
        Schema::create('webhooks', function (Blueprint $table) {
            $table->uuid('id')->primary(); // Define o campo id como UUID
            $table->foreignId('url_id')->constrained('urls')->onDelete('cascade');
            $table->timestamp('timestamp')->nullable();
            $table->string('method', 10);
            $table->text('headers')->nullable();
            $table->text('query_params')->nullable();
            $table->text('body')->nullable();
            $table->text('form_data')->nullable();
            $table->string('host', 255);
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
