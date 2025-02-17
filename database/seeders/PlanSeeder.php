<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;
use App\Models\PlanResource;

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
        PlanResource::create(['plan_id' => $basic->id, 'name' => 'webhooks', 'value' => 1000, 'description' => 'Número máximo de webhooks', 'available' => true]);
        PlanResource::create(['plan_id' => $basic->id, 'name' => 'urls', 'value' => 1, 'description' => 'Número máximo de URLs', 'available' => true]);
        PlanResource::create(['plan_id' => $basic->id, 'name' => 'retransmissions', 'value' => 1, 'description' => 'Número máximo de retransmissões', 'available' => true]);
        PlanResource::create(['plan_id' => $basic->id, 'name' => 'support', 'description' => 'Suporte não disponível', 'available' => false]);
        PlanResource::create(['plan_id' => $basic->id, 'name' => 'custom_slugs', 'description' => 'Slugs personalizados não disponíveis', 'available' => false]);
        PlanResource::create(['plan_id' => $basic->id, 'name' => 'real_time_notifications', 'description' => 'Notificações em tempo real não disponíveis', 'available' => false]);

        PlanResource::create(['plan_id' => $pro->id, 'name' => 'webhooks', 'value' => 5000, 'description' => 'Número máximo de webhooks', 'available' => true]);
        PlanResource::create(['plan_id' => $pro->id, 'name' => 'urls', 'value' => 5, 'description' => 'Número máximo de URLs', 'available' => true]);
        PlanResource::create(['plan_id' => $pro->id, 'name' => 'retransmissions', 'value' => 3, 'description' => 'Número máximo de retransmissões', 'available' => true]);
        PlanResource::create(['plan_id' => $pro->id, 'name' => 'support', 'description' => 'Suporte disponível', 'available' => true]);
        PlanResource::create(['plan_id' => $pro->id, 'name' => 'custom_slugs', 'description' => 'Slugs personalizados não disponíveis', 'available' => false]);
        PlanResource::create(['plan_id' => $pro->id, 'name' => 'real_time_notifications', 'description' => 'Notificações em tempo real não disponíveis', 'available' => false]);

        PlanResource::create(['plan_id' => $business->id, 'name' => 'webhooks', 'value' => 10000, 'description' => 'Número máximo de webhooks', 'available' => true]);
        PlanResource::create(['plan_id' => $business->id, 'name' => 'urls', 'value' => 10, 'description' => 'Número máximo de URLs', 'available' => true]);
        PlanResource::create(['plan_id' => $business->id, 'name' => 'retransmissions', 'value' => 5, 'description' => 'Número máximo de retransmissões', 'available' => true]);
        PlanResource::create(['plan_id' => $business->id, 'name' => 'support', 'description' => 'Suporte disponível', 'available' => true]);
        PlanResource::create(['plan_id' => $business->id, 'name' => 'custom_slugs', 'description' => 'Slugs personalizados disponíveis', 'available' => true]);
        PlanResource::create(['plan_id' => $business->id, 'name' => 'real_time_notifications', 'description' => 'Notificações em tempo real disponíveis', 'available' => true]);
    }
}
