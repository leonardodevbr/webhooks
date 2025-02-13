<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        Account::create([
            'hash' => Str::uuid(),
            'name' => 'Administrador Principal',
            'slug' => 'admin',
            'email' => env('ADMIN_EMAIL', 'admin@admin.com'),
            'password' => Hash::make(env('ADMIN_PASSWORD', '1234qwer')),
            'is_admin' => true,

            // Dados adicionais do admin
            'cpf' => '963.957.425-23',
            'phone' => '(11) 99999-9999',
            'birth_date' => '1985-06-15',

            // Endereço do admin
            'street' => 'Rua Exemplo',
            'number' => '123',
            'neighborhood' => 'Centro',
            'zipcode' => '01010-000',
            'city' => 'São Paulo',
            'state' => 'SP'
        ]);
    }
}
