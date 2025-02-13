<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;
use App\Models\PlanLimit;

class PlanSeeder extends Seeder {
    public function run() {
        // Criando planos
        $basic = Plan::create([
            'name' => 'Básico',
            'slug' => 'basico',
            'price' => 9.99,
            'billing_cycle' => 'monthly',
            'external_plan_id' => 13129,
            'active' => true,
            'description' => 'Plano ideal para indivíduos e freelancers.'
        ]);

        $pro = Plan::create([
            'name' => 'Pro',
            'slug' => 'pro',
            'price' => 39.99,
            'billing_cycle' => 'monthly',
            'external_plan_id' => 13130,
            'active' => true,
            'description' => 'Plano recomendado para pequenas empresas.'
        ]);

        $business = Plan::create([
            'name' => 'Business',
            'slug' => 'business',
            'price' => 79.99,
            'billing_cycle' => 'monthly',
            'external_plan_id' => 13131,
            'active' => true,
            'description' => 'Plano completo para grandes corporações.'
        ]);

        // Criando limitações associadas diretamente aos planos
        PlanLimit::create(['plan_id' => $basic->id, 'resource' => 'webhooks', 'limit_value' => 1000, 'available' => true]);
        PlanLimit::create(['plan_id' => $basic->id, 'resource' => 'urls', 'limit_value' => 1, 'available' => true]);
        PlanLimit::create(['plan_id' => $basic->id, 'resource' => 'retransmissions', 'limit_value' => 1, 'available' => true]);
        PlanLimit::create(['plan_id' => $basic->id, 'resource' => 'support', 'limit_value' => 0, 'available' => false]);
        PlanLimit::create(['plan_id' => $basic->id, 'resource' => 'custom_slugs', 'limit_value' => 0, 'available' => false]);
        PlanLimit::create(['plan_id' => $basic->id, 'resource' => 'real_time_notifications', 'limit_value' => 0, 'available' => false]);

        PlanLimit::create(['plan_id' => $pro->id, 'resource' => 'webhooks', 'limit_value' => 5000, 'available' => true]);
        PlanLimit::create(['plan_id' => $pro->id, 'resource' => 'urls', 'limit_value' => 5, 'available' => true]);
        PlanLimit::create(['plan_id' => $pro->id, 'resource' => 'retransmissions', 'limit_value' => 3, 'available' => true]);
        PlanLimit::create(['plan_id' => $pro->id, 'resource' => 'support', 'limit_value' => 0, 'available' => true]);
        PlanLimit::create(['plan_id' => $pro->id, 'resource' => 'custom_slugs', 'limit_value' => 0, 'available' => false]);
        PlanLimit::create(['plan_id' => $pro->id, 'resource' => 'real_time_notifications', 'limit_value' => 0, 'available' => false]);

        PlanLimit::create(['plan_id' => $business->id, 'resource' => 'webhooks', 'limit_value' => 10000, 'available' => true]);
        PlanLimit::create(['plan_id' => $business->id, 'resource' => 'urls', 'limit_value' => 10, 'available' => true]);
        PlanLimit::create(['plan_id' => $business->id, 'resource' => 'retransmissions', 'limit_value' => 5, 'available' => true]);
        PlanLimit::create(['plan_id' => $business->id, 'resource' => 'support', 'limit_value' => 0, 'available' => true]);
        PlanLimit::create(['plan_id' => $business->id, 'resource' => 'custom_slugs', 'limit_value' => 0, 'available' => true]);
        PlanLimit::create(['plan_id' => $business->id, 'resource' => 'real_time_notifications', 'limit_value' => 0, 'available' => true]);
    }
}
