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
            $table->foreignId('account_id')->nullable()->constrained('accounts')->onDelete('cascade'); // Relacionado a uma conta
            $table->string('slug')->nullable()->unique(); // Slug para a URL
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('urls');
    }
}
