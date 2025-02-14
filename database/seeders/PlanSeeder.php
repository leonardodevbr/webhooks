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
            'external_plan_id' => null,
            'active' => true,
            'description' => 'Plano ideal para indivíduos e freelancers.'
        ]);

        $pro = Plan::create([
            'name' => 'Pro',
            'slug' => 'pro',
            'price' => 39.99,
            'billing_cycle' => 'monthly',
            'external_plan_id' => null,
            'active' => true,
            'description' => 'Plano recomendado para pequenas empresas.'
        ]);

        $business = Plan::create([
            'name' => 'Business',
            'slug' => 'business',
            'price' => 79.99,
            'billing_cycle' => 'monthly',
            'external_plan_id' => null,
            'active' => true,
            'description' => 'Plano completo para grandes corporações.'
        ]);

        // Criando limitações associadas diretamente aos planos
        PlanLimit::create(['plan_id' => $basic->id, 'resource' => 'webhooks', 'limit_value' => 1000, 'description' => 'Número máximo de webhooks', 'available' => true]);
        PlanLimit::create(['plan_id' => $basic->id, 'resource' => 'urls', 'limit_value' => 1, 'description' => 'Número máximo de URLs', 'available' => true]);
        PlanLimit::create(['plan_id' => $basic->id, 'resource' => 'retransmissions', 'limit_value' => 1, 'description' => 'Número máximo de retransmissões', 'available' => true]);
        PlanLimit::create(['plan_id' => $basic->id, 'resource' => 'support', 'description' => 'Suporte não disponível', 'available' => false]);
        PlanLimit::create(['plan_id' => $basic->id, 'resource' => 'custom_slugs', 'description' => 'Slugs personalizados não disponíveis', 'available' => false]);
        PlanLimit::create(['plan_id' => $basic->id, 'resource' => 'real_time_notifications', 'description' => 'Notificações em tempo real não disponíveis', 'available' => false]);

        PlanLimit::create(['plan_id' => $pro->id, 'resource' => 'webhooks', 'limit_value' => 5000, 'description' => 'Número máximo de webhooks', 'available' => true]);
        PlanLimit::create(['plan_id' => $pro->id, 'resource' => 'urls', 'limit_value' => 5, 'description' => 'Número máximo de URLs', 'available' => true]);
        PlanLimit::create(['plan_id' => $pro->id, 'resource' => 'retransmissions', 'limit_value' => 3, 'description' => 'Número máximo de retransmissões', 'available' => true]);
        PlanLimit::create(['plan_id' => $pro->id, 'resource' => 'support', 'description' => 'Suporte disponível', 'available' => true]);
        PlanLimit::create(['plan_id' => $pro->id, 'resource' => 'custom_slugs', 'description' => 'Slugs personalizados não disponíveis', 'available' => false]);
        PlanLimit::create(['plan_id' => $pro->id, 'resource' => 'real_time_notifications', 'description' => 'Notificações em tempo real não disponíveis', 'available' => false]);

        PlanLimit::create(['plan_id' => $business->id, 'resource' => 'webhooks', 'limit_value' => 10000, 'description' => 'Número máximo de webhooks', 'available' => true]);
        PlanLimit::create(['plan_id' => $business->id, 'resource' => 'urls', 'limit_value' => 10, 'description' => 'Número máximo de URLs', 'available' => true]);
        PlanLimit::create(['plan_id' => $business->id, 'resource' => 'retransmissions', 'limit_value' => 5, 'description' => 'Número máximo de retransmissões', 'available' => true]);
        PlanLimit::create(['plan_id' => $business->id, 'resource' => 'support', 'description' => 'Suporte disponível', 'available' => true]);
        PlanLimit::create(['plan_id' => $business->id, 'resource' => 'custom_slugs', 'description' => 'Slugs personalizados disponíveis', 'available' => true]);
        PlanLimit::create(['plan_id' => $business->id, 'resource' => 'real_time_notifications', 'description' => 'Notificações em tempo real disponíveis', 'available' => true]);
    }
}
