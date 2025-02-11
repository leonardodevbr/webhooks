<?php

namespace App\Http\Controllers;

use App\Interfaces\IPaymentService;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EfiPayWebhookController extends Controller
{
    protected IPaymentService $paymentService;

    public function __construct(IPaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Processa o webhook recebido da Efí Pay
     */
    public function handle(Request $request)
    {
        $notificationToken = $request->input('notification');
        Log::info('Token de notificação recebido da Efí Pay', ['token' => $notificationToken]);

        if (!$notificationToken) {
            return response()->json(['error' => 'Token de notificação não encontrado'], 400);
        }

        // Obtém os detalhes da notificação usando o serviço
        try {
            $notificationData = $this->paymentService->getNotificationDetails($notificationToken);
        } catch (\Exception $e) {
            Log::error('Erro ao consultar detalhes da notificação: ' . $e->getMessage());
            return response()->json(['error' => 'Erro ao consultar detalhes da notificação'], 500);
        }

        // Separa os dados importantes
        $status = $notificationData['status'] ?? null;
        $subscriptionId = $notificationData['subscription_id'] ?? null;

        if (!$status || !$subscriptionId) {
            Log::error('Dados da notificação incompletos', ['notification' => $notificationData]);
            return response()->json(['error' => 'Dados da notificação incompletos'], 400);
        }

        // Processa conforme o status
        switch ($status) {
            case 'paid':
                return $this->handlePaymentSuccess($subscriptionId, $notificationData);
            case 'unpaid':
            case 'expired':
                return $this->handlePaymentFailure($subscriptionId, $notificationData);
            case 'canceled':
                return $this->handleSubscriptionCanceled($subscriptionId);
            default:
                Log::warning('Status da notificação não tratado', ['status' => $status]);
                return response()->json(['status' => 'status não tratado']);
        }
    }

    /**
     * Trata pagamentos bem-sucedidos.
     */
    private function handlePaymentSuccess($subscriptionId, $notificationData)
    {
        $subscription = Subscription::where('external_subscription_id', $subscriptionId)->first();

        if (!$subscription) {
            Log::error("Assinatura não encontrada: " . $subscriptionId);
            return response()->json(['error' => 'Assinatura não encontrada'], 404);
        }

        $subscription->update([
            'is_active' => true,
            'expires_at' => now()->addMonth(),
        ]);

        Payment::create([
            'account_id' => $subscription->accounts->first()->id,
            'plan_id' => $subscription->plan_id,
            'gateway_reference' => $notificationData['charge_id'],
            'status' => 'paid',
            'amount' => $notificationData['amount'] / 100,
            'payment_method' => $notificationData['payment_method'],
            'paid_at' => now()
        ]);

        Log::info("Assinatura ativada", ['subscription_id' => $subscription->id]);
        return response()->json(['status' => 'assinatura ativada']);
    }

    /**
     * Trata falhas no pagamento ou expiração.
     */
    private function handlePaymentFailure($subscriptionId, $notificationData)
    {
        $subscription = Subscription::where('external_subscription_id', $subscriptionId)->first();

        if (!$subscription) {
            Log::error("Assinatura não encontrada: " . $subscriptionId);
            return response()->json(['error' => 'Assinatura não encontrada'], 404);
        }

        $subscription->update([
            'is_active' => false,
            'expires_at' => now()
        ]);

        Payment::where('gateway_reference', $notificationData['charge_id'])->update([
            'status' => 'failed'
        ]);

        Log::warning("Assinatura desativada por falta de pagamento", ['subscription_id' => $subscription->id]);
        return response()->json(['status' => 'assinatura desativada']);
    }

    /**
     * Trata assinaturas canceladas.
     */
    private function handleSubscriptionCanceled($subscriptionId)
    {
        $subscription = Subscription::where('external_subscription_id', $subscriptionId)->first();

        if (!$subscription) {
            Log::error("Assinatura não encontrada: " . $subscriptionId);
            return response()->json(['error' => 'Assinatura não encontrada'], 404);
        }

        $subscription->update([
            'is_active' => false,
            'expires_at' => now()
        ]);

        Log::info("Assinatura cancelada", ['subscription_id' => $subscription->id]);
        return response()->json(['status' => 'assinatura cancelada']);
    }
}
