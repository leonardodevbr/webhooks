<?php

namespace App\Http\Controllers;

use App\Interfaces\IPaymentService;
use App\Models\Account;
use App\Models\CustomerCard;
use App\Models\Plan;
use App\Models\Url;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AccountController extends Controller
{
    protected IPaymentService $paymentService;

    public function __construct(IPaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Tela de registro de conta.
     */
    public function showRegisterForm()
    {
        if (Auth::check()) {
            $account = Auth::user();
            $url = $account->urls()->first();
            return redirect()->route('account.webhook.view', [
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
                $account = Auth::user();

                // Garante que a conta tenha uma URL associada
                $url = $account->urls()->first();
                if (!$url) {
                    $url = Url::create([
                        'account_id' => $account->id,
                        'ip_address' => request()->ip(),
                        'hash' => Str::uuid(),
                    ]);
                }

                return response()->json([
                    'ok' => true,
                    'success' => 'Login realizado com sucesso!',
                    'user' => $account,
                    'redirect' => route('account.webhook.view', [
                        'url_hash' => $url->hash
                    ]),
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
                'ok' => true,
                'success' => 'Logout realizado com sucesso.',
                'redirect' => route('webhook.create'),
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

    public function profile()
    {
        if (Auth::check()) {
            $account = Auth::user();
            $plans = Plan::where('active', true)->get();
            $savedCards = $account->customer_cards()->get();
            $payments = [];
            $pendingPayment = null;

            $subscription = $account->subscriptions()->where('expires_at','>', now())->orWhere('expires_at', null)->orderByDesc('created_at')->first();

            if (!empty($subscription)) {
                $payments = $subscription->payments()->get();
            }

            if (!empty($subscription)) {
                $pendingPayment = $subscription->payments()
                    ->where('status', 'waiting')
                    ->where('payment_method', 'banking_billet')
                    ->orderByDesc('created_at')
                    ->first();

                // Se houver um pagamento pendente, busca detalhes da cobrança na API
                if ($pendingPayment) {
                    $paymentDetails = $this->paymentService->getPayment($pendingPayment->external_payment_id);

                    if (!empty($paymentDetails['data']['payment']['banking_billet'])) {
                        $billetDetails = $paymentDetails['data']['payment']['banking_billet'];

                        $pendingPayment->details = [
                            'barcode' => $billetDetails['barcode'] ?? null,
                            'pix' => $billetDetails['pix'] ?? null,
                            'billet_link' => $billetDetails['billet_link'] ?? null,
                            'pdf' => $billetDetails['pdf']['charge'] ?? null,
                            'expire_at' => Carbon::createFromFormat('Y-m-d', $billetDetails['expire_at'])->format('d/m/Y') ?? null,
                        ];
                    }
                }
            }

            return view('account.profile', compact('account', 'plans', 'savedCards', 'subscription', 'payments', 'pendingPayment'));
        }

        return view('account.login');
    }

    public function updateProfile(Request $request)
    {
        $account = Auth::user();
        $request->merge([
            'cpf' => preg_replace('/\D/', '', $request->input('cpf')),
            'zipcode' => preg_replace('/\D/', '', $request->input('zipcode')),
            'birth_date' => Carbon::createFromFormat('d/m/Y', $request->input('birth_date'))->format('Y-m-d'),
            'phone' => preg_replace('/\D/', '', $request->input('phone'))
        ]);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => "required|email|unique:accounts,email,{$account->id}",
            'cpf' => 'required|string|size:11|unique:accounts,cpf,' . $account->id,
            'phone' => 'required|string|max:20',
            'birth_date' => 'required|date',
            'street' => 'required|string|max:255',
            'number' => 'required|string|max:10',
            'neighborhood' => 'required|string|max:255',
            'zipcode' => 'required|string|size:8',
            'city' => 'required|string|max:255',
            'state' => 'required|string|size:2',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $validated = $validator->validated();
            // Atualiza os dados do usuário autenticado
            $account->update($validated);

            return redirect()->back()
                ->with('success', 'Perfil atualizado com sucesso.');
        } catch (\Exception $e) {
            Log::error('Erro ao atualizar perfil: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Erro ao atualizar perfil. Tente novamente.');
        }
    }


}
