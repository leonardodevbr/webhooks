<?php

namespace App\Http\Controllers;

use App\Interfaces\IPaymentService;
use App\Models\Plan;
use App\Models\PlanLimit;
use Illuminate\Http\Request;
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
    public function store(Request $request) {
        // Validação dos dados enviados
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:plans,name',
            'slug' => 'required|string|max:255|unique:plans,slug',
            'description' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
            'billing_cycle' => 'required|in:monthly,yearly',
            'limits' => 'required|array',
            'limit_values' => 'required|array|same:limits'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Criar plano
        $plan = Plan::create($validator->validated());

        // Criar os limites associados ao plano
        foreach ($request->limits as $index => $limit) {
            PlanLimit::create([
                'plan_id' => $plan->id,
                'resource' => $limit,
                'limit_value' => $request->limit_values[$index]
            ]);
        }

        return redirect()->route('plans.index')->with('success', 'Plano criado com sucesso.');
    }

    public function update(Request $request, $id) {
        $plan = Plan::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:plans,name,' . $plan->id,
            'slug' => 'required|string|max:255|unique:plans,slug,' . $plan->id,
            'description' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
            'billing_cycle' => 'required|in:monthly,yearly',
            'limits' => 'required|array',
            'limit_values' => 'required|array|same:limits'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Atualizar plano
        $plan->update($validator->validated());

        // Atualizar limites
        $plan->plan_limits()->delete();
        foreach ($request->limits as $index => $limit) {
            PlanLimit::create([
                'plan_id' => $plan->id,
                'resource' => $limit,
                'limit_value' => $request->limit_values[$index]
            ]);
        }

        return redirect()->route('plans.index')->with('success', 'Plano atualizado com sucesso.');
    }

    /**
     * Exclui o plano.
     */
    public function destroy($id) {
        $plan = Plan::findOrFail($id);
        $plan->plan_limits()->delete();
        $plan->delete();

        return redirect()->route('plans.index')->with('success', 'Plano deletado com sucesso.');
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
