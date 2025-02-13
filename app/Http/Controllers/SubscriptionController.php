<?php

namespace App\Http\Controllers;

use App\Interfaces\IPaymentService;
use App\Models\CustomerCard;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        try {
            DB::beginTransaction();
            $plan = Plan::findOrFail($request->input('plan_id'));
            $account = auth()->user();

            $paymentMethod = $request->input('payment_method');
            $paymentToken = null;

            if ($paymentMethod == 'credit_card') {
                $paymentToken = $request->input('payment_token');

                if (!$paymentToken) {
                    return response()->json(['error' => 'Nenhum token de pagamento foi fornecido.'], 400);
                }

                if ($request->has('save_card') && $request->input('save_card')) {
                    CustomerCard::updateOrCreate(
                        ['account_id' => auth()->id(), 'payment_token' => $paymentToken],
                        [
                            'card_brand' => $request->input('card_brand'),
                            'card_mask' => $request->input('card_mask')
                        ]
                    );
                }
            }

            $subscription = Subscription::create([
                'plan_id' => $plan->id,
                'account_id' => $account->id,
                'started_at' => now()
            ]);

            $subscriptionData = [
                'custom_id' => $subscription->id,
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

            $subscriptionResponse = $this->paymentService->createSubscription($subscriptionData);
            Log::info("Subscription response", $subscriptionResponse);

            if (!isset($subscriptionResponse['data']['subscription_id'])) {
                return response()->json(['error' => 'Erro ao processar a assinatura. Nenhum ID retornado.'], 400);
            }

            $subscription->update([
                'external_subscription_id' => $subscriptionResponse['data']['subscription_id'],
                'expires_at' => $subscriptionResponse['data']['expire_at'] ?? null,
                'is_active' => false
            ]);

            $responseData = [
                'message' => 'Assinatura criada com sucesso!',
                'subscription_id' => $subscriptionResponse['data']['subscription_id'],
                'expires_at' => $subscriptionResponse['data']['expire_at'] ?? null,
                'payment' => null,
            ];

            if (isset($subscriptionResponse['data']['charge'])) {
                $charge = $subscriptionResponse['data']['charge'];

                Payment::create([
                    'account_id' => $account->id,
                    'subscription_id' => $subscription->id,
                    'external_payment_id' => $charge['id'],
                    'status' => $charge['status'],
                    'amount' => $charge['total'] / 100,
                    'payment_method' => $subscriptionResponse['data']['payment'],
                    'gateway_response' => json_encode($subscriptionResponse['data']),
                    'paid_at' => in_array($charge['status'], ['approved', 'paid']) ? now() : null
                ]);

                // Se for boleto ou PIX, incluir na resposta JSON
                if ($paymentMethod === 'banking_billet') {
                    $responseData['payment'] = [
                        'barcode' => $subscriptionResponse['data']['barcode'],
                        'pix' => $subscriptionResponse['data']['pix'],
                        'billet_link' => $subscriptionResponse['data']['billet_link'],
                        'pdf' => $subscriptionResponse['data']['pdf']['charge'],
                        'expire_at' => $subscriptionResponse['data']['expire_at']
                    ];
                }
            }

            DB::commit();

            return response()->json($responseData);
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            DB::rollBack();
            return response()->json(['error' => 'Não foi possível criar a assinatura. Tente novamente mais tarde.'], 500);
        }
    }


    public function cancel($subscriptionId)
    {
        $subscription = Subscription::find($subscriptionId);

        if (!$subscription) {
            return response()->json(['error' => 'Nenhuma assinatura encontrada.'], 404);
        }

        try {
            // Chamar serviço de pagamento para cancelar a assinatura
            if (!empty($subscription->external_subscription_id)) {
                $this->paymentService->cancelSubscription($subscription->external_subscription_id);
            }

            // Atualizar status da assinatura no banco
            $subscription->update([
                'is_active' => false,
                'expires_at' => now(),
            ]);

            return response()->json(['message' => 'Assinatura cancelada com sucesso.']);
        } catch (\Exception $e) {
            Log::error('Erro ao cancelar assinatura: '.$e->getMessage());
            return response()->json(['error' => 'Erro ao cancelar a assinatura.'], 500);
        }
    }
}
