<?php

namespace App\Http\Controllers;

use App\Interfaces\IPaymentService;
use App\Models\Plan;
use App\Services\EfiPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PlanController extends Controller
{
    protected IPaymentService $paymentService;
    public function __construct(IPaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Exibe a lista de planos.
     */
    public function index()
    {
        $plans = Plan::all();
        return view('admin.plans.index', compact('plans'));
    }

    /**
     * Mostra o formulário para criação de um novo plano.
     */
    public function create()
    {
        return view('admin.plans.create');
    }

    /**
     * Processa o formulário de criação e armazena um novo plano.
     */
    public function store(Request $request)
    {
        // Validação dos dados enviados
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:plans,name',
            'slug' => 'required|string|max:255|unique:plans,slug',
            'price' => 'required|numeric|min:0',
            'billing_cycle' => 'required|in:monthly,yearly',
            'max_urls' => 'required|integer|min:1',
            'max_webhooks_per_url' => 'required|integer|min:1',
            'max_retransmission_urls' => 'required|integer|min:1',
            'supports_custom_slugs' => 'sometimes|boolean',
            'real_time_notifications' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Criação do plano
        Plan::create($validator->validated());

        return redirect()->route('plans.index')
            ->with('success', 'Plano criado com sucesso.');
    }

    /**
     * Exibe o formulário para edição de um plano existente.
     */
    public function edit($id)
    {
        $plan = Plan::findOrFail($id);
        return view('admin.plans.edit', compact('plan'));
    }

    /**
     * Atualiza os dados do plano.
     */
    public function update(Request $request, $id)
    {
        $plan = Plan::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:plans,name,' . $plan->id,
            'slug' => 'required|string|max:255|unique:plans,slug,' . $plan->id,
            'price' => 'required|numeric|min:0',
            'billing_cycle' => 'required|in:monthly,yearly',
            'max_urls' => 'required|integer|min:1',
            'max_webhooks_per_url' => 'required|integer|min:1',
            'max_retransmission_urls' => 'required|integer|min:1',
            'supports_custom_slugs' => 'sometimes|boolean',
            'real_time_notifications' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $plan->update($validator->validated());

        if(empty($plan->extenal_plan_id)){
            $externalPlan = $this->paymentService->createPlan($plan->toArray());
        }else{
            $externalPlan = $this->paymentService->updatePlan($plan->toArray());
        }

        if(!empty($externalPlan['data']['plan_id'])){
            $plan->update(['external_plan_id' => $externalPlan['data']['plan_id']]);
        }

        return redirect()->route('plans.index')
            ->with('success', 'Plano atualizado com sucesso.');
    }

    /**
     * Exclui o plano.
     */
    public function destroy($id)
    {
        $plan = Plan::findOrFail($id);
        $plan->delete();

        return redirect()->route('plans.index')
            ->with('success', 'Plano deletado com sucesso.');
    }

    /**
     * Sincroniza o plano com a Efí Pay.
     */
    public function sync($id)
    {
        $plan = Plan::findOrFail($id);

        try {

            if(empty($plan->extenal_plan_id)){
                $externalPlan = $this->paymentService->createPlan($plan->toArray());
            }else{
                $externalPlan = $this->paymentService->updatePlan($plan->toArray());
            }

            if(!empty($externalPlan['data']['plan_id'])){
                $plan->update(['external_plan_id' => $externalPlan['data']['plan_id']]);
            }

            return redirect()->route('plans.index')->with('success', 'Plano sincronizado com sucesso!');
        } catch (\Exception $e) {
            Log::error('Erro ao sincronizar plano: ' . $e->getMessage());
            return redirect()->route('plans.index')->with('error', 'Erro ao sincronizar o plano.');
        }
    }
}
