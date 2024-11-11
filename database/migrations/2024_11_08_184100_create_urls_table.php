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
            $table->string('hash')->unique();
            $table->ipAddress('ip_address');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('urls');
    }
}
