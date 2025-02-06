<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EfiPayWebhookController extends Controller
{
    public function handle(Request $request)
    {
        // Validação da origem do webhook pode ser feita aqui

        $data = $request->all();
        Log::info('Webhook recebido da Efí Pay', $data);

        // Exemplo: Se o webhook indicar que um pagamento foi aprovado
        if (isset($data['event']) && $data['event'] === 'payment.approved') {
            $externalSubscriptionId = $data['subscription_id'] ?? null;
            $paymentStatus = $data['status'] ?? null;

            if ($externalSubscriptionId && $paymentStatus === 'approved') {
                // Atualize o status da assinatura/pagamento
                $subscription = Subscription::where('external_subscription_id', $externalSubscriptionId)->first();
                if ($subscription) {
                    // Atualize o pagamento ou status da assinatura conforme necessário
                    Payment::create([
                        'account_id' => $subscription->accounts()->first()->id ?? null,
                        'plan_id' => $subscription->plan_id,
                        'gateway_reference' => $data['payment_id'] ?? null,
                        'status' => 'approved',
                        'amount' => $data['amount'] ?? 0,
                        'payment_method' => $data['payment_method'] ?? null,
                        'gateway_response' => $data,
                        'paid_at' => now(),
                    ]);
                }
            }
        }

        // Retorne uma resposta para confirmar o recebimento
        return response()->json(['status' => 'ok']);
    }
}
