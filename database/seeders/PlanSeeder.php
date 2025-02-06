<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Executa os seeders para criar os planos iniciais.
     */
    public function run()
    {
        // Plano Básico
        Plan::create([
            'name' => 'Básico',
            'slug' => 'basico',
            'price' => 9.99,
            'billing_cycle' => 'monthly',
            'max_urls' => 1,
            'max_webhooks_per_url' => 1000,
            'max_retransmission_urls' => 1,
            'supports_custom_slugs' => false,
            'real_time_notifications' => false,
            'external_plan_id' => 13129
        ]);

        // Plano Pro
        Plan::create([
            'name' => 'Pro',
            'slug' => 'pro',
            'price' => 39.99,
            'billing_cycle' => 'monthly',
            'max_urls' => 5,
            'max_webhooks_per_url' => 5000,
            'max_retransmission_urls' => 3,
            'supports_custom_slugs' => true,
            'real_time_notifications' => true,
            'external_plan_id' => 13130
        ]);

        // Plano Business
        Plan::create([
            'name' => 'Business',
            'slug' => 'business',
            'price' => 79.99,
            'billing_cycle' => 'monthly',
            'max_urls' => 10,
            'max_webhooks_per_url' => 10000,
            'max_retransmission_urls' => 5,
            'supports_custom_slugs' => true,
            'real_time_notifications' => true,
            'external_plan_id' => 13131
        ]);
    }
}
