<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nome completo da pessoa ou empresa
            $table->string('cpf')->nullable()->unique(); // CPF (Pessoa Física)
            $table->string('cnpj')->nullable()->unique(); // CNPJ (Pessoa Jurídica)
            $table->string('phone')->nullable(); // Telefone principal
            $table->date('birth_date')->nullable(); // Data de nascimento (se aplicável)

            // Endereço da conta
            $table->string('street')->nullable();
            $table->string('number')->nullable();
            $table->string('neighborhood')->nullable();
            $table->string('zipcode')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('accounts');
    }
};
