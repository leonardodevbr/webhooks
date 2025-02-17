<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('urls', function (Blueprint $table) {
            $table->id();
            $table->ipAddress();
            $table->foreignId('account_id')->nullable()->constrained('accounts')->onDelete('cascade');

            $table->string('slug')->unique();
            $table->boolean('notifications_enabled')->default(true);

            // Controle de limites
            $table->unsignedInteger('request_count')->default(0);
            $table->timestamp('blocked_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('urls');
    }
};
