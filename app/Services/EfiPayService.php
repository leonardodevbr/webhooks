<?php

namespace App\Services;

use App\Interfaces\IPaymentService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EfiPayService implements IPaymentService
{
    protected $baseUrl;
    protected $key;
    protected $secret;

    public function __construct()
    {
        $this->baseUrl = config('services.efipay.base_url');
        $this->key = config('services.efipay.key');
        $this->secret = config('services.efipay.secret');
    }

    /**
     * Obtém o token de acesso da Efí Pay.
     *
     * @return string
     * @throws \Exception
     */
    protected function getAccessToken()
    {
        // Verifica se o token está em cache
        if (Cache::has('efipay_access_token')) {
            return Cache::get('efipay_access_token');
        }
        // Faz a requisição para obter o token
        $response = Http::withBasicAuth($this->key, $this->secret)
            ->post("{$this->baseUrl}/authorize", [
                'grant_type' => 'client_credentials',
            ]);

        if ($response->successful()) {
            $data = $response->json();
            $accessToken = $data['access_token'];
            $expiresIn = $data['expires_in'];

            // Armazena o token em cache pelo tempo de expiração
            Cache::put('efipay_access_token', $accessToken, $expiresIn);

            return $accessToken;
        }

        throw new \Exception('Erro ao obter o token de acesso da Efí Pay: '.$response->body());
    }

    public function listPlans(int $planId = null)
    {
        $accessToken = $this->getAccessToken();

        $response = Http::withToken($accessToken)
            ->get("{$this->baseUrl}/plans");

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('Erro ao carregar os planos na Efí Pay: '.$response->body());
    }

    public function deletePlan(int $planId)
    {
        $accessToken = $this->getAccessToken();

        $response = Http::withToken($accessToken)
            ->delete("{$this->baseUrl}/plan/{$planId}");

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('Erro ao deletar o plano na Efí Pay: '.$response->body());
    }

    /**
     * Cria um plano na Efí Pay.
     *
     * @param  array  $planData
     * @return array
     * @throws \Exception
     */
    public function createPlan(array $plan)
    {
        $accessToken = $this->getAccessToken();

        $planData = [
            'name' => $plan['name'],
            'interval' => $this->mapBillingCycle($plan['billing_cycle']),
            'repeats' => null
        ];

        $response = Http::withToken($accessToken)
            ->post("{$this->baseUrl}/plan", $planData);

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('Erro ao criar o plano na Efí Pay: '.$response->body());
    }

    public function updatePlan(array $plan)
    {
        $accessToken = $this->getAccessToken();

        $planData = [
            'name' => $plan['name'],
            'interval' => $this->mapBillingCycle($plan['billing_cycle']),
            'repeats' => null
        ];

        $response = Http::withToken($accessToken)
            ->put("{$this->baseUrl}/plan/{$plan['external_plan_id']}", $planData);

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('Erro ao atualizar o plano na Efí Pay: '.$response->body());
    }

    private function mapBillingCycle($billingCycle): int
    {
        $cycles = [
            'monthly' => 1,
            'yearly' => 12
        ];

        return $cycles[$billingCycle] ?? 'MENSAL';
    }

    public function createSubscription(array $subscriptionData)
    {
        $accessToken = $this->getAccessToken();
        $notificationUrl = config('services.efipay.notification_url');

        // Estrutura base dos itens
        $payload = [
            'items' => [
                [
                    'name' => $subscriptionData['name'],
                    'value' => (int) ($subscriptionData['price'] * 100), // Valor em centavos
                    'amount' => 1
                ]
            ],
            'metadata' => [
                'notification_url' => $notificationUrl,
                'custom_id' => (string)$subscriptionData['custom_id'] ?? null
            ]
        ];

        // Adiciona os detalhes de pagamento conforme o método escolhido
        if ($subscriptionData['payment_method'] === 'banking_billet') {
            $payload['payment'] = [
                'banking_billet' => [
                    'customer' => [
                        'name' => $subscriptionData['customer_name'],
                        'cpf' => $subscriptionData['customer_cpf'],
                        'email' => $subscriptionData['customer_email'],
                        'phone_number' => $subscriptionData['customer_phone'],
                        'address' => [
                            'street' => $subscriptionData['customer_address']['street'],
                            'number' => $subscriptionData['customer_address']['number'],
                            'neighborhood' => $subscriptionData['customer_address']['neighborhood'],
                            'zipcode' => $subscriptionData['customer_address']['zipcode'],
                            'city' => $subscriptionData['customer_address']['city'],
                            'complement' => $subscriptionData['customer_address']['complement'] ?? '',
                            'state' => $subscriptionData['customer_address']['state']
                        ]
                    ],
                    'expire_at' => now()->addDays(3)->format('Y-m-d'), // Vencimento em 3 dias
                    'configurations' => [
                        'fine' => 200, // Multa de 2%
                        'interest' => 33 // Juros de 0,33% ao dia
                    ],
                    'message' => 'Pague pelo código de barras ou pelo QR Code'
                ]
            ];
        } elseif ($subscriptionData['payment_method'] === 'credit_card') {
            $payload['payment'] = [
                'credit_card' => [
                    'customer' => [
                        'name' => $subscriptionData['customer_name'],
                        'cpf' => $subscriptionData['customer_cpf'],
                        'email' => $subscriptionData['customer_email'],
                        'birth' => $subscriptionData['customer_birth'],
                        'phone_number' => $subscriptionData['customer_phone']
                    ],
                    'payment_token' => $subscriptionData['payment_token'], // Token do cartão
                    'billing_address' => [
                        'street' => $subscriptionData['customer_address']['street'],
                        'number' => $subscriptionData['customer_address']['number'],
                        'neighborhood' => $subscriptionData['customer_address']['neighborhood'],
                        'zipcode' => $subscriptionData['customer_address']['zipcode'],
                        'city' => $subscriptionData['customer_address']['city'],
                        'complement' => $subscriptionData['customer_address']['complement'] ?? '',
                        'state' => $subscriptionData['customer_address']['state']
                    ]
                ]
            ];
        } else {
            throw new \Exception('Método de pagamento inválido.');
        }

        // Envio da requisição para a Efí Pay
        $response = Http::withToken($accessToken)
            ->post("{$this->baseUrl}/plan/{$subscriptionData['external_plan_id']}/subscription/one-step", $payload);

        Log::debug("Subscribe Creation Response", $response->json());

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('Erro ao criar a assinatura na Efí Pay: ' . $response->body());
    }

    public function getSubscription(string $subscriptionId)
    {
        $accessToken = $this->getAccessToken();

        $response = Http::withToken($accessToken)
            ->get("{$this->baseUrl}/subscription/{$subscriptionId}");

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('Erro ao buscar detalhes da assinatura: '.$response->body());
    }

    public function listSubscriptions()
    {
        $accessToken = $this->getAccessToken();

        $response = Http::withToken($accessToken)
            ->get("{$this->baseUrl}/subscriptions");

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('Erro ao listar assinaturas: '.$response->body());
    }

    public function updateSubscription(string $subscriptionId, array $data)
    {
        $accessToken = $this->getAccessToken();

        $response = Http::withToken($accessToken)
            ->put("{$this->baseUrl}/subscription/{$subscriptionId}", $data);

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('Erro ao atualizar a assinatura: '.$response->body());
    }

    public function cancelSubscription(string $subscriptionId)
    {
        $accessToken = $this->getAccessToken();

        $response = Http::withToken($accessToken)
            ->put("{$this->baseUrl}/subscription/{$subscriptionId}/cancel");

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('Erro ao cancelar a assinatura: '.$response->body());
    }


    public function setPaymentMethod(string $subscriptionId, array $paymentData)
    {
        $accessToken = $this->getAccessToken();

        $response = Http::withToken($accessToken)
            ->post("{$this->baseUrl}/subscription/{$subscriptionId}/pay", [
                'payment' => [
                    'credit_card' => [
                        'customer' => $paymentData['customer'],
                        'billing_address' => $paymentData['billing_address'],
                        'payment_token' => $paymentData['payment_token']
                    ]
                ]
            ]);

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('Erro ao definir forma de pagamento da assinatura: '.$response->body());
    }

    public function getNotificationDetails(string $notificationToken)
    {
        $accessToken = $this->getAccessToken();
        $response = Http::withToken($accessToken)
            ->get("{$this->baseUrl}/notification/{$notificationToken}");

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('Erro ao obter detalhes da notificação: ' . $response->body());
    }

    public function getPayment(string $paymentId)
    {
        $accessToken = $this->getAccessToken();

        $response = Http::withToken($accessToken)
            ->get("{$this->baseUrl}/charge/{$paymentId}");

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('Erro ao buscar detalhes da assinatura: '.$response->body());
    }

}
