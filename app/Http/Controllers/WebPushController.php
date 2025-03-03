<?php

namespace App\Http\Controllers;

use App\Models\Url;
use App\Models\Webhook;
use App\Models\WebhookRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushController extends Controller
{
    public function subscribe(Request $request)
    {
        $subscription = $request->getContent();

        if (!$subscription) {
            return response()->json(['error' => 'Subscription inválida'], 400);
        }

        file_put_contents(storage_path('push-subscriptions.json'), $subscription);

        return response()->json(['success' => 'Inscrição salva!']);
    }

    public function sendNotification(Url $url, WebhookRequest $webhookRequest)
    {
        $subscriptionData = json_decode(file_get_contents(storage_path('push-subscriptions.json')), true);
        $subscription = Subscription::create($subscriptionData);

        $webPush = new WebPush([
            'VAPID' => [
                'subject' => config('app.url'),
                'publicKey' => config('services.vapid.public_key'),
                'privateKey' => config('services.vapid.private_key'),
            ],
        ]);

        $options = [
            'title' => 'New request: '.$webhookRequest->hash,
            'body' => "{$webhookRequest->method} from {$webhookRequest->ip} ({$webhookRequest->size} bytes)\n" . json_encode(json_decode($webhookRequest->body), JSON_PRETTY_PRINT),
            'icon' => public_path('/apple-touch-icon.png'),
            'tag' => $webhookRequest->hash->toString(),
            'data' => ['url' => route('public.view', $url->slug)]
        ];

        $webPush->queueNotification($subscription, json_encode($options));

        foreach ($webPush->flush() as $report) {
            if ($report->isSuccess()) {
                Log::info("Notificação enviada com sucesso!");
            } else {
                Log::error("Erro ao enviar notificação: ".$report->getReason());
            }
        }

        return response()->json(['success' => 'Notificação enviada!']);
    }
}
