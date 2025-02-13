<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Subscription;
use App\Services\EfiPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EfiPayWebhookController extends Controller
{
    protected EfiPayService $paymentService;

    public function __construct(EfiPayService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function handle(Request $request)
    {
        $notificationToken = $request->input('notification');

        if (!$notificationToken) {
            Log::error("Token de notificação ausente", ['request' => $request->all()]);
            return response()->json(['error' => 'Token de notificação ausente'], 400);
        }

        Log::info("Token de notificação recebido da Efí Pay", ['token' => $notificationToken]);

        // Buscar detalhes da notificação via API da Efí Pay
        $notificationData = $this->paymentService->getNotificationDetails($notificationToken);

//$notificationData = json_decode('{
//  "code": 200,
//  "data": [
//    {
//      "id": 1,
//      "type": "subscription",
//      "custom_id": "2",
//      "status": {
//        "current": "new",
//        "previous": null
//      },
//      "identifiers": {
//        "subscription_id": 95120
//      },
//      "created_at": "2025-02-11 12:02:55"
//    },
//    {
//      "id": 2,
//      "type": "subscription",
//      "custom_id": "2",
//      "status": {
//        "current": "new_charge",
//        "previous": "new"
//      },
//      "identifiers": {
//        "subscription_id": 95120
//      },
//      "created_at": "2025-02-11 12:02:55"
//    },
//    {
//      "id": 3,
//      "type": "subscription_charge",
//      "custom_id": "2",
//      "status": {
//        "current": "new",
//        "previous": null
//      },
//      "identifiers": {
//        "subscription_id": 95120,
//        "charge_id": 44478425
//      },
//      "created_at": "2025-02-11 12:02:55"
//    },
//    {
//      "id": 4,
//      "type": "subscription_charge",
//      "custom_id": "2",
//      "status": {
//        "current": "waiting",
//        "previous": "new"
//      },
//      "identifiers": {
//        "subscription_id": 95120,
//        "charge_id": 44478425
//      },
//      "created_at": "2025-02-11 12:02:55"
//    },
//    {
//      "id": 5,
//      "type": "subscription",
//      "custom_id": "2",
//      "status": {
//        "current": "active",
//        "previous": "new_charge"
//      },
//      "identifiers": {
//        "subscription_id": 95120
//      },
//      "created_at": "2025-02-11 12:02:56"
//    },
//    {
//      "id": 6,
//      "type": "subscription_charge",
//      "custom_id": "2",
//      "status": {
//        "current": "approved",
//        "previous": "waiting"
//      },
//      "identifiers": {
//        "subscription_id": 95120,
//        "charge_id": 44478425
//      },
//      "created_at": "2025-02-11 14:53:48"
//    },
//    {
//      "id": 7,
//      "type": "subscription_charge",
//      "custom_id": "2",
//      "status": {
//        "current": "paid",
//        "previous": "approved"
//      },
//      "identifiers": {
//        "subscription_id": 95120,
//        "charge_id": 44478425
//      },
//      "created_at": "2025-02-11 14:54:18",
//      "value": 1999
//    }
//  ]
//}
//', true);

        if (!$notificationData || empty($notificationData['data'])) {
            Log::error("Dados da notificação incompletos", ['notification' => $notificationData]);
            return response()->json(['error' => 'Dados da notificação incompletos'], 400);
        }

        // Pegar o último evento da lista
        $lastEvent = end($notificationData['data']);

        return $this->processEvent($lastEvent);
    }

    private function processEvent($event)
    {
        $type = $event['type'];
        $status = $event['status']['current'] ?? null;
        $subscriptionId = $event['identifiers']['subscription_id'] ?? null;
        $chargeId = $event['identifiers']['charge_id'] ?? null;

        if (!$subscriptionId || !$status) {
            Log::warning("Evento ignorado por falta de informações", ['event' => $event]);
            return response()->json(['error' => 'Evento inválido'], 400);
        }

        Log::info("Processando evento da Efí Pay", [
            'type' => $type,
            'status' => $status,
            'subscription_id' => $subscriptionId,
            'charge_id' => $chargeId ?? 'N/A'
        ]);

        $subscription = Subscription::where('external_subscription_id', $subscriptionId)->first();

        if (!$subscription) {
            Log::error("Assinatura não encontrada", ['subscription_id' => $subscriptionId]);
            return response()->json(['error' => 'Assinatura não encontrada'], 404);
        }

        // Diferenciar eventos de assinatura e cobrança
        return match ($type) {
            'subscription' => $this->handleSubscriptionEvent($subscription, $status),
            'subscription_charge' => $this->handleChargeEvent($subscription, $chargeId, $status),
            default => $this->handleUnknownEvent($type),
        };
    }

    private function handleSubscriptionEvent($subscription, $status)
    {
        match ($status) {
            'active' => $subscription->update(['is_active' => true]),
            'canceled' => $subscription->update(['is_active' => false]),
            default => Log::warning("Status de assinatura não tratado", ['status' => $status])
        };

        Log::info("Status de assinatura atualizado", [
            'subscription_id' => $subscription->id,
            'status' => $status
        ]);

        return response()->json(['status' => 'assinatura atualizada']);
    }

    private function handleChargeEvent($subscription, $chargeId, $status)
    {
        if (!$chargeId) {
            Log::warning("Evento de cobrança sem charge_id", ['subscription_id' => $subscription->id]);
            return response()->json(['error' => 'Evento de cobrança inválido'], 400);
        }

        match ($status) {
            'waiting' => Log::info("Pagamento pendente", ['charge_id' => $chargeId]),
            'paid' => $this->handlePaymentSuccess($subscription, $chargeId),
            'unpaid', 'expired', 'canceled' => $this->handlePaymentFailure($subscription, $chargeId),
            default => Log::warning("Status de cobrança não tratado", ['status' => $status])
        };

        return response()->json(['status' => 'evento de cobrança atualizado']);
    }

    private function handlePaymentSuccess($subscription, $chargeId)
    {
        $externalPayment = $this->paymentService->getPayment($chargeId);

        if (!$externalPayment || !isset($externalPayment['data']['status'])) {
            Log::error("Falha ao buscar detalhes do pagamento", ['charge_id' => $chargeId]);
            return response()->json(['error' => 'Erro ao buscar detalhes do pagamento'], 500);
        }

        Payment::updateOrCreate(
            ['external_payment_id' => $chargeId],
            [
                'account_id' => $subscription->account_id,
                'subscription_id' => $subscription->id,
                'status' => $externalPayment['data']['status'],
                'amount' => ($externalPayment['data']['total'] ?? 0) / 100,
                'payment_method' => $externalPayment['data']['payment']['method'] ?? 'desconhecido',
                'gateway_response' => json_encode($externalPayment),
                'paid_at' => $externalPayment['data']['status'] === 'paid' ? $externalPayment['data']['payment']['created_at'] ?? now() : null
            ]
        );

        $subscription->update(['is_active' => $externalPayment['data']['status'] == 'paid']);

        Log::info("Pagamento confirmado", ['subscription_id' => $subscription->id, 'charge_id' => $chargeId]);
        return response()->json(['status' => 'pagamento confirmado']);
    }

    private function handlePaymentFailure($subscription, $chargeId)
    {
        $externalPayment = $this->paymentService->getPayment($chargeId);

        if (!$externalPayment || !isset($externalPayment['data']['status'])) {
            Log::error("Falha ao buscar detalhes do pagamento", ['charge_id' => $chargeId]);
            return response()->json(['error' => 'Erro ao buscar detalhes do pagamento'], 500);
        }

        Payment::updateOrCreate(
            ['external_payment_id' => $chargeId],
            [
                'account_id' => $subscription->account_id,
                'subscription_id' => $subscription->id,
                'status' => $externalPayment['data']['status'],
                'amount' => ($externalPayment['data']['total'] ?? 0) / 100,
                'payment_method' => $externalPayment['data']['payment']['method'] ?? 'desconhecido',
                'gateway_response' => json_encode($externalPayment),
                'paid_at' => null
            ]
        );

        Log::warning("Pagamento falhou", ['subscription_id' => $subscription->id, 'charge_id' => $chargeId]);
        return response()->json(['status' => 'pagamento falhou']);
    }

    private function handleUnknownEvent($type)
    {
        Log::warning("Tipo de evento desconhecido recebido", ['type' => $type]);
        return response()->json(['status' => 'evento ignorado']);
    }
}
