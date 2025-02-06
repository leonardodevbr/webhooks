<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUrlsTable extends Migration
{
    public function up()
    {
        Schema::create('urls', function (Blueprint $table) {
            $table->id();
            $table->uuid('hash')->unique();
            $table->ipAddress();
            $table->foreignId('account_id')->nullable()->constrained('accounts')->onDelete('cascade');

            $table->string('slug')->nullable();
            $table->boolean('notifications_enabled')->default(true);

            // Controle de limites
            $table->unsignedInteger('request_count')->default(0);
            $table->timestamp('blocked_at')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('urls');
    }
}
