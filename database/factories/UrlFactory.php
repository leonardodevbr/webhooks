<?php

namespace Database\Factories;

use App\Models\Url;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UrlFactory extends Factory
{
    protected $model = Url::class;

    public function definition()
    {
        return [
            'account_id' => null, // ou um ID válido se necessário
            'hash' => Str::uuid(),
            'ip_address' => $this->faker->ipv4(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
