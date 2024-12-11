<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Url;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AccountController extends Controller
{
    /**
     * Tela de registro de conta.
     */
    public function showRegisterForm()
    {
        if (Auth::check()) {
            $account = Auth::user();
            $url = $account->urls()->first();
            return redirect()->route('account.webhook.view', [
                'account_slug' => $account->slug,
                'url_hash' => $url->hash,
            ]);
        }

        return view('account.register');
    }

    /**
     * Tela de login.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            $account = Auth::user();
            $url = $account->urls()->first();
            return redirect()->route('account.webhook.view', [
                'account_slug' => $account->slug,
                'url_hash' => $url->hash,
            ]);
        }

        return view('account.login');
    }

    /**
     * Realizar registro de conta.
     */
    public function register(Request $request)
    {
        // Valida manualmente para capturar e retornar os erros
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:accounts,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            // Retorna os erros como JSON
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $validated = $validator->validated();

            $slug = $this->generateUniqueSlug($validated['name'], Account::class);
            $account = Account::create([
                'hash' => Str::uuid(),
                'name' => $validated['name'],
                'slug' => $slug,
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            Auth::login($account);
            $ip = request()->ip();

            $url = Url::where('ip_address', $ip)->whereNull('account_id')->first();

            if ($url) {
                // Associa a URL existente à conta
                $url->update(['account_id' => $account->id]);
            } else {
                // Cria uma nova URL vinculada à conta
                $url = Url::create([
                    'account_id' => $account->id,
                    'ip_address' => $ip,
                    'hash' => Str::uuid(),
                ]);
            }

            return response()->json([
                'success' => 'Conta criada com sucesso!',
                'redirect' => route('account.webhook.view', [
                    'account_slug' => $account->slug,
                    'url_hash' => $url->hash
                ]),
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao registrar conta: '.$e->getMessage());
            return response()->json(['error' => 'Erro ao registrar a conta.'], 500);
        }
    }


    /**
     * Realizar login.
     */
    public function login(Request $request)
    {
        try {
            $validated = $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
            ]);

            if (Auth::attempt($validated)) {
                return response()->json([
                    'success' => 'Login realizado com sucesso!',
                    'redirect' => route('webhook.create-url'),
                ]);
            }

            return response()->json(['error' => 'Credenciais inválidas.'], 401);
        } catch (\Exception $e) {
            Log::error('Erro ao realizar login: '.$e->getMessage());
            return response()->json(['error' => 'Erro ao realizar login.'], 500);
        }
    }

    /**
     * Realizar logout.
     */
    public function logout()
    {
        try {
            Auth::logout();
            return response()->json([
                'success' => 'Logout realizado com sucesso.',
                'redirect' => route('webhook.create-url'),
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao realizar logout: '.$e->getMessage());
            return response()->json(['error' => 'Erro ao realizar logout.'], 500);
        }
    }

    public function status()
    {
        try {
            if (Auth::check()) {
                $user = Auth::user();

                // Retorna os dados do usuário autenticado
                return response()->json([
                    'success' => true,
                    'data' => $user->only(['id', 'name', 'email', 'slug']),
                ]);
            }

            // Usuário não autenticado
            return response()->json([
                'success' => false,
                'message' => 'Usuário não autenticado.',
            ], 401);
        } catch (\Exception $e) {
            // Tratamento de erro genérico
            Log::error('Erro ao verificar status de autenticação:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar a solicitação.',
            ], 500);
        }
    }


    public function generateUniqueSlug($name, $model)
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;

        $i = 1;
        while ($model::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$i}";
            $i++;
        }

        return $slug;
    }

}
