<?php

namespace Database\Factories;

use App\Models\Account;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => bcrypt('password'), // Senha padrão para testes
            'hash' => Str::uuid(),
            'slug' => Str::slug($this->faker->name),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
