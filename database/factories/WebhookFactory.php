<?php

namespace Database\Factories;

use App\Models\Webhook;
use App\Models\Url;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class WebhookFactory extends Factory
{
    protected $model = Webhook::class;

    public function definition()
    {
        return [
            'url_id' => Url::factory(),
            'hash' => Str::uuid(),
            'method' => $this->faker->randomElement(['GET', 'POST', 'PUT', 'DELETE']),
            'headers' => ['Content-Type' => 'application/json'],
            'query_params' => ['id' => $this->faker->randomNumber()],
            'body' => json_encode(['key' => 'value']),
            'host' => $this->faker->domainName(),
            'size' => $this->faker->numberBetween(100, 5000),
            'retransmitted' => false,
            'viewed' => false,
        ];
    }
}
