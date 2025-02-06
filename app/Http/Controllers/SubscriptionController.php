<?php

namespace App\Http\Controllers;

use App\Interfaces\IPaymentService;
use App\Models\CustomerCard;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    protected IPaymentService $paymentService;

    public function __construct(IPaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function subscribe(Request $request)
    {
        $plan = Plan::findOrFail($request->input('plan_id'));
        $account = auth()->user();

        $paymentMethod = $request->input('payment_method');
        $paymentToken = null;
        if($paymentMethod == 'credit_card'){
            $paymentToken = $request->input('saved_card_token') ?: $request->input('payment_token');

            if (!$paymentToken) {
                return redirect()->back()->with('error', 'Nenhum token de pagamento foi gerado.');
            }
        }

        $subscriptionData = [
            'payment_method' => $paymentMethod,
            'payment_token' => $paymentToken,
            'external_plan_id' => $plan->external_plan_id,
            'name' => $plan->name,
            'price' => $plan->price,
            'customer_name' => trim(preg_replace('/\s+/', ' ', $account->name)),
            'card_holder' => trim(preg_replace('/\s+/', ' ', $request->input('card_holder'))),
            'customer_email' => $account->email,
            'customer_cpf' => $account->cpf,
            'customer_phone' => $account->phone,
            'customer_birth' => $account->birth_date->format('Y-m-d'),
            'customer_address' => [
                'street' => $account->street,
                'number' => $account->number,
                'neighborhood' => $account->neighborhood,
                'zipcode' => $account->zipcode,
                'city' => $account->city,
                'state' => $account->state
            ]
        ];

        try {
            if ($request->has('save_card') && $request->input('save_card') == 'on') {
                CustomerCard::updateOrCreate(
                    ['account_id' => auth()->id(), 'payment_token' => $request->payment_token],
                    [
                        'card_brand' => $request->card_brand,
                        'card_mask' => $request->card_mask
                    ]
                );
            }

            $response = $this->paymentService->createSubscription($subscriptionData);
            Log::info("Subscription response", $response);

            if (!isset($response['data']['subscription_id'])) {
                return redirect()->back()->with('error', 'Erro ao processar a assinatura. Nenhum ID retornado.');
            }

            $subscription = Subscription::create([
                'plan_id' => $plan->id,
                'external_subscription_id' => $response['data']['subscription_id'],
                'started_at' => now(),
                'expires_at' => $response['data']['expire_at'] ?? null,
                'is_active' => $response['data']['status'] == 'active',
            ]);

            // Atualiza o usuário com a nova assinatura
            $account->subscription_id = $subscription->id;
            $account->save();

            if (isset($response['data']['charge'])) {
                $charge = $response['data']['charge'];

                Payment::create([
                    'account_id' => $account->id,
                    'plan_id' => $plan->id,
                    'gateway_reference' => $charge['id'],
                    'status' => $charge['status'],
                    'amount' => $charge['total'] / 100, // Convertendo centavos para reais
                    'payment_method' => $response['data']['payment'],
                    'gateway_response' => json_encode($response['data']),
                    'paid_at' => in_array($charge['status'], ['approved', 'paid']) ? now() : null
                ]);
            }

            return redirect()->route('account.profile')->with('success', 'Assinatura criada com sucesso!');
        } catch (\Exception $e) {
            Log::error('Erro ao criar assinatura: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Não foi possível criar a assinatura. Tente novamente mais tarde.');
        }
    }

    public function cancel(Request $request)
    {
        $account = auth()->user();
        $subscription = Subscription::where('id', $account->subscription_id)->first();

        if (!$subscription) {
            return redirect()->back()->with('error', 'Nenhuma assinatura encontrada.');
        }

        try {
            // Chamar serviço de pagamento para cancelar a assinatura
            $this->paymentService->cancelSubscription($subscription->external_subscription_id);

            // Atualizar status da assinatura no banco
            $subscription->update([
                'is_active' => false,
                'expires_at' => now(), // Opcional: definir data de expiração como agora
            ]);

            return redirect()->route('account.profile')->with('success', 'Assinatura cancelada com sucesso.');
        } catch (\Exception $e) {
            Log::error('Erro ao cancelar assinatura: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erro ao cancelar a assinatura.');
        }
    }
}
