<?php

namespace Database\Factories;

use App\Models\WebhookRetransmissionUrl;
use App\Models\Url;
use Illuminate\Database\Eloquent\Factories\Factory;

class WebhookRetransmissionUrlFactory extends Factory
{
    protected $model = WebhookRetransmissionUrl::class;

    public function definition()
    {
        return [
            'url_id' => Url::factory(),
            'url' => $this->faker->url(),
            'is_online' => $this->faker->boolean(),
        ];
    }
}
