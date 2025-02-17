<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->onDelete('cascade'); // Cada usuário pertence a uma conta
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_owner')->default(false); // Identifica se o usuário é dono da conta
            $table->rememberToken(); // Token padrão do Laravel para login
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
};
