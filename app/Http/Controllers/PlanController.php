<?php

namespace App\Http\Controllers;

use App\Interfaces\IPaymentService;
use App\Models\Plan;
use App\Models\PlanLimit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PlanController extends Controller {
    protected IPaymentService $paymentService;

    public function __construct(IPaymentService $paymentService) {
        $this->paymentService = $paymentService;
    }

    /**
     * Exibe a lista de planos.
     */
    public function index() {
        $plans = Plan::with('plan_limits')->get();
        return view('admin.plans.index', compact('plans'));
    }

    /**
     * Mostra o formulário para criação de um novo plano.
     */
    public function create() {
        return view('admin.plans.create');
    }


    /**
     * Exibe o formulário para edição de um plano existente.
     */
    public function edit($id) {
        $plan = Plan::with('plan_limits')->findOrFail($id);
        return view('admin.plans.edit', compact('plan'));
    }

    /**
     * Processa o formulário de criação e armazena um novo plano.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:plans,name',
            'slug' => 'required|string|max:255|unique:plans,slug',
            'description' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
            'billing_cycle' => 'required|in:monthly,yearly',
            'limits' => 'required|json'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Criar plano no banco local
            $plan = Plan::create($validator->safe()->except('limits'));

            // Criar plano na API externa
            try {
                $externalPlan = $this->paymentService->createPlan($plan->toArray());
                $plan->update(['external_plan_id' => $externalPlan['data']['plan_id']]);
            } catch (\Exception $e) {
                DB::rollback();
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao criar plano na EFI: ' . $e->getMessage()
                ], 500);
            }

            // Criar limites
            $limits = json_decode($request->limits, true);
            foreach ($limits as $limit) {
                PlanLimit::create([
                    'plan_id' => $plan->id,
                    'resource' => $limit['resource'],
                    'limit_value' => $limit['limit_value'],
                    'description' => $limit['description'],
                    'available' => $limit['available']
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Plano criado com sucesso.',
                'redirect' => route('plans.index')
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Ocorreu um erro ao criar o plano: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $plan = Plan::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:plans,name,' . $plan->id,
            'slug' => 'required|string|max:255|unique:plans,slug,' . $plan->id,
            'description' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
            'billing_cycle' => 'required|in:monthly,yearly',
            'limits' => 'required|json'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Atualizar plano
            if (empty($plan->external_plan_id)) {
                $externalPlan = $this->paymentService->createPlan($plan->toArray());
            } else {
                $this->paymentService->updatePlan($plan->toArray());
            }

            $plan->update(array_merge([$validator->safe()->except('limits'), 'external_plan_id' => $externalPlan['data']['plan_id'] ?? $plan->external_plan_id]));

            // Atualizar limites
            $plan->plan_limits()->delete();

            $limits = json_decode($request->limits, true);
            foreach ($limits as $limit) {
                PlanLimit::create([
                    'plan_id' => $plan->id,
                    'resource' => $limit['resource'],
                    'limit_value' => $limit['limit_value'],
                    'description' => $limit['description'],
                    'available' => $limit['available']
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Plano atualizado com sucesso.',
                'redirect' => route('plans.index')
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e->getMessage());

            return response()->json([
                'success' => false,
                'errors' => ['general' => ['Ocorreu um erro ao salvar o plano. Por favor, tente novamente.']]
            ], 500);
        }
    }

    /**
     * Exclui o plano.
     */
    public function destroy($id) {
        $plan = Plan::findOrFail($id);

        try {
            // Verifica se o plano tem um ID externo vinculado
            if (!empty($plan->external_plan_id)) {
                // Verifica se o plano existe na API da EfiPay antes de tentar excluir
                try {
                    $externalPlan = $this->paymentService->getPlan($plan->external_plan_id);

                    if (!empty($externalPlan['data']['plan_id'])) {
                        // Tenta excluir o plano na API
                        $this->paymentService->deletePlan($plan->external_plan_id);
                    }
                } catch (\Exception $e) {
                    Log::warning('Plano não encontrado na EfíPay ou erro ao buscar: ' . $e->getMessage());
                    // Continua o processo de exclusão no banco caso a API retorne erro de inexistência
                }
            }

            // Verifica se existem assinaturas vinculadas ao plano antes de remover no banco
            if ($plan->subscriptions()->exists()) {
                return redirect()->route('plans.index')->with('error', 'Não é possível excluir um plano com assinaturas vinculadas.');
            }

            // Remove o plano no banco
            $plan->delete();

            return redirect()->route('plans.index')->with('success', 'Plano excluído com sucesso!');
        } catch (\Exception $e) {
            Log::error('Erro ao excluir plano: ' . $e->getMessage());
            return redirect()->route('plans.index')->with('error', 'Erro ao excluir o plano.');
        }
    }


    /**
     * Sincroniza o plano com a Efí Pay.
     */
    public function sync($id) {
        $plan = Plan::findOrFail($id);

        try {
            if (empty($plan->external_plan_id)) {
                $externalPlan = $this->paymentService->createPlan($plan->toArray());
            } else {
                $externalPlan = $this->paymentService->updatePlan($plan->toArray());
            }

            if (!empty($externalPlan['data']['plan_id'])) {
                $plan->update(['external_plan_id' => $externalPlan['data']['plan_id']]);
            }

            return redirect()->route('plans.index')->with('success', 'Plano sincronizado com sucesso!');
        } catch (\Exception $e) {
            Log::error('Erro ao sincronizar plano: ' . $e->getMessage());
            return redirect()->route('plans.index')->with('error', 'Erro ao sincronizar o plano.');
        }
    }
}
