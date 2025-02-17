<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Account;
use App\Models\User;
use App\Models\Role;

class InitialDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Criar as roles iniciais
        $roles = [
            'super' => 'Acesso total ao sistema, sem restrições',
            'admin' => 'Gerencia a conta e os usuários vinculados',
            'developer' => 'Acesso a integrações e configurações técnicas',
            'viewer' => 'Somente leitura, sem permissões de edição'
        ];

        foreach ($roles as $roleName => $description) {
            Role::firstOrCreate([
                'name' => $roleName,
                'description' => $description
            ]);
        }

        // Criar a conta principal
        $account = Account::create([
            'name' => 'Administrador Principal',
            'cpf' => '963.957.425-23',
            'cnpj' => null,
            'phone' => '(11) 99999-9999',
            'birth_date' => '1985-06-15',
            'street' => 'Rua Exemplo',
            'number' => '123',
            'neighborhood' => 'Centro',
            'zipcode' => '01010-000',
            'city' => 'São Paulo',
            'state' => 'SP',
        ]);

        // Criar o usuário Super Admin vinculado à conta
        $superUser = User::create([
            'account_id' => $account->id,
            'name' => 'Super Admin',
            'email' => env('ADMIN_EMAIL', 'admin@admin.com'),
            'password' => Hash::make(env('ADMIN_PASSWORD', '1234qwer')),
            'is_owner' => true, // Dono da conta
        ]);

        // Atribuir a role "super" ao usuário
        $superUser->roles()->attach(Role::where('name', 'super')->first());

        $this->command->info('Administrador Super criado com sucesso!');
    }
}
